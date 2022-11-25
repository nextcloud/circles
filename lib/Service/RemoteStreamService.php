<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
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

use JsonSerializable;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\RemoteRequest;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\RemoteAlreadyExistsException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\RemoteUidException;
use OCA\Circles\Exceptions\UnknownInterfaceException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Tools\ActivityPub\NCSignature;
use OCA\Circles\Tools\Exceptions\RequestNetworkException;
use OCA\Circles\Tools\Exceptions\SignatoryException;
use OCA\Circles\Tools\Exceptions\SignatureException;
use OCA\Circles\Tools\Exceptions\WellKnownLinkNotFoundException;
use OCA\Circles\Tools\Model\NCRequest;
use OCA\Circles\Tools\Model\NCRequestResult;
use OCA\Circles\Tools\Model\NCSignatory;
use OCA\Circles\Tools\Model\NCSignedRequest;
use OCA\Circles\Tools\Model\Request;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCA\Circles\Tools\Traits\TNCLocalSignatory;
use OCA\Circles\Tools\Traits\TNCWellKnown;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\AppFramework\Http;
use OCP\IURLGenerator;
use ReflectionClass;
use ReflectionException;

/**
 * Class RemoteStreamService
 *
 * @package OCA\Circles\Service
 */
class RemoteStreamService extends NCSignature {
	use TDeserialize;
	use TNCLocalSignatory;
	use TStringTools;
	use TNCWellKnown;


	public const UPDATE_DATA = 'data';
	public const UPDATE_ITEM = 'item';
	public const UPDATE_TYPE = 'type';
	public const UPDATE_INSTANCE = 'instance';
	public const UPDATE_HREF = 'href';


	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var RemoteRequest */
	private $remoteRequest;

	/** @var InterfaceService */
	private $interfaceService;

	/** @var ConfigService */
	private $configService;


	/**
	 * RemoteStreamService constructor.
	 *
	 * @param IURLGenerator $urlGenerator
	 * @param RemoteRequest $remoteRequest
	 * @param InterfaceService $interfaceService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IURLGenerator $urlGenerator,
		RemoteRequest $remoteRequest,
		InterfaceService $interfaceService,
		ConfigService $configService
	) {
		$this->setup('app', 'circles');

		$this->urlGenerator = $urlGenerator;
		$this->remoteRequest = $remoteRequest;
		$this->interfaceService = $interfaceService;
		$this->configService = $configService;
	}


	/**
	 * Returns the Signatory model for the Circles app.
	 * Can be signed with a confirmKey.
	 *
	 * @param bool $generate
	 * @param string $confirmKey
	 *
	 * @return RemoteInstance
	 * @throws SignatoryException
	 * @throws UnknownInterfaceException
	 */
	public function getAppSignatory(bool $generate = true, string $confirmKey = ''): RemoteInstance {
		$app = new RemoteInstance($this->interfaceService->getCloudPath('circles.Remote.appService'));
		$this->fillSimpleSignatory($app, $generate);
		$app->setUidFromKey();

		if ($this->isUuid($confirmKey)) {
			$app->setAuthSigned($this->signString($confirmKey, $app));
		}

		$app->setRoot($this->interfaceService->getCloudPath());
		$app->setEvent($this->interfaceService->getCloudPath('circles.Remote.event'));
		$app->setIncoming($this->interfaceService->getCloudPath('circles.Remote.incoming'));
		$app->setTest($this->interfaceService->getCloudPath('circles.Remote.test'));
		$app->setCircles($this->interfaceService->getCloudPath('circles.Remote.circles'));
		$app->setCircle(
			urldecode(
				$this->interfaceService->getCloudPath('circles.Remote.circle', ['circleId' => '{circleId}'])
			)
		);
		$app->setMembers(
			urldecode(
				$this->interfaceService->getCloudPath('circles.Remote.members', ['circleId' => '{circleId}'])
			)
		);
		$app->setMember(
			urldecode(
				$this->interfaceService->getCloudPath(
					'circles.Remote.member',
					['type' => '{type}', 'userId' => '{userId}']
				)
			)
		);
		$app->setInherited(
			urldecode(
				$this->interfaceService->getCloudPath(
					'circles.Remote.inherited',
					['circleId' => '{circleId}']
				)
			)
		);
		$app->setMemberships(
			urldecode(
				$this->interfaceService->getCloudPath(
					'circles.Remote.memberships',
					['circleId' => '{circleId}']
				)
			)
		);

		if ($this->interfaceService->isCurrentInterfaceInternal()) {
			$app->setAliases(array_values(array_filter($this->interfaceService->getInterfaces(false))));
		}

		$app->setOrigData($this->serialize($app));

		return $app;
	}


