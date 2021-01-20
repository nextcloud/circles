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

use daita\MySmallPhpTools\Exceptions\InvalidOriginException;
use daita\MySmallPhpTools\Exceptions\MalformedArrayException;
use daita\MySmallPhpTools\Exceptions\SignatoryException;
use daita\MySmallPhpTools\Exceptions\SignatureException;
use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21Controller;
use Exception;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Service\RemoteService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;


/**
 * Class RemoteController
 *
 * @package OCA\Circles\Controller
 */
class RemoteController extends Controller {


	use TNC21Controller;


	/** @var CircleRequest */
	private $circleRequest;

	/** @var RemoteService */
	private $remoteService;


	public function __construct(
		string $appName, IRequest $request, CircleRequest $circleRequest, RemoteService $remoteService
	) {
		parent::__construct($appName, $request);

		$this->circleRequest = $circleRequest;
		$this->remoteService = $remoteService;
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
	public function test() {
		return $this->successObj($this->remoteService->incomingTest());
	}


	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @SignedRequest(signatory=toto)
	 */
	public function incoming() {
		try {
			return $this->success([]);
		} catch (Exception $e) {
			return $this->fail($e);
		}
	}


	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function circles() {
		$circles = $this->circleRequest->getFederated();

		return $this->success($circles, false);
	}

}

