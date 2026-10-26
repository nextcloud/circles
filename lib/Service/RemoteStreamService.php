<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use Exception;
use JsonSerializable;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\ConfigLexicon;
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
use OCA\Circles\Tools\Model\NCSignatory;
use OCA\Circles\Tools\Model\NCSignedRequest;
use OCA\Circles\Tools\Model\Request;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCA\Circles\Tools\Traits\TNCLocalSignatory;
use OCA\Circles\Tools\Traits\TNCWellKnown;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\AppFramework\Http;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\OCM\Exceptions\OCMProviderException;
use OCP\OCM\IOCMDiscoveryService;
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

	/** Paths of the items served by the OCM endpoint, relative to the Circles OCM capability */
	private const OCM_PATHS = [
		RemoteInstance::EVENT => 'event',
		RemoteInstance::INCOMING => 'incoming',
		RemoteInstance::CIRCLES => 'circles',
		RemoteInstance::CIRCLE => 'circle/{circleId}',
		RemoteInstance::MEMBERS => 'members/{circleId}',
		RemoteInstance::MEMBER => 'member/{type}/{userId}',
		RemoteInstance::INHERITED => 'inherited/{circleId}',
		RemoteInstance::MEMBERSHIPS => 'memberships/{circleId}',
	];

	/**
	 * RemoteStreamService constructor.
	 *
	 * @param IURLGenerator $urlGenerator
	 * @param RemoteRequest $remoteRequest
	 * @param InterfaceService $interfaceService
	 * @param ConfigService $configService
	 */
	public function __construct(
		private readonly IConfig $config,
		private IURLGenerator $urlGenerator,
		private RemoteRequest $remoteRequest,
		private InterfaceService $interfaceService,
		private ConfigService $configService,
		private readonly IAppConfig $appConfig,
		private readonly IOCMDiscoveryService $ocmDiscoveryService,
	) {
		$this->setup('app', 'circles');
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
		// use Server::get() to avoid circular dependency
		$federationAgentService = \OCP\Server::get(FederationAgentService::class);
		$app->setFederationAgentId($federationAgentService->getOrCreateFederationAgentId());

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
		} catch (SignatoryException) {
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
		array $params = [],
	): array {
		$this->interfaceService->setCurrentInterfaceFromInstance($instance);

		// use OCM endpoint when possible, otherwise fall back to circles own federation protocol
		$ocmPath = $this->getOcmPath($instance, $item, $params);
		if ($ocmPath !== null) {
			return $this->resultRequestRemoteOcmEndpoint($instance, $ocmPath, $object);
		}

		$signedRequest = $this->requestRemoteInstance($instance, $item, $type, $object, $params);
		if (!$signedRequest->getOutgoingRequest()->hasResult()) {
			throw new RemoteInstanceException();
		}

		$result = $signedRequest->getOutgoingRequest()->getResult();

		if ($result->getStatusCode() === Http::STATUS_OK) {
			return $result->getAsArray();
		}

		throw $this->getFederatedItemException($result->getStatusCode(), $result->getAsArray());
	}

	/**
	 * Returns the path to request the item on OCM endpoint of the remote instance,
	 * or null if the request should circles own federation protocol instead.
	 */
	private function getOcmPath(string $instance, string $item, array $params): ?string {
		if (!array_key_exists($item, self::OCM_PATHS)
			|| $this->configService->isLocalInstance($instance)
			|| !$this->appConfig->getValueBool(Application::APP_ID, ConfigLexicon::FEDERATED_TEAMS_OCM)
			|| $this->appConfig->getValueBool('core', 'ocm_signed_request_disabled', lazy: true)) {
			return null;
		}

		try {
			$provider = $this->ocmDiscoveryService->discover($this->getCachedRemoteInstance($instance)->getRoot());
		} catch (RemoteNotFoundException|UnknownRemoteException|OCMProviderException) {
			return null;
		}

		if (!$provider->isEnabled() || !$provider->hasCapability(Application::OCM_CAPABILITY)) {
			return null;
		}

		return Application::OCM_CAPABILITY . '/' . $this->feedStringWithParams(self::OCM_PATHS[$item], $params);
	}

	/**
	 * Sends a signed request to the OCM endpoint of the remote instance.
	 *
	 * @throws FederatedItemException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws UnknownRemoteException
	 */
	private function resultRequestRemoteOcmEndpoint(string $instance, string $ocmPath, ?JsonSerializable $object): array {
		$remote = $this->getCachedRemoteInstance($instance);
		$options = [
			'timeout' => 5,
			'connect_timeout' => 5,
			'http_errors' => false,
			'verify' => $this->configService->getAppValue(ConfigService::SELF_SIGNED_CERT) !== '1',
			'nextcloud' => ['allow_local_address' => true],
		];

		$payload = is_null($object) ? [] : $this->serialize($object);
		// The OCM endpoint identifies the signing instance from the OCM address in the 'sender' field
		$payload['sender'] = Application::APP_ID . '@' . $this->interfaceService->getLocalInstance();

		try {
			$response = $this->ocmDiscoveryService->requestRemoteOcmEndpoint(
				Application::OCM_CAPABILITY,
				$remote->getRoot(),
				$ocmPath,
				$payload,
				'post',
				options: $options,
			);
		} catch (Exception $e) {
			throw new RemoteInstanceException($e->getMessage());
		}

		$data = json_decode((string)$response->getBody(), true);
		if (!is_array($data)) {
			$data = [];
		}

		if ($response->getStatusCode() === Http::STATUS_OK) {
			return $data;
		}

		throw $this->getFederatedItemException($response->getStatusCode(), $data);
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
		array $params = [],
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
		} catch (RequestNetworkException|SignatoryException $e) {
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
			} catch (RemoteNotFoundException) {
				throw new SignatoryException();
			}
		}

		$remoteInstance = new RemoteInstance($keyId);
		$confirm = $this->uuid();

		$request = new NCRequest();
		$this->configService->configureRequest($request);

		$request->setLocalAddressAllowed($this->config->getSystemValueBool('allow_local_remote_servers'));
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
		bool $overwrite = false,
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
		} catch (RemoteNotFoundException) {
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
	 * Rebuilds the exception returned by a remote instance from the status and body of its response.
	 *
	 * @param int $statusCode
	 * @param array $data
	 *
	 * @return FederatedItemException
	 */
	private function getFederatedItemException(int $statusCode, array $data): FederatedItemException {
		$message = $this->get('message', $data);
		$code = $this->getInt('code', $data);
		$class = $this->get('class', $data);

		try {
			$test = new ReflectionClass($class);
			$this->confirmFederatedItemExceptionFromClass($test);
			$e = $class;
		} catch (ReflectionException|FederatedItemException) {
			$e = $this->getFederatedItemExceptionFromStatus($statusCode);
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
		} catch (RemoteNotFoundException) {
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
