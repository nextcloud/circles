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


namespace OCA\Circles\InternalAsync;

use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\SyncedItemLockRequest;
use OCA\Circles\Db\SyncedItemRequest;
use OCA\Circles\Db\SyncedShareRequest;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedSyncManagerNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotConfirmedException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SyncedItemNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\FederatedItems\FederatedSync\ItemUpdate;
use OCA\Circles\IInternalAsync;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\SyncedItem;
use OCA\Circles\Model\SyncedItemLock;
use OCA\Circles\Model\SyncedShare;
use OCA\Circles\Service\DebugService;
use OCA\Circles\Service\FederatedEventService;
use OCA\Circles\Service\FederatedSyncService;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Model\ReferencedDataStore;


class AsyncItemUpdate implements IInternalAsync {

	private CircleRequest $circleRequest;
	private SyncedItemRequest $syncedItemRequest;
	private SyncedShareRequest $syncedShareRequest;
	private SyncedItemLockRequest $syncedItemLockRequest;
	private FederatedEventService $federatedEventService;
	private FederatedSyncService $federatedSyncService;
	private DebugService $debugService;

	public function __construct(
		CircleRequest $circleRequest,
		SyncedItemRequest $syncedItemRequest,
		SyncedShareRequest $syncedShareRequest,
		SyncedItemLockRequest $syncedItemLockRequest,
		FederatedEventService $federatedEventService,
		FederatedSyncService $federatedSyncService,
		DebugService $debugService
	) {
		$this->circleRequest = $circleRequest;
		$this->syncedItemRequest = $syncedItemRequest;
		$this->syncedShareRequest = $syncedShareRequest;
		$this->syncedItemLockRequest = $syncedItemLockRequest;
		$this->federatedEventService = $federatedEventService;
		$this->federatedSyncService = $federatedSyncService;
		$this->debugService = $debugService;
	}


	/**
	 * @param ReferencedDataStore $store
	 *
	 * @throws FederatedSyncManagerNotFoundException
	 * @throws SyncedItemNotFoundException
	 * @throws InvalidItemException
	 */
	public function runAsynced(ReferencedDataStore $store): void {
		/** @var SyncedItem $syncedItem */
		$syncedItem = $store->gObj('syncedItem');
		/** @var SyncedItemLock $syncedLock */
		$syncedLock = $store->gObj('syncedItemLock');

		$item = $this->federatedSyncService->initSyncManager($syncedItem)
										   ->serializeItem($syncedItem->getItemId());
		$syncedItem->setSerialized($item);

		$this->updateChecksum($syncedItem);
		$this->broadcastItemUpdate($syncedItem);

		$this->removeLock($syncedLock);
	}


	/**
	 * @param Circle $circle
	 * @param SyncedItem $syncedItem
	 *
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws UnknownRemoteException
	 */
	private function broadcastItemUpdate(SyncedItem $item): void {
		$item->setSerialized();

		foreach ($this->getAffectedCircles($item->getSingleId()) as $circle) {
			$event = new FederatedEvent(ItemUpdate::class);
			$event->setCircle($circle)
				  ->setSyncedItem($item);

			$this->debugService->info(
				'generating {`IFederatedEvent} using {event.class}',
				$circle->getSingleId(),
				[
					'event' => $event,
					'syncedItem' => $item,
					'circle' => $circle
				]
			);

			// TODO: confirm there is no re-async as we are already on a // thread (even with multiple circles)
			$this->federatedEventService->newEvent($event);
		}
	}


	/**
	 * @param string $singleId
	 *
	 * @return Circle[]
	 * @throws RequestBuilderException
	 */
	private function getAffectedCircles(string $singleId): array {
		$circleIds = array_map(
			function (SyncedShare $share): string {
				return $share->getCircleId();
			}, $this->syncedShareRequest->getshares($singleId)
		);

		return $this->circleRequest->getCirclesByIds($circleIds);
	}


	/**
	 * @param SyncedItem $syncedItem
	 */
	private function updateChecksum(SyncedItem $syncedItem): void {
		$sum = md5(json_encode($syncedItem->getSerialized()));

		$this->syncedItemRequest->updateChecksum($syncedItem->getSingleId(), $sum);
	}


	/**
	 * @param SyncedItemLock $syncedLock
	 */
	private function removeLock(SyncedItemLock $syncedLock): void {
		$this->syncedItemLockRequest->remove($syncedLock);
	}
}
