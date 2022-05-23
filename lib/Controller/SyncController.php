<?php

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


namespace OCA\Circles\Controller;

use Exception;
use OCA\Circles\Model\SyncedItem;
use OCA\Circles\Service\DebugService;
use OCA\Circles\Service\FederatedSyncItemService;
use OCA\Circles\Service\FederatedSyncShareService;
use OCA\Circles\Service\SignedControllerService;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCA\Circles\Tools\Traits\TNCLocalSignatory;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class SyncController extends Controller {
	use TNCLocalSignatory;
	use TDeserialize;

	private SignedControllerService $signedControllerService;
	private FederatedSyncItemService $federatedSyncItemService;
	private FederatedSyncShareService $federatedSyncShareService;
	private DebugService $debugService;

	public function __construct(
		string $appName,
		IRequest $request,
		SignedControllerService $signedControllerService,
		FederatedSyncItemService $federatedSyncItemService,
		FederatedSyncShareService $federatedSyncShareService,
		DebugService $debugService
	) {
		parent::__construct($appName, $request);

		$this->signedControllerService = $signedControllerService;
		$this->federatedSyncItemService = $federatedSyncItemService;
		$this->federatedSyncShareService = $federatedSyncShareService;
		$this->debugService = $debugService;
	}


	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return DataResponse
	 */
	public function getSyncedItem(): DataResponse {
		try {
			/** @var SyncedItem $item */
			$item = $this->signedControllerService->extractObjectFromRequest(
				SyncedItem::class,
				$signed
			);

			$this->debugService->info(
				'{instance} is requesting details on SyncedItem {syncedItem.singleId}', '',
				[
					'instance' => $signed->getOrigin(),
					'syncedItem' => $item
				]
			);
			$local = $this->federatedSyncItemService->getLocalSyncedItem($item->getSingleId(), true);

			// confirm that remote is in a circle with a share on the item
			$this->federatedSyncShareService->confirmRemoteInstanceAccess(
				$local->getSingleId(),
				$signed->getOrigin()
			);

			$this->debugService->info(
				'SyncedItem exists, is local, and {instance} have access to the SyncedItem.', '',
				[
					'instance' => $signed->getOrigin(),
					'local' => $local,
				]
			);

//			$this->federatedSyncItemService->get
			return new DataResponse($this->serialize($local));
		} catch (Exception $e) {
			$this->e($e);

			return $this->signedControllerService->exceptionResponse($e, Http::STATUS_UNAUTHORIZED);
		}
	}

}
