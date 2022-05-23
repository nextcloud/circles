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


namespace OCA\Circles\FederatedItems\FederatedSync;

use OCA\Circles\Db\SyncedItemRequest;
use OCA\Circles\Exceptions\FederatedSyncConflictException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SyncedItemNotFoundException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemAsyncProcess;
use OCA\Circles\IFederatedItemHighSeverity;
use OCA\Circles\IFederatedItemLimitedToInstanceWithMember;
use OCA\Circles\IFederatedItemSyncedItem;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\SyncedItem;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\DebugService;
use OCA\Circles\Service\FederatedSyncItemService;
use OCA\Circles\Service\FederatedSyncShareService;
use OCA\Circles\Tools\Traits\TDeserialize;


class ShareCreation implements
	IFederatedItem,
	IFederatedItemLimitedToInstanceWithMember,
	IFederatedItemHighSeverity,
	IFederatedItemAsyncProcess,
	IFederatedItemSyncedItem {
	use TDeserialize;

	private SyncedItemRequest $syncedItemRequest;
	private FederatedSyncItemService $federatedSyncItemService;
	private FederatedSyncShareService $federatedSyncShareService;
	private ConfigService $configService;
	private DebugService $debugService;


	/**
	 * @param SyncedItemRequest $syncedItemRequest
	 * @param FederatedSyncItemService $federatedSyncItemService
	 * @param FederatedSyncShareService $federatedSyncShareService
	 * @param ConfigService $configService
	 * @param DebugService $debugService
	 */
	public function __construct(
		SyncedItemRequest $syncedItemRequest,
		FederatedSyncItemService $federatedSyncItemService,
		FederatedSyncShareService $federatedSyncShareService,
		ConfigService $configService,
		DebugService $debugService
	) {
		$this->syncedItemRequest = $syncedItemRequest;
		$this->federatedSyncItemService = $federatedSyncItemService;
		$this->federatedSyncShareService = $federatedSyncShareService;
		$this->configService = $configService;
		$this->debugService = $debugService;
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedSyncConflictException
	 */
	public function verify(FederatedEvent $event): void {
		$circle = $event->getCircle();
		$syncedItem = $event->getSyncedItem();
//		$initiator = $circle->getInitiator();

		$syncedItem->setInstance($event->getOrigin());

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
			$this->debugService->info(
				'no known syncedItem {syncedItem.singleId} were found in database, assuming this is good',
				'',
				['syncedItem' => $syncedItem]
			);
		}
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws RequestBuilderException
	 */
	public function manage(FederatedEvent $event): void {
		if ($this->configService->isLocalInstance($event->getOrigin())) {
			$this->debugService->info(
				'{`FederatedEvent} has its origin set as current instance. leaving.', '',
				['event' => $event]
			);

			return;
		}

		$circle = $event->getCircle();
		$syncedItem = $event->getSyncedItem();

		try {
			$this->compareWithKnownItemId($syncedItem);
			$this->compareWithKnownSingleId($syncedItem);
		} catch (FederatedSyncConflictException $e) {
			$this->debugService->exception(
				$e, '',
				['note' => 'WIP: this exception should start the process of fixing conflict']
			);

			return;   // TODO: manage FederatedSyncConflictException - can be done 'live' at this point
		} catch (SyncedItemNotFoundException $e) {
		}

		$extraData = $event->getParams()->gArray('extraData');

		$this->federatedSyncItemService->updateSyncedItem($syncedItem);
		$this->federatedSyncShareService->syncShareCreation($syncedItem, $circle, $extraData);
	}


	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
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

}
