<?php declare(strict_types=1);


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


namespace OCA\Circles\Controller;


use daita\MySmallPhpTools\Traits\Nextcloud\nc22\TNC22Controller;
use daita\MySmallPhpTools\Traits\TAsync;
use daita\MySmallPhpTools\Traits\TStringTools;
use OCA\Circles\Service\FederatedEventService;
use OCA\Circles\Service\RemoteDownstreamService;
use OCA\Circles\Service\RemoteUpstreamService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;


/**
 * Class GlobalScaleController
 *
 * @package OCA\Circles\Controller
 */
class EventWrapperController extends Controller {


	use TStringTools;
	use TAsync;
	use TNC22Controller;


	/** @var FederatedEventService */
	private $federatedEventService;

	/** @var RemoteUpstreamService */
	private $remoteUpstreamService;

	/** @var RemoteDownstreamService */
	private $remoteDownstreamService;


	/**
	 * GlobalScaleController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param FederatedEventService $federatedEventService
	 * @param RemoteUpstreamService $remoteUpstreamService
	 * @param RemoteDownstreamService $remoteDownstreamService
	 */
	public function __construct(
		string $appName, IRequest $request,
		FederatedEventService $federatedEventService, RemoteUpstreamService $remoteUpstreamService,
		RemoteDownstreamService $remoteDownstreamService
	) {
		parent::__construct($appName, $request);
		$this->federatedEventService = $federatedEventService;
		$this->remoteUpstreamService = $remoteUpstreamService;
		$this->remoteDownstreamService = $remoteDownstreamService;
	}


	/**
	 * Called locally.
	 *
	 * Async process and broadcast the event to every instances of GS
	 * This should be initiated by the instance that owns the Circles.
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $token
	 *
	 * @return DataResponse
	 */
	public function asyncBroadcast(string $token): DataResponse {
		$wrappers = $this->remoteUpstreamService->getEventsByToken($token);
		if (empty($wrappers)) {
			return new DataResponse(null, Http::STATUS_OK);
		}

		// closing socket, keep current process running.
		$this->async();
		foreach ($wrappers as $wrapper) {
			$this->remoteUpstreamService->broadcastWrapper($wrapper);
		}

		$this->federatedEventService->manageResults($token);

		// exit() or useless log will be generated
		exit();
	}


//	/**
//	 * Status Event. This is an event to check status of items between instances.
//	 *
//	 * @PublicPage
//	 * @NoCSRFRequired
//	 *
//	 * @return DataResponse
//	 */
//	public function status(): DataResponse {
//		$data = file_get_contents('php://input');
//
//		try {
//			$event = new GSEvent();
//			$event->importFromJson($data);
//			$this->gsDownstreamService->statusEvent($event);
//
//			return $this->success(['success' => $event]);
//		} catch (Exception $e) {
//			return $this->fail(['data' => $data, 'error' => $e->getMessage()]);
//		}
//	}

}

