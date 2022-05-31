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
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\RemoteRequest;
use OCA\Circles\Db\SyncedItemLockRequest;
use OCA\Circles\Db\SyncedItemRequest;
use OCA\Circles\Db\SyncedShareRequest;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedSyncConflictException;
use OCA\Circles\Exceptions\FederatedSyncManagerNotFoundException;
use OCA\Circles\Exceptions\FederatedSyncPermissionException;
use OCA\Circles\Exceptions\FederatedSyncRequestException;
use OCA\Circles\Exceptions\InitiatorNotConfirmedException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SyncedItemLockException;
use OCA\Circles\Exceptions\SyncedItemNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\IFederatedUser;
use OCA\Circles\InternalAsync\AsyncItemUpdate;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Model\SyncedItem;
use OCA\Circles\Model\SyncedItemLock;
use OCA\Circles\Model\SyncedWrapper;
use OCA\Circles\Tools\ActivityPub\NCSignature;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Model\ReferencedDataStore;
use OCA\Circles\Tools\Model\Request;
use OCA\Circles\Tools\Traits\TAsync;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCA\Circles\Tools\Traits\TStringTools;

class FederatedSyncItemService extends NCSignature {
	use TStringTools;
	use TDeserialize;
	use TAsync;

	const LOCK_RETRY_LIMIT = 3;
	const LOCK_TIMEOUT = 15; // in seconds

	private SyncedItemRequest $syncedItemRequest;
	private SyncedShareRequest $syncedShareRequest;
	private SyncedItemLockRequest $syncedItemLockRequest;
	private CircleRequest $circleRequest;
	private RemoteRequest $remoteRequest;
	private FederatedSyncService $federatedSyncService;
	private FederatedEventService $federatedEventService;
	private RemoteStreamService $remoteStreamService;
	private InterfaceService $interfaceService;
	private AsyncService $asyncService;
	private DebugService $debugService;


