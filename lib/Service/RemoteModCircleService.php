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
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\FederatedItems\CircleCreate;
use OCA\Circles\FederatedItems\SingleMemberAdd;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\ManagedModel;
use OCA\Circles\Model\Member;
use OCA\Circles\Tools\Model\NCRequest;
use OCA\Circles\Tools\Model\Request;
use OCA\Circles\Tools\Traits\TNCWellKnown;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\AppFramework\Services\IAppConfig;
use Psr\Log\LoggerInterface;

class RemoteModCircleService {
	use TNCWellKnown;
	use TStringTools;

	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly RemoteStreamService $remoteStreamService,
		private readonly InterfaceService $interfaceService,
		private readonly FederatedUserService $federatedUserService,
		private readonly CircleService $circleService,
		private readonly LoggerInterface $logger,
		private readonly FederatedEventService $federatedEventService,
		private readonly CircleRequest $circleRequest,
		private readonly MemberService $memberService,
		private readonly PermissionService $permissionService,
		private readonly MemberRequest $memberRequest,
	) {
	}

	public function discoverModeratorCircles(): void {
		$this->interfaceService->setCurrentInterface(InterfaceService::IFACE_FRONTAL);

		$remoteInstances = $this->appConfig->getAppValueArray(ConfigLexicon::REMOTE_MOD_CIRCLE_INSTANCES);
		if ($remoteInstances === []) {
			$this->logger->debug('no remote instance configured, skipping discovery');
			return;
		}

		$remoteModeratorMapping = [];
		foreach ($remoteInstances as $remoteInstance) {
			try {
				$moderatorCircleId = $this->requestModerator($remoteInstance);
				$remoteModeratorMapping[$remoteInstance] = $moderatorCircleId;
			} catch (Exception $e) {
				$this->logger->error('could not discover moderator from remote instance', ['instance' => $remoteInstance, 'exception' => $e]);
			}
		}

		$this->appConfig->setAppValueArray(ConfigLexicon::REMOTE_MOD_CIRCLE_MAPPING, $remoteModeratorMapping);
	}

	public function syncModeratorCircles(): void {
		$this->federatedUserService->setLocalCurrentApp(Application::APP_ID, Member::APP_CIRCLES);
		$currentApp = $this->federatedUserService->getCurrentApp();
		$this->federatedUserService->setCurrentUser($currentApp);

		$mapping = $this->appConfig->getAppValueArray(ConfigLexicon::REMOTE_MOD_CIRCLE_MAPPING);
		if ($mapping === []) {
			$this->logger->debug('no remote moderator known, skipping reconciliation');
			return;
		}

		$thirdPartyCircles = $this->circleRequest->getThirdParty();
		if ($thirdPartyCircles === []) {
			$this->logger->debug('no third party circle known, skipping reconciliation');
			return;
		}

		foreach ($thirdPartyCircles as $circle) {
			foreach ($mapping as $remoteInstance => $moderatorSingleId) {
				$this->ensureModerator($circle->getSingleId(), $remoteInstance, $moderatorSingleId);
			}
		}
	}

	/**
	 * @throws Exception
	 */
	private function requestModerator(string $remoteInstance): string {
		$request = new NCRequest('', Request::TYPE_POST);
		$request->basedOnUrl(rtrim($remoteInstance, '/') . '/index.php/apps/circles/moderator/');
		$request->setFollowLocation(true);
		$request->setLocalAddressAllowed(true);
		$request->setTimeout(5);
		// data cannot be an empty array
		$request->setData(['payload' => true]);

		$app = $this->remoteStreamService->getAppSignatory();
		$signedRequest = $this->remoteStreamService->signOutgoingRequest($request, $app);
		$outgoingRequest = $signedRequest->getOutgoingRequest();
		$outgoingRequest->setLocalAddressAllowed(true);
		$outgoingRequest->setFollowLocation(true);

		$this->doRequest($outgoingRequest);

		$result = $outgoingRequest->getResult();
		if ($result->getStatusCode() !== 200) {
			throw new Exception('HTTP ' . $result->getStatusCode());
		}

		$data = json_decode($result->getContent(), true);
		if (empty($data['circleId'])) {
			throw new Exception('unexpected response');
		}

		return $data['circleId'];
	}

	private function ensureModerator(string $circleId, string $remoteInstance, string $moderatorSingleId): void {
		try {
			$this->memberRequest->getMember($circleId, $moderatorSingleId);
			return;
		} catch (MemberNotFoundException) {
		}

		try {
			$federatedUser = $this->federatedUserService->getFederatedUser($moderatorSingleId . '@' . $remoteInstance, Member::TYPE_CIRCLE);
			$circle = $this->circleRequest->getCircle($circleId, $this->federatedUserService->getCurrentUser());

			$member = new Member();
			$member->importFromIFederatedUser($federatedUser);

			$this->federatedUserService->setMemberPatron($member);

			$event = new FederatedEvent(SingleMemberAdd::class);
			$event->setCircle($circle);
			$event->setMember($member);
			$event->setAsync(false);
			$this->federatedEventService->newEvent($event);

			$addedMember = $event->getMember();
			$this->memberService->memberLevel($addedMember->getId(), Member::LEVEL_MODERATOR);

			$this->logger->debug('moderator from remote instance added to circle', ['circleId' => $circleId, 'memberId' => $addedMember->getId(), 'remoteInstance' => $remoteInstance]);
		} catch (Exception $e) {
			$this->logger->error('could not add moderator from remote instance to circle', ['circleId' => $circleId, 'remoteInstance' => $remoteInstance, 'exception' => $e]);
		}
	}

	/**
	 * @throws Exception
	 */
	public function createCircle(string $name): array {
		$this->federatedUserService->setLocalCurrentApp(Application::APP_ID, Member::APP_CIRCLES);
		$owner = $this->federatedUserService->getCurrentApp();

		$config = Circle::CFG_BACKEND;

		$circle = new Circle();
		$circle->setName($this->circleService->cleanCircleName($name))
			->setSingleId($this->token(ManagedModel::ID_LENGTH))
			->setSource(Member::APP_CIRCLES)
			->setConfig($config);

		$this->circleService->confirmName($circle);
		$this->permissionService->confirmAllowedCircleTypes($circle);

		$member = new Member();
		$member->importFromIFederatedUser($owner);
		$member->setId($this->token(ManagedModel::ID_LENGTH))
			->setCircleId($circle->getSingleId())
			->setLevel(Member::LEVEL_OWNER)
			->setStatus(Member::STATUS_MEMBER);

		$this->federatedUserService->setMemberPatron($member);

		$circle->setOwner($member)
			->setInitiator($member);

		$event = new FederatedEvent(CircleCreate::class);
		$event->setCircle($circle);
		$this->federatedEventService->newEvent($event);

		return $event->getOutcome();
	}
}
