<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2020
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Circles\Controller;

use daita\MySmallPhpTools\Exceptions\InvalidItemException;
use daita\MySmallPhpTools\Exceptions\InvalidOriginException;
use daita\MySmallPhpTools\Exceptions\JsonNotRequestedException;
use daita\MySmallPhpTools\Exceptions\MalformedArrayException;
use daita\MySmallPhpTools\Exceptions\SignatoryException;
use daita\MySmallPhpTools\Exceptions\SignatureException;
use daita\MySmallPhpTools\Exceptions\UnknownTypeException;
use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21SignedRequest;
use daita\MySmallPhpTools\Model\SimpleDataStore;
use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21Controller;
use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21LocalSignatory;
use Exception;
use OC\AppFramework\Middleware\Security\Exceptions\NotLoggedInException;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use OCA\Circles\Service\RemoteDownstreamService;
use OCA\Circles\Service\RemoteStreamService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;


/**
 * Class RemoteController
 *
 * @package OCA\Circles\Controller
 */
class RemoteController extends Controller {


	use TNC21Controller;
	use TNC21LocalSignatory;


	/** @var CircleRequest */
	private $circleRequest;

	/** @var RemoteStreamService */
	private $remoteStreamService;

	/** @var RemoteDownstreamService */
	private $remoteDownstreamService;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var CircleService */
	private $circleService;

	/** @var MemberService */
	private $memberService;

	/** @var ConfigService */
	private $configService;