	/**
	 * Reset the Signatory (and the Identity) for the Circles app.
	 */
	public function resetAppSignatory(): void {
		try {
			$app = $this->getAppSignatory();

			$this->removeSimpleSignatory($app);
		} catch (SignatoryException $e) {
		}
	}


	/**
	 * shortcut to requestRemoteInstance that return result if available, or exception.
	 *
	 * @param string $instance
	 * @param string $item
	 * @param int $type
	 * @param JsonSerializable|null $object
	 * @param array $params
	 *
	 * @return array
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws FederatedItemException
	 */
	public function resultRequestRemoteInstance(
		string $instance,
		string $item,
		int $type = Request::TYPE_GET,
		?JsonSerializable $object = null,
		array $params = []
	): array {
		$this->interfaceService->setCurrentInterfaceFromInstance($instance);

		$signedRequest = $this->requestRemoteInstance($instance, $item, $type, $object, $params);
		if (!$signedRequest->getOutgoingRequest()->hasResult()) {
			throw new RemoteInstanceException();
		}

		$result = $signedRequest->getOutgoingRequest()->getResult();

		if ($result->getStatusCode() === Http::STATUS_OK) {
			return $result->getAsArray();
		}

		throw $this->getFederatedItemExceptionFromResult($result);
	}


	/**
	 * Send a request to a remote instance, based on:
	 * - instance: address as saved in database,
	 * - item: the item to request (incoming, event, ...)
	 * - type: GET, POST
	 * - data: Serializable to be send if needed
	 *
	 * @param string $instance
	 * @param string $item
	 * @param int $type
	 * @param JsonSerializable|null $object
	 * @param array $params
	 *
	 * @return NCSignedRequest
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws RemoteInstanceException
	 * @throws UnknownInterfaceException
	 */
	private function requestRemoteInstance(
		string $instance,
		string $item,
		int $type = Request::TYPE_GET,
		?JsonSerializable $object = null,
		array $params = []
	): NCSignedRequest {
		$request = new NCRequest('', $type);
		$this->configService->configureRequest($request);
		$link = $this->getRemoteInstanceEntry($instance, $item, $params);
		$request->basedOnUrl($link);

		// TODO: Work Around: on local, if object is empty, request takes 10s. check on other configuration
		if (is_null($object) || empty($object->jsonSerialize())) {
			$object = new SimpleDataStore(['empty' => 1]);
		}

		if (!is_null($object)) {
			$request->setDataSerialize($object);
		}

		try {
			$app = $this->getAppSignatory();
//		$app->setAlgorithm(NCSignatory::SHA512);
			$signedRequest = $this->signOutgoingRequest($request, $app);
			$this->doRequest($signedRequest->getOutgoingRequest(), false);
		} catch (RequestNetworkException | SignatoryException $e) {
			throw new RemoteInstanceException($e->getMessage());
		}

		return $signedRequest;
	}


	/**
	 * get the value of an entry from the Signatory of the RemoteInstance.
	 *
	 * @param string $instance
	 * @param string $item
	 * @param array $params
	 *
	 * @return string
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 */
	private function getRemoteInstanceEntry(string $instance, string $item, array $params = []): string {
		$remote = $this->getCachedRemoteInstance($instance);

		$value = $this->get($item, $remote->getOrigData());
		if ($value === '') {
			throw new RemoteResourceNotFoundException();
		}

		return $this->feedStringWithParams($value, $params);
	}


