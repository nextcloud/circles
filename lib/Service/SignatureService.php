<?php
/**
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

namespace OCA\Circles\Service;


use daita\MySmallPhpTools\ActivityPub\Nextcloud\nc21\NC21Signature;
use daita\MySmallPhpTools\Exceptions\InvalidOriginException;
use daita\MySmallPhpTools\Exceptions\MalformedArrayException;
use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Exceptions\SignatoryException;
use daita\MySmallPhpTools\Exceptions\SignatureException;
use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21Request;
use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21Signatory;
use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21SignedRequest;
use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21LocalSignatory;
use OCA\Circles\Model\AppService;
use OCP\IURLGenerator;


/**
 * Class SignatureService
 *
 * @package OCA\Circles\Service
 */
class SignatureService extends NC21Signature {


	use TNC21LocalSignatory;


	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var ConfigService */
	private $configService;


	public function __construct(IURLGenerator $urlGenerator, ConfigService $configService) {
		$this->setup('app', 'circles');

		$this->urlGenerator = $urlGenerator;
		$this->configService = $configService;
	}


	/**
	 * @param string $keyId
	 * @param bool $refresh
	 *
	 * @return NC21Signatory
	 * @throws SignatoryException
	 */
	public function retrieveSignatory(string $keyId, bool $refresh = false): NC21Signatory {
		return parent::retrieveSignatory($keyId, $refresh);
	}


	/**
	 * @param string $remote
	 * @param array $data
	 *
	 * @return NC21SignedRequest
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 */
	public function test(string $remote, array $data = ['test' => 42]): NC21SignedRequest {
		$request = new NC21Request();
		$request->basedOnUrl($remote);
		$request->setFollowLocation(true);
		$request->setLocalAddressAllowed(true);
		$request->setTimeout(5);
		$request->setData($data);

		$app = new AppService($this->configService->getRemotePath('circles.Navigation.navigate'));
		$this->buildSimpleSignatory($app, true);
		$signedRequest = $this->signRequest($request, $app);

		$this->retrieveJson($signedRequest->getOutgoingRequest());

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


}
