<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use Exception;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\ConfigLexicon;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\UnknownInterfaceException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Probes\BasicProbe;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Exceptions\InvalidRemoteInstanceException;
use OCA\Circles\Tools\Exceptions\ItemNotFoundException;
use OCA\Circles\Tools\Exceptions\SignatoryException;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IRequest;

/**
 * Handles requests received from remote instances.
 */
class RemoteEndpointService {
	use TDeserialize;
	use TNCLogger;

	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly RemoteDownstreamService $remoteDownstreamService,
		private readonly FederatedUserService $federatedUserService,
		private readonly CircleService $circleService,
		private readonly MemberService $memberService,
		private readonly InterfaceService $interfaceService,
		private readonly ConfigService $configService,
	) {
		$this->setup('app', Application::APP_ID);
	}

	/**
	 * Sets the current interface from the host of the request and refuses events
	 * received on the frontal interface while Federated Teams are disabled.
	 *
	 * @throws UnknownInterfaceException
	 * @throws FederatedEventException
	 */
	public function confirmEventInterface(IRequest $request): void {
		$this->interfaceService->setCurrentInterfaceFromRequest($request);
		$iface = $this->interfaceService->getCurrentInterface();
		if ($iface === InterfaceService::IFACE_FRONTAL
			&& !$this->appConfig->getAppValueBool(ConfigLexicon::FEDERATED_TEAMS_ENABLED)) {
			throw new FederatedEventException('frontal interface is not enabled');
		}
	}

	/**
	 * Confirms that the sender of the request is a known and trusted remote instance,
	 * and sets the current interface to the one used to reach it.
	 *
	 * @param RemoteInstance|null $remoteInstance the remote instance that signed the request
	 * @param string $origin the origin of the signed request
	 *
	 * @throws SignatoryException
	 */
	public function confirmRemoteInstance(?RemoteInstance $remoteInstance, string $origin): RemoteInstance {
		if ($remoteInstance === null) {
			$this->debug('Signatory is not a known RemoteInstance', ['origin' => $origin]);
			throw new SignatoryException('Could not confirm identity');
		}

		if (!$this->configService->isLocalInstance($origin)
			&& $remoteInstance->getType() === RemoteInstance::TYPE_UNKNOWN) {
			$this->debug('Could not confirm identity', ['origin' => $origin]);
			throw new SignatoryException('Could not confirm identity');
		}

		$this->interfaceService->setCurrentInterface($remoteInstance->getInterface());

		return $remoteInstance;
	}

	public function importEvent(array $data, string $origin): FederatedEvent {
		$event = new FederatedEvent();
		$event->import($data);
		$event->setSender($origin);

		return $event;
	}

	/**
	 * Sets the remote instance and the initiator used to filter the data returned
	 * to the remote instance, and extracts the filters sent with the request.
	 *
	 * @throws InvalidRemoteInstanceException
	 */
	public function prepareDataRequest(RemoteInstance $remoteInstance, ?array $payload): SimpleDataStore {
		// There should be no need to confirm the need or the origin of the initiator as $remoteInstance
		// already helps filtering request to the database.
		// initiator here is only used to play with the visibility, on top of the visibility provided to
		// the remote instance based on its type.
		$this->federatedUserService->setRemoteInstance($remoteInstance);

		$data = new SimpleDataStore();
		$store = new SimpleDataStore($payload);
		try {
			/** @var FederatedUser $initiator */
			$initiator = $store->gObj('initiator', FederatedUser::class);
			if ($initiator->getInstance() !== $remoteInstance->getInstance() && !in_array($initiator->getInstance(), $remoteInstance->getAliases(), true)) {
				throw new InvalidRemoteInstanceException('Initiator instance does not match remote instance');
			}
			$this->federatedUserService->setCurrentUser($initiator);
		} catch (InvalidItemException|ItemNotFoundException) {
		}

		try {
			/** @var FederatedUser $initiator */
			$filterMember = $store->gObj('filterMember', Member::class);
			$data->aObj('filterMember', $filterMember);
		} catch (InvalidItemException|ItemNotFoundException) {
		}

		try {
			/** @var FederatedUser $initiator */
			$filterCircle = $store->gObj('filterCircle', Circle::class);
			$data->aObj('filterCircle', $filterCircle);
		} catch (InvalidItemException|ItemNotFoundException) {
		}

		return $data;
	}

	/**
	 * @throws Exception
	 */
	public function requestedEvent(FederatedEvent $event): array {
		$this->remoteDownstreamService->requestedEvent($event);

		return $event->getOutcome();
	}

	/**
	 * @throws Exception
	 */
	public function incomingEvent(FederatedEvent $event): array {
		$this->remoteDownstreamService->incomingEvent($event);

		return $this->serialize($event->getResult());
	}

	/**
	 * @return Circle[]
	 * @throws Exception
	 */
	public function getCircles(SimpleDataStore $data): array {
		/** @var Circle $filterCircle */
		$filterCircle = $data->gObj('filterCircle');
		/** @var Member $filterMember */
		$filterMember = $data->gObj('filterMember');

		$probe = new CircleProbe();
		$probe->setFilterCircle($filterCircle)
			->setFilterMember($filterMember)
			->addDetail(BasicProbe::DETAILS_POPULATION);

		return $this->circleService->getCircles($probe);
	}

	/**
	 * @throws Exception
	 */
	public function getCircle(string $circleId): array {
		return $this->serialize($this->circleService->getCircle($circleId));
	}

	/**
	 * @return Member[]
	 * @throws Exception
	 */
	public function getMembers(string $circleId): array {
		return $this->memberService->getMembers($circleId, true);
	}

	/**
	 * @throws Exception
	 */
	public function getFederatedUser(string $type, string $userId): array {
		// FILTER CIRCLE BASED ON THE CONFIG/FEDERATED_8192 !!
		if ($type === Member::$TYPE[Member::TYPE_SINGLE]) {
			$federatedUser = $this->federatedUserService->getFederatedUser($userId, Member::TYPE_SINGLE);
		} elseif ($type === Member::$TYPE[Member::TYPE_CIRCLE]) {
			$federatedUser = $this->federatedUserService->getFederatedUser($userId, Member::TYPE_CIRCLE);
		} elseif ($type === Member::$TYPE[Member::TYPE_USER]) {
			$federatedUser = $this->federatedUserService->getLocalFederatedUser($userId);
		} else {
			throw new FederatedUserNotFoundException('Entity not found');
		}

		return $this->serialize($federatedUser);
	}

	/**
	 * @return Member[]
	 * @throws Exception
	 */
	public function getInheritedMembers(string $circleId): array {
		return $this->circleService->getCircle($circleId)->getInheritedMembers();
	}

	/**
	 * @throws Exception
	 */
	public function getMemberships(string $circleId): array {
		return $this->circleService->getCircle($circleId)->getMemberships();
	}

	public function exceptionResponse(
		Exception $e,
		int $httpErrorCode = Http::STATUS_BAD_REQUEST,
	): DataResponse {
		if ($e instanceof FederatedItemException) {
			return new DataResponse($this->serialize($e), $e->getStatus());
		}

		$errorCode = (int)$e->getCode();
		return new DataResponse(
			[
				'message' => $e->getMessage(),
				'code' => $e->getCode()
			],
			($errorCode > 0) ? $errorCode : $httpErrorCode
		);
	}
}