	/**
	 * get RemoteInstance with confirmed and known identity from database.
	 *
	 * @param string $instance
	 *
	 * @return RemoteInstance
	 * @throws RemoteNotFoundException
	 * @throws UnknownRemoteException
	 */
	public function getCachedRemoteInstance(string $instance): RemoteInstance {
		$remoteInstance = $this->remoteRequest->getFromInstance($instance);
		if ($remoteInstance->getType() === RemoteInstance::TYPE_UNKNOWN) {
			throw new UnknownRemoteException($instance . ' is set as \'unknown\' in database');
		}

		return $remoteInstance;
	}


	/**
	 * Add a remote instance, based on the address
	 *
	 * @param string $instance
	 *
	 * @return RemoteInstance
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws SignatureException
	 * @throws WellKnownLinkNotFoundException
	 */
	public function retrieveRemoteInstance(string $instance): RemoteInstance {
		$resource = $this->getResourceData($instance, Application::APP_SUBJECT, Application::APP_REL);

		/** @var RemoteInstance $remoteInstance */
		$remoteInstance = $this->retrieveSignatory($resource->g('id'), true);
		$remoteInstance->setInstance($instance);

		return $remoteInstance;
	}


	/**
	 * retrieve Signatory.
	 *
	 * @param string $keyId
	 * @param bool $refresh
	 *
	 * @return RemoteInstance
	 * @throws SignatoryException
	 * @throws SignatureException
	 */
	public function retrieveSignatory(string $keyId, bool $refresh = true): NCSignatory {
		if (!$refresh) {
			try {
				return $this->remoteRequest->getFromHref(NCSignatory::removeFragment($keyId));
			} catch (RemoteNotFoundException $e) {
				throw new SignatoryException();
			}
		}

		$remoteInstance = new RemoteInstance($keyId);
		$confirm = $this->uuid();

		$request = new NCRequest();
		$this->configService->configureRequest($request);

		$this->downloadSignatory($remoteInstance, $keyId, ['auth' => $confirm], $request);
		$remoteInstance->setUidFromKey();

		$this->confirmAuth($remoteInstance, $confirm);

		return $remoteInstance;
	}


	/**
	 * Add a remote instance, based on the address
	 *
	 * @param string $instance
	 * @param string $type
	 * @param int $iface
	 * @param bool $overwrite
	 *
	 * @throws RemoteAlreadyExistsException
	 * @throws RemoteUidException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws SignatureException
	 * @throws WellKnownLinkNotFoundException
	 */
	public function addRemoteInstance(
		string $instance,
		string $type = RemoteInstance::TYPE_EXTERNAL,
		int $iface = InterfaceService::IFACE_FRONTAL,
		bool $overwrite = false
	): void {
		if ($this->configService->isLocalInstance($instance)) {
			throw new RemoteAlreadyExistsException('instance is local');
		}

		$remoteInstance = $this->retrieveRemoteInstance($instance);
		$remoteInstance->setType($type)
					   ->setInterface($iface);

		if (!$this->interfaceService->isInterfaceInternal($remoteInstance->getInterface())) {
			$remoteInstance->setAliases([]);
		}

		try {
			$known = $this->remoteRequest->searchDuplicate($remoteInstance);
			if ($overwrite) {
				$this->remoteRequest->deleteById($known);
			} else {
				throw new RemoteAlreadyExistsException('instance is already known');
			}
		} catch (RemoteNotFoundException $e) {
		}

		$this->remoteRequest->save($remoteInstance);
	}


	/**
	 * @param string $address
	 *
	 * @return RemoteInstance
	 * @throws RemoteNotFoundException
	 */
	public function getRemoteInstanceFromAddress(string $address): RemoteInstance {
		$remotes = $this->remoteRequest->getAllInstances();
		foreach ($remotes as $remote) {
			if ($remote->getInstance() === $address || in_array($address, $remote->getAliases())) {
				return $remote;
			}
		}

		throw new RemoteNotFoundException();
	}


	/**
	 * @param string $instance
	 * @param string $check
	 *
	 * @return bool
	 * @throws RemoteNotFoundException
	 */
	public function isFromSameInstance(string $instance, string $check): bool {
		$remote = $this->getRemoteInstanceFromAddress($instance);
		if ($remote->getInstance() === $check || in_array($check, $remote->getAliases())) {
			return true;
		}

		return false;
	}


