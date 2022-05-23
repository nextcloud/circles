<?php
/*
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2022
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


namespace OCA\Circles\Service;

use Exception;
use OC\AppFramework\Middleware\Security\Exceptions\NotLoggedInException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\JsonNotRequestedException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Exceptions\InvalidOriginException;
use OCA\Circles\Tools\Exceptions\ItemNotFoundException;
use OCA\Circles\Tools\Exceptions\MalformedArrayException;
use OCA\Circles\Tools\Exceptions\SignatoryException;
use OCA\Circles\Tools\Exceptions\SignatureException;
use OCA\Circles\Tools\Exceptions\UnknownTypeException;
use OCA\Circles\Tools\IDeserializable;
use OCA\Circles\Tools\Model\NCSignedRequest;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCA\Circles\Tools\Traits\TNCLocalSignatory;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IUserSession;

class SignedControllerService {
	use TNCLocalSignatory;
	use TDeserialize;


	protected RemoteStreamService $remoteStreamService;
	protected InterfaceService $interfaceService;
	protected FederatedUserService $federatedUserService;
	protected ConfigService $configService;


	public function __construct(
		IUserSession $userSession,
		FederatedUserService $federatedUserService,
		InterfaceService $interfaceService,
		RemoteStreamService $remoteStreamService,
		ConfigService $configService
	) {

		$this->remoteStreamService = $remoteStreamService;
		$this->interfaceService = $interfaceService;
		$this->federatedUserService = $federatedUserService;
		$this->configService = $configService;
	}


	/**
	 * @return FederatedEvent
	 * @throws InvalidOriginException
	 * @throws MalformedArrayException
	 * @throws SignatoryException
	 * @throws SignatureException
	 */
	public function extractEventFromRequest(): FederatedEvent {
		/** @var NCSignedRequest $signed */
		$event = $this->extractObjectFromRequest(FederatedEvent::class, $signed);
		$event->setSender($signed->getOrigin());

		return $event;
	}

	/**
	 * @param string $class
	 * @param NCSignedRequest|null $signed
	 *
	 * @return FederatedEvent
	 * @throws InvalidOriginException
	 * @throws MalformedArrayException
	 * @throws SignatoryException
	 * @throws SignatureException
	 */
	public function extractObjectFromRequest(
		string $class,
		?NCSignedRequest &$signed = null
	): IDeserializable {
		$signed = $this->remoteStreamService->incomingSignedRequest();
		$this->confirmRemoteInstance($signed);

		$obj = new $class();
		$obj->import(json_decode($signed->getBody(), true));

		return $obj;
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
	public function extractDataFromFromRequest(): SimpleDataStore {
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
			$this->federatedUserService->setCurrentUser($initiator);
		} catch (InvalidItemException | ItemNotFoundException $e) {
		}

		try {
			/** @var FederatedUser $initiator */
			$filterMember = $store->gObj('filterMember', Member::class);
			$data->aObj('filterMember', $filterMember);
		} catch (InvalidItemException | ItemNotFoundException $e) {
		}

		try {
			/** @var FederatedUser $initiator */
			$filterCircle = $store->gObj('filterCircle', Circle::class);
			$data->aObj('filterCircle', $filterCircle);
		} catch (InvalidItemException | ItemNotFoundException $e) {
		}

		return $data;
	}


	/**
	 * @param NCSignedRequest $signedRequest
	 *
	 * @return RemoteInstance
	 * @throws SignatoryException
	 */
	private function confirmRemoteInstance(NCSignedRequest $signedRequest): RemoteInstance {
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
		$accepts = array_map([$this, 'trimHeader'], explode(',', $this->request->getHeader('Accept')));

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
