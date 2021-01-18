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


use daita\MySmallPhpTools\ActivityPub\Nextcloud\nc21\NC21Signature;
use daita\MySmallPhpTools\Exceptions\InvalidOriginException;
use daita\MySmallPhpTools\Exceptions\MalformedArrayException;
use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Exceptions\SignatoryException;
use daita\MySmallPhpTools\Exceptions\SignatureException;
use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21Request;
use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21SignedRequest;
use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21LocalSignatory;
use daita\MySmallPhpTools\Traits\TStringTools;
use OCA\Circles\Db\RemoteRequest;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\RemoteUidException;
use OCA\Circles\Model\AppService;
use OCP\IURLGenerator;


/**
 * Class RemoteService
 *
 * @package OCA\Circles\Service
 */
class RemoteService extends NC21Signature {


	use TNC21LocalSignatory;
	use TStringTools;


	const UPDATE_DATA = 'data';
	const UPDATE_INSTANCE = 'instance';
	const UPDATE_HREF = 'href';


	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var RemoteRequest */
	private $remoteRequest;

	/** @var ConfigService */
	private $configService;


	/**
	 * RemoteService constructor.
	 *
	 * @param IURLGenerator $urlGenerator
	 * @param RemoteRequest $remoteRequest
	 * @param ConfigService $configService
	 */
	public function __construct(
		IURLGenerator $urlGenerator, RemoteRequest $remoteRequest, ConfigService $configService
	) {
		$this->setup('app', 'circles');

		$this->urlGenerator = $urlGenerator;
		$this->remoteRequest = $remoteRequest;
		$this->configService = $configService;
	}


	/**
	 * @param bool $generate
	 *
	 * @param string $confirmKey
	 *
	 * @return AppService
	 * @throws SignatoryException
	 * @throws SignatureException
	 */
	public function getAppSignatory(bool $generate = false, string $confirmKey = ''): AppService {
		$app = new AppService($this->configService->getRemotePath());
		$this->fillSimpleSignatory($app, $generate);
		$app->setUidFromKey();

		if ($confirmKey !== '') {
			$app->setAuthSigned($this->signString($confirmKey, $app));
			$this->verifyString($confirmKey, base64_decode($app->getAuthSigned()), $app->getPublicKey());
		}

		$app->setTest($this->configService->getRemotePath('circles.Remote.test'));
		$app->setIncoming($this->configService->getRemotePath('circles.Remote.incoming'));
		$app->setCircles($this->configService->getRemotePath('circles.Remote.circles'));
		$app->setMembers($this->configService->getRemotePath('circles.Remote.members'));

		$app->setOrigData($app->jsonSerialize());

		return $app;
	}

	/**
	 * @throws SignatureException
	 */
	public function resetAppSignatory(): void {
		try {
			$app = $this->getAppSignatory();

			$this->removeSimpleSignatory($app);
		} catch (SignatoryException $e) {
		}
	}


	/**
	 * @param string $remote
	 * @param array $data
	 *
	 * @return NC21SignedRequest
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws SignatureException
	 */
	public function outgoingTest(string $remote, array $data = ['test' => 42]): NC21SignedRequest {
		$request = new NC21Request();
		$request->basedOnUrl($remote);
		$request->setFollowLocation(true);
		$request->setLocalAddressAllowed(true);
		$request->setTimeout(5);
		$request->setData($data);

		$app = $this->getAppSignatory();
//		$app->setAlgorithm(NC21Signatory::SHA512);
		$signedRequest = $this->signRequest($request, $app);
		$this->doRequest($signedRequest->getOutgoingRequest());

		return $signedRequest;
	}

	/**
	 * @return NC21SignedRequest
	 * @throws InvalidOriginException
	 * @throws MalformedArrayException
	 * @throws SignatoryException
	 * @throws SignatureException
	 */
	public function incomingTest(): NC21SignedRequest {
		return $this->incomingSignedRequest($this->configService->getLocalInstance());
	}


	/**
	 * @param string $instance
	 *
	 * @return array
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws SignatureException
	 */
	public function getCircles(string $instance): array {
		$result = $this->requestRemoteResource($instance, 'circles');
		echo '--> ' . json_encode($result) . " \n";
		//$url = $this->
		$circles = [];

		return $circles;
	}


