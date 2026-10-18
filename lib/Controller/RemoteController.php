<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Controller;

use Exception;
use OC\AppFramework\Middleware\Security\Exceptions\NotLoggedInException;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\JsonNotRequestedException;
use OCA\Circles\Exceptions\UnknownInterfaceException;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Service\InterfaceService;
use OCA\Circles\Service\MembershipService;
use OCA\Circles\Service\RemoteEndpointService;
use OCA\Circles\Service\RemoteStreamService;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Exceptions\InvalidOriginException;
use OCA\Circles\Tools\Exceptions\InvalidRemoteInstanceException;
use OCA\Circles\Tools\Exceptions\MalformedArrayException;
use OCA\Circles\Tools\Exceptions\SignatoryException;
use OCA\Circles\Tools\Exceptions\SignatureException;
use OCA\Circles\Tools\Exceptions\UnknownTypeException;
use OCA\Circles\Tools\Model\NCSignedRequest;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCA\Circles\Tools\Traits\TNCLocalSignatory;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Class RemoteController
 *
 * @package OCA\Circles\Controller
 */
class RemoteController extends Controller {
	use TNCLocalSignatory;
	use TDeserialize;

	public function __construct(
		string $appName,
		IRequest $request,
		private readonly CircleRequest $circleRequest,
		private readonly RemoteStreamService $remoteStreamService,
		private readonly RemoteEndpointService $remoteEndpointService,
		private readonly MembershipService $membershipService,
		private readonly InterfaceService $interfaceService,
		private readonly IUserSession $userSession,
	) {
		parent::__construct($appName, $request);

		$this->setup('app', 'circles');
		$this->setupArray('enforceSignatureHeaders', ['digest', 'content-length']);
	}

	/**
	 *
	 * @param string $test
	 *
	 * @return DataResponse
	 * @throws NotLoggedInException
	 * @throws SignatoryException
	 * @throws UnknownInterfaceException
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function appService(string $test = ''): DataResponse {
		try {
			$this->publicPageJsonLimited();
		} catch (JsonNotRequestedException) {
			return new DataResponse();
		}

		$this->interfaceService->setCurrentInterfaceFromRequest($this->request, $test);
		$signatory = $this->remoteStreamService->getAppSignatory(false, $this->request->getParam('auth', ''));

		return new DataResponse($signatory);
	}

	/**
	 *
	 * @return DataResponse
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function event(): DataResponse {
		try {
			$event = $this->extractEventFromRequest();
		} catch (Exception $e) {
			return $this->remoteEndpointService->exceptionResponse($e, Http::STATUS_UNAUTHORIZED);
		}

		try {
			return new DataResponse($this->remoteEndpointService->requestedEvent($event));
		} catch (Exception $e) {
			$this->e($e, ['event' => $event->getWrapperToken()]);

			return $this->remoteEndpointService->exceptionResponse($e);
		}
	}

	/**
	 *
	 * @return DataResponse
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function incoming(): DataResponse {
		try {
			$event = $this->extractEventFromRequest();
		} catch (Exception $e) {
			$this->e($e);

			return $this->remoteEndpointService->exceptionResponse($e, Http::STATUS_UNAUTHORIZED);
		}

		try {
			return new DataResponse($this->remoteEndpointService->incomingEvent($event));
		} catch (Exception $e) {
			return $this->remoteEndpointService->exceptionResponse($e);
		}
	}

	/**
	 *
	 * @return DataResponse
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function test(): DataResponse {
		try {
			$this->interfaceService->setCurrentInterfaceFromRequest($this->request);
			$test = $this->remoteStreamService->incomingSignedRequest();

			return new DataResponse($this->serialize($test));
		} catch (Exception $e) {
			$this->e($e);

			return $this->remoteEndpointService->exceptionResponse($e, Http::STATUS_UNAUTHORIZED);
		}
	}

	/**
	 *
	 * @return DataResponse
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function circles(): DataResponse {
		try {
			$data = $this->extractDataFromFromRequest();
		} catch (Exception $e) {
			return $this->remoteEndpointService->exceptionResponse($e, Http::STATUS_UNAUTHORIZED);
		}

		try {
			return new DataResponse($this->remoteEndpointService->getCircles($data));
		} catch (Exception $e) {
			return $this->remoteEndpointService->exceptionResponse($e);
		}
	}

	/**
	 *
	 * @param string $circleId
	 *
	 * @return DataResponse
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function circle(string $circleId): DataResponse {
		try {
			$this->extractDataFromFromRequest();
		} catch (Exception $e) {
			return $this->remoteEndpointService->exceptionResponse($e, Http::STATUS_UNAUTHORIZED);
		}

		try {
			return new DataResponse($this->remoteEndpointService->getCircle($circleId));
		} catch (Exception $e) {
			return $this->remoteEndpointService->exceptionResponse($e);
		}
	}

	/**
	 *
	 * @param string $circleId
	 *
	 * @return DataResponse
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function members(string $circleId): DataResponse {
		try {
			$this->extractDataFromFromRequest();
		} catch (Exception $e) {
			return $this->remoteEndpointService->exceptionResponse($e, Http::STATUS_UNAUTHORIZED);
		}

		try {
			return new DataResponse($this->remoteEndpointService->getMembers($circleId));
		} catch (Exception $e) {
			return $this->remoteEndpointService->exceptionResponse($e);
		}
	}

	/**
	 * ?? TODO: rename /member/ to /federatedUser/ ou /federated/  ?
	 *
	 *
	 * @param string $type
	 * @param string $userId
	 *
	 * @return DataResponse
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function member(string $type, string $userId): DataResponse {
		try {
			$this->extractDataFromFromRequest();
		} catch (Exception $e) {
			$this->e($e);

			return $this->remoteEndpointService->exceptionResponse($e, Http::STATUS_UNAUTHORIZED);
		}

		try {
			return new DataResponse($this->remoteEndpointService->getFederatedUser($type, $userId));
		} catch (Exception $e) {
			return $this->remoteEndpointService->exceptionResponse($e);
		}
	}

	/**
	 *
	 * @param string $circleId
	 *
	 * @return DataResponse
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function inherited(string $circleId): DataResponse {
		try {
			$this->extractDataFromFromRequest();
		} catch (Exception $e) {
			return $this->remoteEndpointService->exceptionResponse($e, Http::STATUS_UNAUTHORIZED);
		}

		try {
			return new DataResponse($this->remoteEndpointService->getInheritedMembers($circleId));
		} catch (Exception $e) {
			return $this->remoteEndpointService->exceptionResponse($e);
		}
	}

	/**
	 *
	 * @param string $circleId
	 *
	 * @return DataResponse
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function memberships(string $circleId): DataResponse {
		try {
			$this->extractDataFromFromRequest();
		} catch (Exception $e) {
			return $this->remoteEndpointService->exceptionResponse($e, Http::STATUS_UNAUTHORIZED);
		}

		try {
			return new DataResponse($this->remoteEndpointService->getMemberships($circleId));
		} catch (Exception $e) {
			return $this->remoteEndpointService->exceptionResponse($e);
		}
	}

	/**
	 * @return FederatedEvent
	 * @throws InvalidItemException
	 * @throws InvalidOriginException
	 * @throws MalformedArrayException
	 * @throws SignatoryException
	 * @throws SignatureException
	 * @throws UnknownInterfaceException
	 * @throws FederatedEventException
	 */
	private function extractEventFromRequest(): FederatedEvent {
		// will throw exception if instance is not configured for this event.
		$this->remoteEndpointService->confirmEventInterface($this->request);

		$signed = $this->remoteStreamService->incomingSignedRequest();
		$this->confirmRemoteInstance($signed);

		return $this->remoteEndpointService->importEvent(json_decode($signed->getBody(), true), $signed->getOrigin());
	}

