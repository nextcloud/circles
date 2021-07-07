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

use ArtificialOwl\MySmallPhpTools\Exceptions\InvalidItemException;
use ArtificialOwl\MySmallPhpTools\Exceptions\InvalidOriginException;
use ArtificialOwl\MySmallPhpTools\Exceptions\JsonNotRequestedException;
use ArtificialOwl\MySmallPhpTools\Exceptions\MalformedArrayException;
use ArtificialOwl\MySmallPhpTools\Exceptions\SignatoryException;
use ArtificialOwl\MySmallPhpTools\Exceptions\SignatureException;
use ArtificialOwl\MySmallPhpTools\Exceptions\UnknownTypeException;
use ArtificialOwl\MySmallPhpTools\Model\Nextcloud\nc22\NC22SignedRequest;
use ArtificialOwl\MySmallPhpTools\Model\SimpleDataStore;
use ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc22\TNC22Controller;
use ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc22\TNC22Deserialize;
use ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc22\TNC22LocalSignatory;
use Exception;
use OC\AppFramework\Middleware\Security\Exceptions\NotLoggedInException;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\UnknownInterfaceException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\InterfaceService;
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
	use TNC22Controller;
	use TNC22LocalSignatory;
	use TNC22Deserialize;


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

	/** @var InterfaceService */
	private $interfaceService;

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
	 * @param InterfaceService $interfaceService
	 * @param ConfigService $configService
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		CircleRequest $circleRequest,
		RemoteStreamService $remoteStreamService,
		RemoteDownstreamService $remoteDownstreamService,
		FederatedUserService $federatedUserService,
		CircleService $circleService,
		MemberService $memberService,
		InterfaceService $interfaceService,
		ConfigService $configService
	) {
		parent::__construct($appName, $request);
		$this->circleRequest = $circleRequest;
		$this->remoteStreamService = $remoteStreamService;
		$this->remoteDownstreamService = $remoteDownstreamService;
		$this->federatedUserService = $federatedUserService;
		$this->circleService = $circleService;
		$this->memberService = $memberService;
		$this->interfaceService = $interfaceService;
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
	 * @throws UnknownInterfaceException
	 */
	public function appService(): DataResponse {
		try {
			$this->publicPageJsonLimited();
		} catch (JsonNotRequestedException $e) {
			return new DataResponse();
		}

		$this->interfaceService->setCurrentInterfaceFromRequest($this->request);
		$signatory = $this->remoteStreamService->getAppSignatory(false, $this->request->getParam('auth', ''));

		return new DataResponse($signatory);
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
			$this->e($e, ['event' => $event]);

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

			return new DataResponse($this->serialize($event->getResult()));
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
			$this->interfaceService->setCurrentInterfaceFromRequest($this->request);
			$test = $this->remoteStreamService->incomingSignedRequest();

			return new DataResponse($this->serialize($test));
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

			return new DataResponse($this->serialize($circle));
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
			} elseif ($type === Member::$TYPE[Member::TYPE_CIRCLE]) {
				$federatedUser = $this->federatedUserService->getFederatedUser($userId, Member::TYPE_CIRCLE);
			} elseif ($type === Member::$TYPE[Member::TYPE_USER]) {
				$federatedUser = $this->federatedUserService->getLocalFederatedUser($userId);
			} else {
				throw new FederatedUserNotFoundException('Entity not found');
			}

			return new DataResponse($this->serialize($federatedUser));
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
	 * @throws UnknownInterfaceException
	 */
	private function extractEventFromRequest(): FederatedEvent {
		$signed = $this->remoteStreamService->incomingSignedRequest();
		$this->confirmRemoteInstance($signed);

		$event = new FederatedEvent();
		$event->import(json_decode($signed->getBody(), true));
		$event->setSender($signed->getOrigin());

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
		$signed = $this->remoteStreamService->incomingSignedRequest();
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
	 * @param NC22SignedRequest $signedRequest
	 *
	 * @return RemoteInstance
	 * @throws SignatoryException
	 */
	private function confirmRemoteInstance(NC22SignedRequest $signedRequest): RemoteInstance {
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

		$this->interfaceService->setCurrentInterface($signatory->getInterface());

		return $signatory;
	}


	/**
	 * @param Exception $e
	 * @param int $httpErrorCode
	 *
	 * @return DataResponse
	 */
	public function exceptionResponse(
		Exception $e,
		int $httpErrorCode = Http::STATUS_BAD_REQUEST
	): DataResponse {
		if ($e instanceof FederatedItemException) {
			return new DataResponse($this->serialize($e), $e->getStatus());
		}

		return new DataResponse(
			[
				'message' => $e->getMessage(),
				'code' => $e->getCode()
			],
			($e->getCode() > 0) ? $e->getCode() : $httpErrorCode
		);
	}
}
