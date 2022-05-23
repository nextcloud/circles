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

use Exception;
use OCA\Circles\Db\RemoteRequest;
use OCA\Circles\Db\SyncedItemRequest;
use OCA\Circles\Db\SyncedShareRequest;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedSyncConflictException;
use OCA\Circles\Exceptions\FederatedSyncManagerNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\SyncedItemNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Model\SyncedItem;
use OCA\Circles\Tools\ActivityPub\NCSignature;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Model\Request;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCA\Circles\Tools\Traits\TStringTools;

class FederatedSyncItemService extends NCSignature {
	use TStringTools;
	use TDeserialize;

	private SyncedItemRequest $syncedItemRequest;
	private SyncedShareRequest $syncedShareRequest;
	private RemoteRequest $remoteRequest;
	private FederatedSyncService $federatedSyncService;
	private FederatedEventService $federatedEventService;
	private RemoteStreamService $remoteStreamService;
	private InterfaceService $interfaceService;
	private DebugService $debugService;


	/**
	 * @param SyncedItemRequest $syncedItemRequest
	 * @param SyncedShareRequest $syncedShareRequest
	 * @param RemoteRequest $remoteRequest
	 * @param FederatedSyncService $federatedSyncService
	 * @param FederatedEventService $federatedEventService
	 * @param RemoteStreamService $remoteStreamService
	 * @param InterfaceService $interfaceService
	 * @param DebugService $debugService
	 */
	public function __construct(
		SyncedItemRequest $syncedItemRequest,
		SyncedShareRequest $syncedShareRequest,
		RemoteRequest $remoteRequest,
		FederatedSyncService $federatedSyncService,
		FederatedEventService $federatedEventService,
		RemoteStreamService $remoteStreamService,
		InterfaceService $interfaceService,
		DebugService $debugService
	) {
		$this->syncedItemRequest = $syncedItemRequest;
		$this->syncedShareRequest = $syncedShareRequest;
		$this->remoteRequest = $remoteRequest;
		$this->federatedSyncService = $federatedSyncService;
		$this->federatedEventService = $federatedEventService;
		$this->remoteStreamService = $remoteStreamService;
		$this->interfaceService = $interfaceService;
		$this->debugService = $debugService;
	}


	/**
	 * @param SyncedItem $syncedItem
	 *
	 * @return SyncedItem
	 * @throws FederatedItemException
	 * @throws FederatedSyncConflictException
	 * @throws InvalidItemException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 */
	public function confirmRemoteSyncedItem(SyncedItem $syncedItem): SyncedItem {
		if ($syncedItem->isLocal()) {
			// TODO: how is it really handle ?
			throw new FederatedSyncConflictException('instance of SyncedItem is set as local');
		}

		$this->interfaceService->setCurrentInterfaceFromInstance($syncedItem->getInstance());
		$data = $this->remoteStreamService->resultRequestRemoteInstance(
			$syncedItem->getInstance(),
			RemoteInstance::SYNC_ITEM,
			Request::TYPE_GET,
			$syncedItem
		);

		/** @var SyncedItem $remoteItem */
		$remoteItem = $this->deserialize($data, SyncedItem::class);

		if (!$syncedItem->compareWith($remoteItem)) {
			// TODO: how is it really handle ?
			throw new FederatedSyncConflictException('returned data from remote instance does not fit');
		}

		$this->debugService->info(
			'data from SyncedItem {syncedItem.singleId} retrieved from {syncedItem.instance} with Checksum {remoteItem.checksum}',
			'',
			[
				'data' => $data,
				'syncedItem' => $syncedItem,
				'remoteItem' => $remoteItem
			]
		);

		return $remoteItem;
	}


	/**
	 * TODO: $serializeItem might not be a necessary check
	 *
	 * @param string $singleId
	 * @param bool $serializeItem
	 *
	 * @return SyncedItem
	 * @throws FederatedSyncConflictException
	 * @throws FederatedSyncManagerNotFoundException
	 * @throws SyncedItemNotFoundException
	 */
	public function getLocalSyncedItem(string $singleId, bool $serializeItem = false): SyncedItem {
		$item = $this->syncedItemRequest->getSyncedItemFromSingleId($singleId);
		if (!$item->isLocal()) {
			throw new FederatedSyncConflictException();
		}

		if ($serializeItem) {
			$item->setSerialized(
				$this->federatedSyncService->initSyncManager($item)
										   ->serializeItem($item->getItemId())
			);
		}

		return $item;
	}