	/**
	 * RemoteController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param CircleRequest $circleRequest
	 * @param RemoteStreamService $remoteStreamService
	 * @param RemoteDownstreamService $remoteDownstreamService
	 * @param FederatedUserService $federatedUserService
	 * @param CircleService $circleService
	 * @param MemberService $memberService
	 * @param ConfigService $configService
	 */
	public function __construct(
		string $appName, IRequest $request, CircleRequest $circleRequest,
		RemoteStreamService $remoteStreamService,
		RemoteDownstreamService $remoteDownstreamService, FederatedUserService $federatedUserService,
		CircleService $circleService, MemberService $memberService, ConfigService $configService
	) {
		parent::__construct($appName, $request);
		$this->circleRequest = $circleRequest;
		$this->remoteStreamService = $remoteStreamService;
		$this->remoteDownstreamService = $remoteDownstreamService;
		$this->federatedUserService = $federatedUserService;
		$this->circleService = $circleService;
		$this->memberService = $memberService;
		$this->configService = $configService;

		$this->setup('app', 'circles');
	}


	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return DataResponse
	 * @throws NotLoggedInException
	 * @throws SignatoryException
	 */
	public function appService(): DataResponse {
		try {
			$this->publicPageJsonLimited();
		} catch (JsonNotRequestedException $e) {
			return new DataResponse();
		}

		$confirm = $this->request->getParam('auth', '');

		return new DataResponse($this->remoteStreamService->getAppSignatory(false, $confirm));
	}


	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return DataResponse
	 */
	public function event(): DataResponse {
		try {
			$event = $this->extractEventFromRequest();
		} catch (Exception $e) {
			return $this->eventResponse($e, null, Http::STATUS_UNAUTHORIZED);
		}

		try {
			$this->remoteDownstreamService->requestedEvent($event);
		} catch (FederatedEventException $e) {
			return $this->eventResponse($e, $event, Http::STATUS_INTERNAL_SERVER_ERROR);
		} catch (FederatedItemException $e) {
			return $this->eventResponse($e, $event, $e->getStatus());
		} catch (Exception $e) {
			return $this->eventResponse($e, $event, Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse($event->getOutcome()->jsonSerialize());
	}


	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return DataResponse
	 */
	public function incoming(): DataResponse {
		try {
			$event = $this->extractEventFromRequest();
			$this->remoteDownstreamService->incomingEvent($event);

			return new DataResponse($event->getResult()->jsonSerialize(), Http::STATUS_OK);
		} catch (Exception $e) {
			$this->e($e);

			return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
		}
	}


	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return DataResponse
	 * @throws InvalidOriginException
	 * @throws MalformedArrayException
	 * @throws SignatoryException
	 * @throws SignatureException
	 */
	public function test(): DataResponse {
		$test = $this->remoteStreamService->incomingSignedRequest($this->configService->getFrontalInstance());

		return new DataResponse($test->jsonSerialize());
	}


	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return DataResponse
	 */
	public function circles(): DataResponse {
		try {
			$data = $this->extractDataFromFromRequest();

			/** @var Member $filter */
			$filter = $data->gObj('filter');
			$circles = $this->circleService->getCircles($filter);

			return new DataResponse($circles);
		} catch (Exception $e) {
			$this->e($e);

			return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
		}
	}


	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $circleId
	 *
	 * @return DataResponse
	 */
	public function circle(string $circleId): DataResponse {
		try {
			$this->extractDataFromFromRequest();

			try {
				$circle = $this->circleService->getCircle($circleId);

				return new DataResponse($circle->jsonSerialize());
			} catch (FederatedItemException $e) {
				return $this->eventResponse($e, null, $e->getStatus());
			}

		} catch (Exception $e) {
			return $this->eventResponse($e, null, Http::STATUS_INTERNAL_SERVER_ERROR);
		}

	}


	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $circleId
	 *
	 * @return DataResponse
	 */
	public function members(string $circleId): DataResponse {
		try {
			$this->extractDataFromFromRequest();
			$members = $this->memberService->getMembers($circleId);

			return new DataResponse($members);
		} catch (Exception $e) {
			$this->e($e);

			return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}
	}


	/**
	 * ?? TODO: rename /member/ to /federatedUser/ ou /federated/  ?
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $type
	 * @param string $userId
	 *
	 * @return DataResponse
	 */
	public function member(string $type, string $userId): DataResponse {
		try {
			$this->extractDataFromFromRequest();

			// FILTER CIRCLE BASED ON THE CONFIG/FEDERATED_8192 !!
			if ($type === Member::$DEF_TYPE[Member::TYPE_SINGLE]) {
				$federatedUser = $this->federatedUserService->getFederatedUser($userId, Member::TYPE_SINGLE);
			} else if ($type === Member::$DEF_TYPE[Member::TYPE_CIRCLE]) {
				$federatedUser = $this->federatedUserService->getFederatedUser($userId, Member::TYPE_CIRCLE);
			} else if ($type === Member::$DEF_TYPE[Member::TYPE_USER]) {
				$federatedUser = $this->federatedUserService->getLocalFederatedUser($userId);
			} else {
				throw new FederatedUserNotFoundException();
			}

			return new DataResponse($federatedUser->jsonSerialize());
		} catch (FederatedUserNotFoundException $e) {
			$this->e($e);

			return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
		} catch (Exception $e) {
			$this->e($e);

			return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}
	}


	/**
	 * @return FederatedEvent
	 * @throws InvalidOriginException
	 * @throws MalformedArrayException
	 * @throws SignatoryException
	 * @throws SignatureException
	 * @throws InvalidItemException
	 */
	private function extractEventFromRequest(): FederatedEvent {
		$signed =
			$this->remoteStreamService->incomingSignedRequest($this->configService->getFrontalInstance());
		$this->confirmRemoteInstance($signed);

		$event = new FederatedEvent();
		$event->import(json_decode($signed->getBody(), true));
		$event->setIncomingOrigin($signed->getOrigin());

		return $event;
	}


	/**
	 * @return SimpleDataStore
	 * @throws FederatedUserException
	 * @throws InvalidOriginException
	 * @throws MalformedArrayException
	 * @throws SignatoryException
	 * @throws SignatureException
	 * @throws UnknownTypeException
	 */
	private function extractDataFromFromRequest(): SimpleDataStore {
		$signed =
			$this->remoteStreamService->incomingSignedRequest($this->configService->getFrontalInstance());
		$remoteInstance = $this->confirmRemoteInstance($signed);

		// There should be no need to confirm the need or the origin of the initiator as $remoteInstance
		// already helps filtering request to the database.
		// initiator here is only used to play with the visibility, on top of the visibility provided to
		// the remote instance based on its type.
		$this->federatedUserService->setRemoteInstance($remoteInstance);

		$data = new SimpleDataStore();
		$store = new SimpleDataStore(json_decode($signed->getBody(), true));
		try {
			/** @var FederatedUser $initiator */
			$initiator = $store->gObj('initiator', FederatedUser::class);
			if (!is_null($initiator)) {
				$this->federatedUserService->setCurrentUser($initiator);
			}
		} catch (InvalidItemException $e) {
		}

		try {
			/** @var FederatedUser $initiator */
			$filter = $store->gObj('filter', Member::class);
			if (!is_null($filter)) {
				$data->aObj('filter', $filter);
			}
		} catch (InvalidItemException $e) {
		}

		return $data;
	}


	/**
	 * @param NC21SignedRequest $signedRequest
	 *
	 * @return RemoteInstance
	 * @throws SignatoryException
	 */
	private function confirmRemoteInstance(NC21SignedRequest $signedRequest): RemoteInstance {
		/** @var RemoteInstance $signatory */
		$signatory = $signedRequest->getSignatory();

		if (!$signatory instanceof RemoteInstance) {
			$this->debug('Signatory is not a known RemoteInstance', ['signedRequest' => $signedRequest]);
			throw new SignatoryException('Could not confirm identity');
		}

		if (!$this->configService->isLocalInstance($signedRequest->getOrigin())
			&& $signatory->getType() === RemoteInstance::TYPE_UNKNOWN) {
			$this->debug('Could not confirm identity', ['signedRequest' => $signedRequest]);
			throw new SignatoryException('Could not confirm identity');
		}

		return $signatory;
	}


	/**
	 * @param Exception|null $e
	 * @param FederatedEvent|null $event
	 * @param int $status
	 *
	 * @return DataResponse
	 */
	public function eventResponse(
		Exception $e,
		?FederatedEvent $event = null,
		int $status = Http::STATUS_OK
	): DataResponse {

		$params = [];
		if ($e instanceof FederatedItemException) {
			$params = array_merge($e->getParams(), ['_exception' => $e]);
		}

		if (!is_null($event)) {
			$event->setReadingOutcome(
				$e->getMessage(),
				$params
			);

			$this->e($e, ['event' => $event]);

			return new DataResponse($event->getReadingOutcome()->jsonSerialize(), $status);
		}

		$this->e($e);

		return new DataResponse(array_filter(['message' => $e->getMessage(), 'params' => $params]), $status);
	}


}

