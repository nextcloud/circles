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
use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21SignedRequest;
use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21Controller;
use Exception;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\RemoteDownstreamService;
use OCA\Circles\Service\RemoteService;
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


	/** @var CircleRequest */
	private $circleRequest;

	/** @var RemoteService */
	private $remoteService;

	/** @var RemoteDownstreamService */
	private $remoteDownstreamService;

	/** @var ConfigService */
	private $configService;


	/**
	 * RemoteController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param CircleRequest $circleRequest
	 * @param RemoteService $remoteService
	 * @param RemoteDownstreamService $remoteDownstreamService
	 * @param ConfigService $configService
	 */
	public function __construct(
		string $appName, IRequest $request, CircleRequest $circleRequest, RemoteService $remoteService,
		RemoteDownstreamService $remoteDownstreamService, ConfigService $configService
	) {
		parent::__construct($appName, $request);
		$this->circleRequest = $circleRequest;
		$this->remoteService = $remoteService;
		$this->remoteDownstreamService = $remoteDownstreamService;
		$this->configService = $configService;

		$this->setup('app', 'circles');
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
			return $this->fail($e, [], Http::STATUS_BAD_REQUEST);
		}

		try {
			$this->remoteDownstreamService->requestedEvent($event);
		} catch (FederatedEventException $e) {
			$event->setOutcome($e->getMessage(), $e->getParams(), false);
		} catch (Exception $e) {
			$event->setOutcome($e->getMessage(), [], false);
		}

		return $this->successObj($event->getOutcome());
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
			$result = $this->remoteDownstreamService->incomingEvent($event);

			return $this->success($result);
		} catch (Exception $e) {
			return $this->fail($e);
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
		$test = $this->remoteService->incomingSignedRequest($this->configService->getLocalInstance());

		return $this->successObj($test);
	}


	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return DataResponse
	 */
	public function circles(): DataResponse {
		$circles = $this->circleRequest->getFederated();

		return $this->success($circles, false);
	}


	/**
	 * @param NC21SignedRequest $signedRequest
	 *
	 * @throws SignatoryException
	 */
	private function confirmRemoteInstance(NC21SignedRequest $signedRequest) {
		$signatory = $signedRequest->getSignatory();
		if (!$signatory instanceof RemoteInstance
			|| $signatory->getType() === RemoteInstance::TYPE_UNKNOWN) {
			$this->debug('Could not confirm identity', ['signedRequest' => $signedRequest]);
			throw new SignatoryException('could not confirm identity');
		}
	}


	/**
	 * @throws InvalidOriginException
	 * @throws MalformedArrayException
	 * @throws SignatoryException
	 * @throws SignatureException
	 */
	private function extractEventFromRequest(): FederatedEvent {
		$signed = $this->remoteService->incomingSignedRequest($this->configService->getLocalInstance());
		$this->confirmRemoteInstance($signed);

		$event = new FederatedEvent();
		$event->import(json_decode($signed->getBody(), true));
		$event->setIncomingOrigin($signed->getOrigin());

		return $event;
	}

}

