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

use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Db\RemoteRequest;
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
use OCA\Circles\Exceptions\SyncedSharedAlreadyExistException;
use OCA\Circles\Exceptions\SyncedShareNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\FederatedItems\FederatedSync\ShareCreation;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\SyncedItem;
use OCA\Circles\Model\SyncedShare;
use OCA\Circles\Tools\ActivityPub\NCSignature;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Tools\Traits\TStringTools;

class FederatedSyncShareService extends NCSignature {
	use TStringTools;

	private MemberRequest $memberRequest;
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
		MemberRequest $memberRequest,
		SyncedItemRequest $syncedItemRequest,
		SyncedShareRequest $syncedShareRequest,
		RemoteRequest $remoteRequest,
		FederatedSyncService $federatedSyncService,
		FederatedEventService $federatedEventService,
		RemoteStreamService $remoteStreamService,
		InterfaceService $interfaceService,
		DebugService $debugService
	) {
		$this->memberRequest = $memberRequest;
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
	 * search for existing shares in circles_share based on itemSingleId, circleId.
	 *
	 * @param SyncedItem $syncedItem
	 * @param Circle $circle
	 * @param array $extraData
	 *
	 * @throws SyncedSharedAlreadyExistException
	 * @throws FederatedSyncManagerNotFoundException
	 */
	public function createShare(SyncedItem $syncedItem, Circle $circle, array $extraData = []) {
		if (!$this->isShareCreatable($syncedItem, $circle, $extraData)) {
			$this->debugService->info(
				'share of SyncedItem {!syncedItem.singleId} to {!circle.id} is set as not creatable by {!syncedItem.appId}',
				$circle->getSingleId(),
				[
					'syncedItem' => $syncedItem,
					'circle' => $circle,
					'extraData' => $extraData
				]
			);
		}

		try {
			$this->syncedItemRequest->getSyncedItemFromSingleId($syncedItem->getSingleId());
		} catch (SyncedItemNotFoundException $e) {
			$this->debugService->info(
				'storing SyncedItem {syncedItem.singleId} in database', '',
				['syncedItem' => $syncedItem]
			);

			$this->syncedItemRequest->save($syncedItem);
		}

		$this->syncShareCreation($syncedItem, $circle, $extraData);
		$this->broadcastShareCreation($syncedItem, $circle, $extraData);

		$this->debugService->info(
			'SyncedShare created and FederatedSync is on its way; {~end of main process}'
		);
	}


	/**
	 * @param SyncedItem $syncedItem
	 * @param Circle $circle
	 * @param array $extraData
	 *
	 * @throws FederatedSyncManagerNotFoundException
	 */
	public function syncShareCreation(SyncedItem $syncedItem, Circle $circle, array $extraData = []): void {
		$syncedShare = new SyncedShare();
		$syncedShare->setSingleId($syncedItem->getSingleId())
					->setCircleId($circle->getSingleId());

		$syncManager = $this->federatedSyncService->initSyncManager($syncedItem);

		$federatedUser = new FederatedUser();
		$federatedUser->importFromIFederatedUser($circle->getInitiator());

		$this->debugService->info(
			'calling {`onShareCreation()} on {syncManager}',
			$syncedShare->getCircleId(),
			[
				'syncManager' => get_class($syncManager),
				'syncedItem' => $syncedItem,
				'syncedShare' => $syncedShare,
				'extraData' => $extraData,
				'initiator' => $circle->getInitiator(),
				'federatedUser' => $federatedUser
			]
		);

		$syncManager->onShareCreation(
			$syncedItem->getItemId(),
			$syncedShare->getCircleId(),
			$extraData,
			$federatedUser
		);

		$this->syncedShareRequest->save($syncedShare);
		$this->debugService->info(
			'storing SyncedShare of {syncedShare.singleId} to {syncedShare.circleId} in database',
			$circle->getSingleId(),
			[
				'syncedItem' => $syncedItem,
				'circle' => $circle,
				'syncedShare' => $syncedShare
			]
		);
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
	private function broadcastShareCreation(
		SyncedItem $syncedItem,
		Circle $circle,
		array $extraData = []
	): void {
		$event = new FederatedEvent(ShareCreation::class);
		$event->setCircle($circle)
			  ->setSyncedItem($syncedItem)
			  ->setParams(new SimpleDataStore(['extraData' => $extraData]));

		$this->debugService->info(
			'generating {`IFederatedEvent} using {event.class}',
			$circle->getSingleId(),
			[
				'event' => $event,
				'syncedItem' => $syncedItem,
				'circle' => $circle
			]
		);

		$this->federatedEventService->newEvent($event);
	}


	/**
	 * verify that:
	 *
	 * - SyncedShare is not already known
	 * - app agree on creating this share
	 *
	 * @param SyncedItem $syncedItem
	 * @param Circle $circle
	 * @param array $extraData
	 *
	 * @return bool
	 * @throws FederatedSyncManagerNotFoundException
	 * @throws SyncedSharedAlreadyExistException
	 */
	private function isShareCreatable(SyncedItem $syncedItem, Circle $circle, array $extraData = []): bool {
		try {
			$this->syncedShareRequest->getShare($syncedItem->getSingleId(), $circle->getsingleId());
			throw new SyncedSharedAlreadyExistException('share already exists');
		} catch (SyncedShareNotFoundException $e) {
		}


		$syncManager = $this->federatedSyncService->initSyncManager($syncedItem);
		$this->debugService->info(
			'sharing of SyncedItem {syncedItem.singleId} looks doable, calling {`isShareCreatable()} on {syncManager.class} for confirmation',
			$circle->getSingleId(),
			[
				'syncedItem' => $syncedItem,
				'syncManager' => ['class' => get_class($syncManager)]
			]
		);

		return $syncManager->isShareCreatable(
			$syncedItem->getItemId(),
			$circle->getSingleId(),
			$extraData,
			$circle->getInitiator()->getInheritedBy()
		);
	}


	/**
	 * @param string $syncedId
	 * @param string $instance
	 *
	 * @throws RequestBuilderException
	 * @throws SyncedShareNotFoundException
	 */
	public function confirmRemoteInstanceAccess(string $syncedId, string $instance): void {
		$circleIds = array_values(
			array_map(
				function (SyncedShare $share): string {
					return $share->getCircleId();
				},
				$this->syncedShareRequest->getShares($syncedId)
			)
		);

		// might look nasty to use memberRequest instead membershipRequest, but it is easier this way
		// as we are only interested in direct membership to the current circle.
		// federated circles only works as root, so sub-circles cannot spread on multiple instances.
		$links = $this->memberRequest->getLinksWithInstance($instance, $circleIds);
		$this->debugService->info(
			'SyncedItem {singleId} is shared to ' . count($circleIds) . ' circles', '',
			[
				'syncedId' => $syncedId,
				'circleIds' => $circleIds,
				'links' => $links
			]
		);

		if (empty($links)) {
			throw new SyncedShareNotFoundException('instance have no access to this item');
		}
	}

}