	/**
	 * @return SimpleDataStore
	 * @throws FederatedUserException
	 * @throws InvalidOriginException
	 * @throws MalformedArrayException
	 * @throws SignatoryException
	 * @throws SignatureException
	 * @throws UnknownTypeException
	 * @throws InvalidRemoteInstanceException
	 */
	private function extractDataFromFromRequest(): SimpleDataStore {
		$signed = $this->remoteStreamService->incomingSignedRequest();
		$remoteInstance = $this->confirmRemoteInstance($signed);

		return $this->remoteEndpointService->prepareDataRequest($remoteInstance, json_decode($signed->getBody(), true));
	}

	/**
	 * @param NCSignedRequest $signedRequest
	 *
	 * @return RemoteInstance
	 * @throws SignatoryException
	 */
	private function confirmRemoteInstance(NCSignedRequest $signedRequest): RemoteInstance {
		$signatory = $signedRequest->getSignatory();

		return $this->remoteEndpointService->confirmRemoteInstance(
			($signatory instanceof RemoteInstance) ? $signatory : null,
			$signedRequest->getOrigin()
		);
	}

	/**
	 * use this one if a method from a Controller is only PublicPage when remote client asking for Json
	 *
	 * try {
	 *      $this->publicPageJsonLimited();
	 *      return new DataResponse(['test' => 42]);
	 * } catch (JsonNotRequestedException $e) {}
	 *
	 *
	 * @throws NotLoggedInException
	 * @throws JsonNotRequestedException
	 */
	private function publicPageJsonLimited(): void {
		if (!$this->jsonRequested()) {
			if (!$this->userSession->isLoggedIn()) {
				throw new NotLoggedInException();
			}

			throw new JsonNotRequestedException();
		}
	}

	/**
	 * @return bool
	 */
	private function jsonRequested(): bool {
		return ($this->areWithinAcceptHeader(
			[
				'application/json',
				'application/ld+json',
				'application/activity+json'
			]
		));
	}

	/**
	 * @param array $needles
	 *
	 * @return bool
	 */
	private function areWithinAcceptHeader(array $needles): bool {
		$accepts = array_map($this->trimHeader(...), explode(',', $this->request->getHeader('Accept')));

		foreach ($accepts as $accept) {
			if (in_array($accept, $needles)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $header
	 *
	 * @return string
	 */
	private function trimHeader(string $header): string {
		$header = trim($header);
		$pos = strpos($header, ';');
		if ($pos === false) {
			return $header;
		}

		return substr($header, 0, $pos);
	}
}