	/**
	 * @param string $instance
	 * @param string $item
	 *
	 * @return array
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws SignatureException
	 */
	public function requestRemoteResource(string $instance, string $item): array {
		$link = $this->getRemoteEntry($instance, $item);

		$request = new NC21Request();
		$request->basedOnUrl($link);
		$request->setFollowLocation(true);
		$request->setLocalAddressAllowed(true);
		$request->setTimeout(5);
//		$request->setData($data);

		$app = $this->getAppSignatory();
//		$app->setAlgorithm(NC21Signatory::SHA512);
		$signedRequest = $this->signRequest($request, $app);
		$this->doRequest($signedRequest->getOutgoingRequest());

		return $signedRequest->getOutgoingRequest()->getResult()->getAsArray();
	}


//
//	public function outgoing(string $instance, string $route) {
//		$this->retrieveSignatory()
//	}


	/**
	 * @param string $instance
	 * @param string $item
	 *
	 * @return string
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws SignatoryException
	 * @throws SignatureException
	 */
	public function getRemoteEntry(string $instance, string $item): string {
		if ($this->configService->isLocalInstance($instance)) {
			$remote = $this->getAppSignatory();
		} else {
			$remote = $this->remoteRequest->getFromInstance($instance);
		}

		$value = $this->get($item, $remote->getOrigData());
		if ($value === '') {
			throw new RemoteResourceNotFoundException();
		}

		return $value;
	}


	/**
	 * @param string $keyId
	 * @param bool $refresh
	 * @param bool $auth
	 *
	 * @return AppService
	 * @throws SignatoryException
	 * @throws SignatureException
	 */
	public function retrieveSignatory(string $keyId, bool $refresh = false, bool $auth = false): AppService {
		if (!$refresh) {
			//		 return	$this->retrieveCachedSignatory($keyId);
			throw new SignatoryException();
		}

		$appService = new AppService($keyId);
		$confirm = '';
		$params = [];
		if ($auth) {
			$confirm = $this->uuid();
			$params['auth'] = $confirm;
		}

		$this->downloadSignatory($appService, $keyId, $params);
		$appService->setUidFromKey();

		$this->confirmAuth($appService, $confirm);

		return $appService;
	}


	/**
	 * @param AppService $remote
	 * @param string $auth
	 *
	 * @throws SignatureException
	 */
	private function confirmAuth(AppService $remote, string $auth): void {
		if ($auth === '') {
			return;
		}

		list($algo, $signed) = explode(':', $this->get('auth-signed', $remote->getOrigData()));
		try {
			if ($signed === null) {
				throw new SignatureException('invalid auth-signed');
			}
			$this->verifyString($auth, base64_decode($signed), $remote->getPublicKey(), $algo);
			$remote->setIdentityAuthed(true);
		} catch (SignatureException $e) {
			$this->debug(
				'Auth cannot be confirmed',
				['auth' => $auth, 'signed' => $signed, 'signatory' => $remote]
			);
			throw $e;
		}
	}


	/**
	 * @param AppService $remote
	 * @param AppService|null $stored
	 *
	 * @throws RemoteNotFoundException
	 * @throws RemoteUidException
	 */
	public function confirmValidRemote(AppService $remote, ?AppService &$stored = null): void {
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
	 * @param AppService $remote
	 */
	public function save(AppService $remote): void {
		$this->remoteRequest->save($remote);
	}

	/**
	 * @param AppService $remote
	 * @param string $update
	 *
	 * @throws RemoteUidException
	 */
	public function update(AppService $remote, string $update = self::UPDATE_DATA): void {
		switch ($update) {
			case self::UPDATE_DATA:
				$this->remoteRequest->update($remote);
				break;

			case self::UPDATE_HREF:
				$remote->mustBeIdentityAuthed();
				$this->remoteRequest->updateHref($remote);
				break;

			case self::UPDATE_INSTANCE:
				$remote->mustBeIdentityAuthed();
				$this->remoteRequest->updateInstance($remote);
				break;
		}
	}

}

