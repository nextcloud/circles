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
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemAsyncProcess;
use OCA\Circles\IFederatedItemHighSeverity;
use OCA\Circles\IFederatedItemInitiatorCheckNotRequired;
use OCA\Circles\IFederatedItemLimitedToInstanceWithMember;
use OCA\Circles\IFederatedItemSyncedItem;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\FederatedUser;
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
	IFederatedItemInitiatorCheckNotRequired,
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
		$syncedItem = $event->getSyncedItem();

		$syncedItem->setInstance($event->getOrigin());
		$this->federatedSyncItemService->compareWithKnownItem($syncedItem, true);
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
		if (!$circle->hasInitiator()) {
			$this->debugService->info(
				'{?Initiator not available}', '',
				['circle' => $circle]
			);

			return;
		}

		// note that we have no confirmation about the origin and/or memberships of FederatedUser at
		// this point as this class implements IFederatedItemInitiatorCheckNotRequired
		// the only use of this IFederatedItem is to broadcast an eventual new share which needs
		// to be confirmed with a direct request to the instance that owns the shared item
		$federatedUser = new FederatedUser();
		$federatedUser->importFromIFederatedUser($circle->getInitiator());
		$syncedItem = $event->getSyncedItem();

		$this->federatedSyncItemService->compareWithKnownItem($syncedItem, true);

		$extraData = $event->getParams()->gArray('extraData');

		$this->federatedSyncItemService->updateSyncedItem($syncedItem);
		$this->federatedSyncShareService->syncShareCreation(
			$federatedUser,
			$syncedItem,
			$circle->getSingleId(),
			$extraData
		);
	}


	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
	}

}
