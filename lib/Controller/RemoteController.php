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
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Model\Circle;
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
		$this->setupArray('enforceSignatureHeaders', ['digest', 'content-length']);
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
			return $this->exceptionResponse($e, Http::STATUS_UNAUTHORIZED);
		}

		try {
			$this->remoteDownstreamService->requestedEvent($event);

			return new DataResponse($event->getOutcome());
		} catch (Exception $e) {
			return $this->exceptionResponse($e);
		}
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
		} catch (Exception $e) {
			$this->e($e);

			return $this->exceptionResponse($e, Http::STATUS_UNAUTHORIZED);
		}

		try {
			$this->remoteDownstreamService->incomingEvent($event);

			return new DataResponse($event->getResult()->jsonSerialize());
		} catch (Exception $e) {
			return $this->exceptionResponse($e);
		}
	}


	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return DataResponse
	 */
	public function test(): DataResponse {
		try {
			$test =
				$this->remoteStreamService->incomingSignedRequest($this->configService->getFrontalInstance());

			return new DataResponse($test->jsonSerialize());
		} catch (Exception $e) {
			return $this->exceptionResponse($e, Http::STATUS_UNAUTHORIZED);
		}
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
		} catch (Exception $e) {
			return $this->exceptionResponse($e, Http::STATUS_UNAUTHORIZED);
		}

		try {
			/** @var Circle $filterCircle */
			$filterCircle = $data->gObj('filterCircle');
			/** @var Member $filterMember */
			$filterMember = $data->gObj('filterMember');
			$circles = $this->circleService->getCircles($filterCircle, $filterMember);

			return new DataResponse($circles);
		} catch (Exception $e) {
			return $this->exceptionResponse($e);
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
		} catch (Exception $e) {
			return $this->exceptionResponse($e, Http::STATUS_UNAUTHORIZED);
		}

		try {
			$circle = $this->circleService->getCircle($circleId);

			return new DataResponse($circle->jsonSerialize());
		} catch (Exception $e) {
			return $this->exceptionResponse($e);
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
		} catch (Exception $e) {
			return $this->exceptionResponse($e, Http::STATUS_UNAUTHORIZED);
		}

		try {
			$members = $this->memberService->getMembers($circleId);

			return new DataResponse($members);
		} catch (Exception $e) {
			return $this->exceptionResponse($e);
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
		} catch (Exception $e) {
			$this->e($e);

			return $this->exceptionResponse($e, Http::STATUS_UNAUTHORIZED);
		}

		try {
			// FILTER CIRCLE BASED ON THE CONFIG/FEDERATED_8192 !!
			if ($type === Member::$TYPE[Member::TYPE_SINGLE]) {
				$federatedUser = $this->federatedUserService->getFederatedUser($userId, Member::TYPE_SINGLE);
			} else if ($type === Member::$TYPE[Member::TYPE_CIRCLE]) {
				$federatedUser = $this->federatedUserService->getFederatedUser($userId, Member::TYPE_CIRCLE);
			} else if ($type === Member::$TYPE[Member::TYPE_USER]) {
				$federatedUser = $this->federatedUserService->getLocalFederatedUser($userId);
			} else {
				throw new FederatedUserNotFoundException('Entity not found');
			}

			return new DataResponse($federatedUser->jsonSerialize());
		} catch (Exception $e) {
			return $this->exceptionResponse($e);
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
			$filterMember = $store->gObj('filterMember', Member::class);
			if (!is_null($filterMember)) {
				$data->aObj('filterMember', $filterMember);
			}
		} catch (InvalidItemException $e) {
		}

		try {
			/** @var FederatedUser $initiator */
			$filterCircle = $store->gObj('filterCircle', Circle::class);
			if (!is_null($filterCircle)) {
				$data->aObj('filterCircle', $filterCircle);
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
	 * @param Exception $e
	 * @param int $httpErrorCode
	 *
	 * @return DataResponse
	 */
	public function exceptionResponse(Exception $e, $httpErrorCode = Http::STATUS_BAD_REQUEST): DataResponse {
		if ($e instanceof FederatedItemException) {
			return new DataResponse($e->jsonSerialize(), $e->getStatus());
		}

		return new DataResponse(
			[
				'message' => $e->getMessage(),
				'code'    => $e->getCode()
			],
			($e->getCode() > 0) ? $e->getCode() : $httpErrorCode
		);
	}

}