	public function __construct(
		SyncedItemRequest $syncedItemRequest,
		SyncedShareRequest $syncedShareRequest,
		SyncedItemLockRequest $syncedItemLockRequest,
		CircleRequest $circleRequest,
		RemoteRequest $remoteRequest,
		FederatedSyncService $federatedSyncService,
		FederatedEventService $federatedEventService,
		RemoteStreamService $remoteStreamService,
		InterfaceService $interfaceService,
		AsyncService $asyncService,
		DebugService $debugService
	) {
		$this->syncedItemRequest = $syncedItemRequest;
		$this->syncedShareRequest = $syncedShareRequest;
		$this->syncedItemLockRequest = $syncedItemLockRequest;
		$this->circleRequest = $circleRequest;
		$this->remoteRequest = $remoteRequest;
		$this->federatedSyncService = $federatedSyncService;
		$this->federatedEventService = $federatedEventService;
		$this->remoteStreamService = $remoteStreamService;
		$this->interfaceService = $interfaceService;
		$this->asyncService = $asyncService;
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

//
//	/**
//	 * @param string $singleId
//	 * @param bool $serializeItem
//	 *
//	 * @return SyncedItem
//	 * @throws FederatedSyncConflictException
//	 * @throws FederatedSyncManagerNotFoundException
//	 * @throws SyncedItemNotFoundException
//	 */
//	public function getSyncedItem(string $singleId, bool $serializeItem = false): SyncedItem {
//		$item = $this->syncedItemRequest->getSyncedItemFromSingleId($singleId);
//
//		if ($serializeItem) {
//			$item->setSerialized(
//				$this->federatedSyncService->initSyncManager($item)
//										   ->serializeItem($item->getItemId())
//			);
//		}
//
//		return $item;
//	}

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
	public function initSyncedItem(
		string $appId,
		string $itemType,
		string $itemId
	): SyncedItem {

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
	 * @param IFederatedUser $federatedUser
	 * @param SyncedItem $syncedItem
	 * @param SyncedItemLock $syncedLock
	 * @param array $extraData
	 * @param bool $initiatedRemotely
	 *
	 * @return array
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws FederatedSyncConflictException
	 * @throws FederatedSyncManagerNotFoundException
	 * @throws InitiatorNotConfirmedException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws UnknownRemoteException
	 */
	public function requestSyncedItemUpdate(
		IFederatedUser $federatedUser,
		SyncedItem $syncedItem,
		SyncedItemLock $syncedLock,
		array $extraData = [],
		?string $remoteSum = null
	): void {
		// confirm item is local
		if ($syncedItem->isLocal()) {
			$this->requestSyncedItemUpdateLocal(
				$federatedUser,
				$syncedItem,
				$syncedLock,
				$extraData,
				$remoteSum
			);

			return;
		} else if (is_null($remoteSum)) {
			$this->requestSyncedItemUpdateRemote($federatedUser, $syncedItem, $syncedLock, $extraData);

			return;
		}

		throw new FederatedSyncConflictException();
	}


	/**
	 * @param IFederatedUser $federatedUser
	 * @param SyncedItem $syncedItem
	 * @param SyncedItemLock $syncedLock
	 * @param array $extraData
	 *
	 * @return array
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws FederatedSyncManagerNotFoundException
	 * @throws InitiatorNotConfirmedException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws UnknownRemoteException
	 * @throws FederatedSyncPermissionException
	 */
	private function requestSyncedItemUpdateLocal(
		IFederatedUser $federatedUser,
		SyncedItem $syncedItem,
		SyncedItemLock $syncedLock,
		array $extraData = [],
		?string $remoteSum = null
	): void {
		// item will be lock during the process, only to be unlocked when new item checksum have
		// been calculated (on async process)
		$this->manageLock($syncedItem, $syncedLock, $remoteSum);

		if (!$this->isItemModifiable($federatedUser, $syncedItem, $syncedLock, $extraData)) {
			throw new FederatedSyncPermissionException('item modification not allowed');
		}

		$syncManager = $this->federatedSyncService->initSyncManager($syncedItem);
//		$syncManager->syncItem(
//			$syncedItem->getItemId(),
//			$item
//		);

		$syncManager->onItemModification(
			$syncedItem->getItemId(),
			$syncedLock->getUpdateType(),
			$syncedLock->getUpdateTypeId(),
			$extraData,
			$federatedUser
		);

		// this should split the process and give back hand if request is coming from a remote instance
		$this->asyncService->splitArray(['success' => true]);

		$this->asyncService->asyncInternal(
			AsyncItemUpdate::class,
			new ReferencedDataStore(
				[
					'syncedItem' => $syncedItem,
					'syncedItemLock' => $syncedLock,
				]
			)
		);
	}


	private function requestSyncedItemUpdateRemote(
		IFederatedUser $federatedUser,
		SyncedItem $syncedItem,
		SyncedItemLock $syncedLock,
		array $extraData = []
	): array {
		$wrapper = new SyncedWrapper($federatedUser, $syncedItem, $syncedLock, null, $extraData);
		$this->interfaceService->setCurrentInterfaceFromInstance($syncedItem->getInstance());
		$data = $this->remoteStreamService->resultRequestRemoteInstance(
			$syncedItem->getInstance(),
			RemoteInstance::SYNC_ITEM,
			Request::TYPE_PUT,
			$wrapper
		);

		if (!$this->getBool('success', $data)) {
			throw new FederatedSyncRequestException();
		}

		$syncManager = $this->federatedSyncService->initSyncManager($syncedItem);
		$syncManager->onItemModification(
			$syncedItem->getItemId(),
			$syncedLock->getUpdateType(),
			$syncedLock->getUpdateTypeId(),
			$extraData,
			$federatedUser
		);

//		$syncManager->syncItem(
//			$syncedItem->getItemId(),
//			$data
//		);

		// update item based on current (old) checksum to confirm item was not already updated (race condition)
		// do not update checksum
//		$this->updateChecksum($syncedItem->getSingleId(), $data);

		return $data;
	}
//
//
//	private function updateChecksum(string $syncedItemId, ?array $data = null): void {
//		$currSum = '';
//		if (is_null($data)) {
//			$knownItem = $this->getLocalSyncedItem($syncedItemId, true);
//			$data = $knownItem->getSerialized();
//			$currSum = $knownItem->getChecksum();
//		}
//
//		$sum = md5(json_encode($data));
//		if ($sum === $currSum) {
//			return;
//		}
//
//		$this->syncedItemRequest->updateChecksum($syncedItemId, $sum);
//	}


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

//		try {
//			$this->syncedItemRequest->getSyncedItemFromSingleId($syncedItem->getSingleId());
//		} catch (SyncedItemNotFoundException $e) {
		$this->debugService->info(
			'storing SyncedItem {syncedItem.singleId} in database', '',
			['syncedItem' => $syncedItem]
		);

		$this->syncedItemRequest->save($syncedItem);

//		}


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


	/**
	 * @param IFederatedUser $federatedUser
	 * @param SyncedItem $syncedItem
	 * @param SyncedItemLock $syncedLock
	 * @param array $extraData
	 *
	 * @return bool
	 * @throws FederatedSyncManagerNotFoundException
	 */
	private function isItemModifiable(
		IFederatedUser $federatedUser,
		SyncedItem $syncedItem,
		SyncedItemLock $syncedLock,
		array $extraData = []
	): bool {
		$syncManager = $this->federatedSyncService->initSyncManager($syncedItem);
		$this->debugService->info(
			'sharing of SyncedItem {syncedItem.singleId} looks doable, calling {`isShareCreatable()} on {syncManager.class} for confirmation',
			'',
			[
				'federatedUser' => $federatedUser,
				'syncedItem' => $syncedItem,
				'syncManager' => ['class' => get_class($syncManager)]
			]
		);

		return $syncManager->isItemModifiable(
			$syncedItem->getItemId(),
			$syncedLock->getUpdateType(),
			$syncedLock->getUpdateTypeId(),
			$extraData,
			$federatedUser
		);
	}


	/**
	 * @param SyncedItem $syncedItem
	 *
	 * @throws FederatedSyncConflictException
	 */
	public function compareWithKnownItem(
		SyncedItem $syncedItem,
		bool $allowUnknownItem = false
	): void {
		try {
			$this->compareWithKnownItemId($syncedItem);
			$this->compareWithKnownSingleId($syncedItem);
		} catch (FederatedSyncConflictException $e) {
			$this->debugService->exception(
				$e, '',
				[
					'note' => 'WIP: exception is thrown and catch to async process; while returning error',
					'note2' => 'async process will try to fix the conflict'
				]
			);
			// TODO: manage FederatedSyncConflictException - should not be run 'live' at this point
			// The solution might be to Async the current process with an error while fixing the issue
			// on the child process. remote instance will tell the initiator that there is an issue and
			// he should try again (estimating the process to fix conflict might takes few seconds.
			// To do so, catching the exception earlier instead of here.
			throw $e;
		} catch (SyncedItemNotFoundException $e) {
			if (!$allowUnknownItem) {
				throw $e;
			}

			$this->debugService->info(
				'no known syncedItem {syncedItem.singleId} were found in database, assuming this is good',
				'',
				['syncedItem' => $syncedItem]
			);
		}
	}


	/**
	 * @throws FederatedSyncConflictException
	 * @throws SyncedItemNotFoundException
	 */
	private function compareWithKnownSingleId(SyncedItem $syncedItem): void {
		$knownItem = $this->syncedItemRequest->getSyncedItemFromSingleId($syncedItem->getSingleId());
		$this->debugService->info(
			'Comparing with the SyncedItem {syncedItem.singleId} from database: {knownItem.appId}.{knownItem.itemType}.{knownItem.itemId}',
			'',
			[
				'syncedItem' => $syncedItem,
				'knownItem' => $knownItem
			]
		);

		if ($knownItem->getAppId() !== $syncedItem->getAppId()
			|| $knownItem->getItemType() !== $syncedItem->getItemType()
			|| $knownItem->getInstance() !== $syncedItem->getInstance()
			|| $knownItem->isDeleted()) {
			throw new FederatedSyncConflictException('conflict/dsync on SyncedItem');
		}
	}


	/**
	 * @param SyncedItem $syncedItem
	 *
	 * @throws FederatedSyncConflictException
	 */
	private function compareWithKnownItemId(SyncedItem $syncedItem): void {
		try {
			$knownItem = $this->syncedItemRequest->getSyncedItem(
				$syncedItem->getAppId(),
				$syncedItem->getItemType(),
				$syncedItem->getItemId()
			);
		} catch (SyncedItemNotFoundException $e) {
			return;
		}

		$this->debugService->info(
			'Comparing with the SyncedItem {syncedItem.appId}.{syncedItem.itemType}.{syncedItem.itemId} from database: {knownItem.singleId}',
			'',
			[
				'syncedItem' => $syncedItem,
				'knownItem' => $knownItem
			]
		);

		if ($knownItem->getSingleId() !== $syncedItem->getSingleId()) {
			throw new FederatedSyncConflictException('conflict/dsync on SyncedItem');
		}
	}


	/**
	 * @param SyncedItemLock $syncedLock
	 *
	 * @throws InvalidItemException
	 * @throws SyncedItemLockException
	 */
	private function manageLock(
		SyncedItem $syncedItem,
		SyncedItemLock $syncedLock,
		?string $remoteSum = null
	): void {
		$locked = true;
		for ($i = 0; $i < self::LOCK_RETRY_LIMIT; $i++) {
			try {
				$this->syncedItemLockRequest->clean(self::LOCK_TIMEOUT);
				$this->syncedItemLockRequest->getSyncedItemLock($syncedLock);
				sleep(1);
			} catch (SyncedItemNotFoundException $e) {
				$locked = false;
				break;
			}
		}

		if (!is_null($remoteSum) && $syncedLock->isVerifyChecksum()) {
			$known = $this->syncedItemRequest->getSyncedItemFromSingleId($syncedItem->getSingleId());
			if ($known->getChecksum() !== $remoteSum) {
				throw new SyncedItemLockException('checksum is too old, sync required');
			}
		}

		if ($locked) {
			throw new SyncedItemLockException('item is currently lock, try again later');
		}

		$this->syncedItemLockRequest->save($syncedLock);
	}

}