	/**
	 * get existing SyncedItem based appId, itemType and itemId.
	 *
	 * throws FederatedSyncConflictException if sync conflict:
	 *   - item is marked as deleted, but app have not confirmed its deletion,
	 *   - item is not local and have no known origin.
	 *
	 * if SyncedItem does not exist, app is called to confirm itemId exist locally. If item exists, we create
	 * a new SyncedItem.
	 *
	 * @param string $appId
	 * @param string $itemType
	 * @param string $itemId
	 *
	 * @return SyncedItem
	 * @throws FederatedSyncConflictException
	 * @throws FederatedSyncManagerNotFoundException
	 */
	public function getSyncedItem(string $appId, string $itemType, string $itemId): SyncedItem {

		// verify existing SyncedItem is not flag as deleted
		try {
			$syncedItem = $this->syncedItemRequest->getSyncedItem($appId, $itemType, $itemId);
			if ($syncedItem->isDeleted()) {
				throw new FederatedSyncConflictException("SyncedItem $appId.$itemType.$itemId is deprecated");
			}

			$this->debugService->info('Found SyncedItem {syncedItem.singleId} in database', '', [
				'syncedItem' => $syncedItem
			]);

			return $syncedItem;
		} catch (SyncedItemNotFoundException $e) {
		}

		$syncManager = $this->federatedSyncService->getSyncManager($appId, $itemType);
		$this->debugService->info(
			'SyncedItem is unknown, calling {`serializeItem()} on {syncManager.class} to confirm item {itemId} is local',
			'',
			[
				'syncManager' => get_class($syncManager),
				'itemId' => $itemId
			]
		);
		try {
			$syncManager->serializeItem($itemId);
		} catch (Exception $e) {
			throw new FederatedSyncConflictException(
				'SyncedItem not found in database and does not appears to be local'
			);
		}

		// create entry
		return $this->createSyncedItem($appId, $itemType, $itemId);
	}


	/**
	 * create a new (local) SyncedItem based on appId, itemType, itemId.
	 *
	 * @param string $appId
	 * @param string $itemType
	 * @param string $itemId
	 *
	 * @return SyncedItem
	 */
	private function createSyncedItem(string $appId, string $itemType, string $itemId): SyncedItem {
		$syncedItem = new SyncedItem();
		$syncedItem->setSingleId($this->token(31))
				   ->setAppId($appId)
				   ->setItemType($itemType)
				   ->setItemId($itemId);

		$this->debugService->info(
			'generating new SyncedItem for {syncedItem.appId}.{syncedItem.itemType}.{syncedItem.itemId}',
			'',
			['syncedItem' => $syncedItem]
		);

		return $syncedItem;
	}


	/**
	 * TODO: verify Exception returned and how to handle them (remove entry ? notification ?)
	 *
	 * This method request syncedItem.instance to confirm its validity directly from the said source.
	 * It also creates/updates the entry in `circles_item`.
	 *
	 * @param SyncedItem $syncedItem
	 *
	 * @throws FederatedItemException
	 * @throws FederatedSyncConflictException
	 * @throws InvalidItemException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws SyncedItemNotFoundException
	 * @throws UnknownRemoteException
	 * @throws InvalidIdException
	 * @throws FederatedSyncManagerNotFoundException
	 */
	public function updateSyncedItem(SyncedItem $syncedItem): void {
		$this->debugService->info(
			'updating SyncedItem {syncedItem.singleId} from {syncedItem.instance}', '',
			['syncedItem' => $syncedItem]
		);

		try {
			$remoteItem = $this->confirmRemoteSyncedItem($syncedItem);
		} catch (SyncedItemNotFoundException $e) {
			$this->debugService->info('ERROR 404 !?');
			// TODO: delete entry in circles_item and circles_share ?
			$this->debugService->exception($e);
			throw $e;
		} catch (Exception $e) {
			$this->debugService->exception($e);
			throw $e;
		}

		$this->debugService->info(
			'remote SyncedItem {syncedItem.singleId} received with Checksum {remoteItem.checksum}', '',
			[
				'syncedItem' => $syncedItem,
				'remoteItem' => $remoteItem
			]
		);

		try {
			$localItem = $this->syncedItemRequest->getSyncedItemFromSingleId($remoteItem->getSingleId());
			if ($localItem->getChecksum() === $remoteItem->getChecksum()) {
				$this->debugService->info(
					'local Checksum {syncedItem.checksum} for SyncedItem {syncedItem.singleId} is identical; no update needed',
					'',
					[
						'syncedItem' => $syncedItem,
						'remoteItem' => $remoteItem
					]
				);

				return;
			}
		} catch (SyncedItemNotFoundException $e) {
		}

		$syncManager = $this->federatedSyncService->initSyncManager($syncedItem);
		$this->debugService->info(
			'no conflict found yet, calling {`syncItem()} on {syncManager.class} to create/update local entry',
			'',
			['syncManager' => ['class' => get_class($syncManager)]]
		);

		$syncManager->syncItem($syncedItem->getItemId(), $remoteItem->getSerialized());
		$this->debugService->info(
			'storing SyncedItem {syncedItem.singleId} into database as no local entry found',
			'',
			[
				'syncedItem' => $syncedItem,
				'remoteItem' => $remoteItem
			]
		);

		$this->syncedItemRequest->save($remoteItem);
	}
}
