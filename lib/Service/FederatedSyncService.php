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

use OCA\Circles\Db\RemoteRequest;
use OCA\Circles\Db\SyncedItemRequest;
use OCA\Circles\Db\SyncedShareRequest;
use OCA\Circles\Exceptions\FederatedSyncManagerNotFoundException;
use OCA\Circles\IFederatedSyncManager;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Model\SyncedItem;
use OCA\Circles\Tools\ActivityPub\NCSignature;
use OCA\Circles\Tools\Model\Request;
use OCA\Circles\Tools\Traits\TStringTools;

class FederatedSyncService extends NCSignature {
	use TStringTools;

	private SyncedItemRequest $syncedItemRequest;
	private SyncedShareRequest $syncedShareRequest;
	private RemoteRequest $remoteRequest;
	private FederatedEventService $federatedEventService;
	private RemoteStreamService $remoteStreamService;
	private InterfaceService $interfaceService;

	/** @var IFederatedSyncManager[] */
	private array $syncManager = [];


	/**
	 * @param SyncedItemRequest $syncedItemRequest
	 * @param SyncedShareRequest $syncedShareRequest
	 * @param RemoteRequest $remoteRequest
	 * @param FederatedEventService $federatedEventService
	 * @param RemoteStreamService $remoteStreamService
	 * @param InterfaceService $interfaceService
	 */
	public function __construct(
		SyncedItemRequest $syncedItemRequest,
		SyncedShareRequest $syncedShareRequest,
		RemoteRequest $remoteRequest,
		FederatedEventService $federatedEventService,
		RemoteStreamService $remoteStreamService,
		InterfaceService $interfaceService
	) {
		$this->syncedItemRequest = $syncedItemRequest;
		$this->syncedShareRequest = $syncedShareRequest;
		$this->remoteRequest = $remoteRequest;
		$this->federatedEventService = $federatedEventService;
		$this->remoteStreamService = $remoteStreamService;
		$this->interfaceService = $interfaceService;
	}


	/**
	 * @param IFederatedSyncManager $federatedSyncManager
	 */
	public function addFederatedSyncManager(IFederatedSyncManager $federatedSyncManager): void {
		$this->syncManager[] = $federatedSyncManager;
	}


	/**
	 * @param string $appId
	 * @param string $itemType
	 *
	 * @return IFederatedSyncManager
	 * @throws FederatedSyncManagerNotFoundException
	 */
	public function getSyncManager(string $appId, string $itemType): IFederatedSyncManager {
		foreach ($this->syncManager as $federatedSyncManager) {
			if ($federatedSyncManager->getAppId() === $appId
				&& $federatedSyncManager->getItemType() === $itemType) {
				return $federatedSyncManager;
			}
		}

		throw new FederatedSyncManagerNotFoundException();
	}


	/**
	 * @param SyncedItem $syncedItem
	 *
	 * @return IFederatedSyncManager
	 * @throws FederatedSyncManagerNotFoundException
	 */
	public function initSyncManager(SyncedItem $syncedItem): IFederatedSyncManager {
		return $this->getSyncManager($syncedItem->getAppId(), $syncedItem->getItemType());
	}



	//
	public function reachInstance(string $instance) {
//		$remoteInstance = $this->remoteRequest->getFromInstance($instance);
		$syncedItem = new SyncedItem();
		$syncedItem->setSingleId('toto');

//		$this->interfaceService->setCurrentInterface($remoteInstance->getInterface());
		$data = $this->remoteStreamService->resultRequestRemoteInstance(
			$instance,
			RemoteInstance::SYNC_ITEM,
			Request::TYPE_POST,
			$syncedItem
		);

		echo 'reached !? ' . json_encode($data) . "\n";
	}

}