	/**
	 * Confirm the Auth of a RemoteInstance, based on the result from a request
	 *
	 * @param RemoteInstance $remote
	 * @param string $auth
	 *
	 * @throws SignatureException
	 */
	private function confirmAuth(RemoteInstance $remote, string $auth): void {
		[$algo, $signed] = explode(':', $this->get(RemoteInstance::AUTH_SIGNED, $remote->getOrigData()));
		try {
			if ($signed === null) {
				throw new SignatureException('invalid auth-signed');
			}
			$this->verifyString($auth, base64_decode($signed), $remote->getPublicKey(), $algo);
			$remote->setIdentityAuthed(true);
		} catch (SignatureException $e) {
			$this->e($e, [
				'auth' => $auth,
				'signed' => $signed,
				'msg' => 'auth not confirmed'
			]
			);
			throw new SignatureException('auth not confirmed');
		}
	}


	/**
	 * @param NCRequestResult $result
	 *
	 * @return FederatedItemException
	 */
	private function getFederatedItemExceptionFromResult(NCRequestResult $result): FederatedItemException {
		$data = $result->getAsArray();

		$message = $this->get('message', $data);
		$code = $this->getInt('code', $data);
		$class = $this->get('class', $data);

		try {
			$test = new ReflectionClass($class);
			$this->confirmFederatedItemExceptionFromClass($test);
			$e = $class;
		} catch (ReflectionException | FederatedItemException $_e) {
			$e = $this->getFederatedItemExceptionFromStatus($result->getStatusCode());
		}

		return new $e($message, $code);
	}


	/**
	 * @param ReflectionClass $class
	 *
	 * @return void
	 * @throws FederatedItemException
	 */
	private function confirmFederatedItemExceptionFromClass(ReflectionClass $class): void {
		while (true) {
			foreach (FederatedItemException::$CHILDREN as $e) {
				if ($class->getName() === $e) {
					return;
				}
			}
			$class = $class->getParentClass();
			if (!$class) {
				throw new FederatedItemException();
			}
		}
	}


	/**
	 * @param int $statusCode
	 *
	 * @return string
	 */
	private function getFederatedItemExceptionFromStatus(int $statusCode): string {
		foreach (FederatedItemException::$CHILDREN as $e) {
			if ($e::STATUS === $statusCode) {
				return $e;
			}
		}

		return FederatedItemException::class;
	}


	/**
	 * TODO: confirm if method is really needed
	 *
	 * @param RemoteInstance $remote
	 * @param RemoteInstance|null $stored
	 *
	 * @throws RemoteNotFoundException
	 * @throws RemoteUidException
	 */
	public function confirmValidRemote(RemoteInstance $remote, ?RemoteInstance &$stored = null): void {
		try {
			$stored = $this->remoteRequest->getFromHref($remote->getId());
		} catch (RemoteNotFoundException $e) {
			if ($remote->getInstance() === '') {
				throw new RemoteNotFoundException();
			}

			$stored = $this->remoteRequest->getFromInstance($remote->getInstance());
		}

		if ($stored->getUid() !== $remote->getUid(true)) {
			throw new RemoteUidException();
		}
	}


	/**
	 * TODO: check if this method is not useless
	 *
	 * @param RemoteInstance $remote
	 * @param string $update
	 *
	 * @throws RemoteUidException
	 */
	public function update(RemoteInstance $remote, string $update = self::UPDATE_DATA): void {
		if (!$this->interfaceService->isInterfaceInternal($remote->getInterface())) {
			$remote->setAliases([]);
		}

		switch ($update) {
			case self::UPDATE_DATA:
				$this->remoteRequest->update($remote);
				break;

			case self::UPDATE_ITEM:
				$this->remoteRequest->updateItem($remote);
				break;

			case self::UPDATE_TYPE:
				$this->remoteRequest->updateType($remote);
				break;

			case self::UPDATE_HREF:
				$this->remoteRequest->updateHref($remote);
				break;

			case self::UPDATE_INSTANCE:
				$this->remoteRequest->updateInstance($remote);
				break;
		}
	}
}
