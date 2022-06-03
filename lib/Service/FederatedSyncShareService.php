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

use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Db\MembershipRequest;
use OCA\Circles\Db\RemoteRequest;
use OCA\Circles\Db\SyncedItemRequest;
use OCA\Circles\Db\SyncedShareRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedSyncConflictException;
use OCA\Circles\Exceptions\FederatedSyncManagerNotFoundException;
use OCA\Circles\Exceptions\FederatedSyncPermissionException;
use OCA\Circles\Exceptions\FederatedSyncRequestException;
use OCA\Circles\Exceptions\InitiatorNotConfirmedException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MembershipNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SyncedSharedAlreadyExistException;
use OCA\Circles\Exceptions\SyncedShareNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\FederatedItems\FederatedSync\ShareCreation;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Model\SyncedItem;
use OCA\Circles\Model\SyncedShare;
use OCA\Circles\Model\SyncedWrapper;
use OCA\Circles\Tools\ActivityPub\NCSignature;
use OCA\Circles\Tools\Model\Request;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Tools\Traits\TStringTools;

class FederatedSyncShareService extends NCSignature {
	use TStringTools;

	private CircleRequest $circleRequest;
	private MemberRequest $memberRequest;
	private MembershipRequest $membershipRequest;
	private SyncedItemRequest $syncedItemRequest;
	private SyncedShareRequest $syncedShareRequest;
	private RemoteRequest $remoteRequest;
	private FederatedSyncService $federatedSyncService;
	private FederatedEventService $federatedEventService;
	private RemoteStreamService $remoteStreamService;
	private InterfaceService $interfaceService;
	private DebugService $debugService;


	/**
	 * @param CircleRequest $circleRequest
	 * @param MemberRequest $memberRequest
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
		CircleRequest $circleRequest,
		MemberRequest $memberRequest,
		MembershipRequest $membershipRequest,
		SyncedItemRequest $syncedItemRequest,
		SyncedShareRequest $syncedShareRequest,
		RemoteRequest $remoteRequest,
		FederatedSyncService $federatedSyncService,
		FederatedEventService $federatedEventService,
		RemoteStreamService $remoteStreamService,
		InterfaceService $interfaceService,
		DebugService $debugService
	) {
		$this->circleRequest = $circleRequest;
		$this->memberRequest = $memberRequest;
		$this->membershipRequest = $membershipRequest;
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
	 * @param IFederatedUser $federatedUser
	 * @param SyncedItem $syncedItem
	 * @param string $circleId
	 * @param array $extraData
	 * @param bool $fromRemote
	 *
	 * @throws CircleNotFoundException
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws FederatedSyncConflictException
	 * @throws FederatedSyncManagerNotFoundException
	 * @throws FederatedSyncRequestException
	 * @throws InitiatorNotConfirmedException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws SyncedSharedAlreadyExistException
	 * @throws UnknownRemoteException
	 */
	public function requestSyncedShareCreation(
		IFederatedUser $federatedUser,
		SyncedItem $syncedItem,
		string $circleId,
		array $extraData = [],
		bool $fromRemote = false
	): void {
		if ($syncedItem->isLocal()) {
			$this->requestSyncedShareCreationLocal($federatedUser, $syncedItem, $circleId, $extraData);

			return;
		} else if (!$fromRemote) {
			$this->requestSyncedShareCreationRemote($federatedUser, $syncedItem, $circleId, $extraData);

			return;
		}

		throw new FederatedSyncConflictException();
	}


	/**
	 * @throws FederatedSyncManagerNotFoundException
	 * @throws SyncedSharedAlreadyExistException
	 * @throws CircleNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RemoteInstanceException
	 * @throws OwnerNotFoundException
	 * @throws InitiatorNotConfirmedException
	 * @throws FederatedItemException
	 * @throws RequestBuilderException
	 * @throws FederatedEventException
	 * @throws RemoteNotFoundException
	 * @throws UnknownRemoteException
	 */
	public function requestSyncedShareCreationLocal(
		IFederatedUser $federatedUser,
		SyncedItem $syncedItem,
		string $circleId,
		array $extraData = []
	): void {

		// TODO: verify rules that apply when sharing to a circle
		$probe = new CircleProbe();
		$probe->includeSystemCircles()
			  ->mustBeMember();
		$circle = $this->circleRequest->getCircle($circleId, $federatedUser, $probe);

		if (!$this->isShareCreatable($federatedUser, $syncedItem, $circleId, $extraData)) {
			$this->debugService->info(
				'share of SyncedItem {!syncedItem.singleId} to {!circleId} is set as not creatable by {!syncedItem.appId}',
				$circleId,
				[
					'syncedItem' => $syncedItem,
					'circleId' => $circleId,
					'extraData' => $extraData
				]
			);
		}

		$this->syncShareCreation($federatedUser, $syncedItem, $circleId, $extraData);
		$this->broadcastShareCreation($syncedItem, $circle, $extraData);

		$this->debugService->info(
			'SyncedShare created and FederatedSync is on its way; {~end of main process}'
		);
	}


	private function requestSyncedShareCreationRemote(
		IFederatedUser $federatedUser,
		SyncedItem $syncedItem,
		string $circleId,
		array $extraData = []
	): void {
		$syncedShare = new SyncedShare();
		$syncedShare->setSingleId($syncedItem->getSingleId())
					->setCircleId($circleId);

		$wrapper = new SyncedWrapper($federatedUser, null, null, $syncedShare, $extraData);
		$this->interfaceService->setCurrentInterfaceFromInstance($syncedItem->getInstance());
		$data = $this->remoteStreamService->resultRequestRemoteInstance(
			$syncedItem->getInstance(),
			RemoteInstance::SYNC_SHARE,
			Request::TYPE_PUT,
			$wrapper
		);

		if (!$this->getBool('success', $data)) {
			throw new FederatedSyncRequestException();
		}

		$syncManager = $this->federatedSyncService->initSyncManager($syncedItem);
		$syncManager->onShareCreation(
			$syncedItem->getItemId(),
			$syncedShare->getCircleId(),
			$extraData,
			$federatedUser
		);
	}

	/**
	 * @param IFederatedUser $federatedUser
	 * @param SyncedItem $syncedItem
	 * @param string $circleId
	 * @param array $extraData
	 *
	 * @throws FederatedSyncManagerNotFoundException
	 * @throws InvalidIdException
	 */
	public function syncShareCreation(
		IFederatedUser $federatedUser,
		SyncedItem $syncedItem,
		string $circleId,
		array $extraData = []
	): void {
		$syncedShare = new SyncedShare();
		$syncedShare->setSingleId($syncedItem->getSingleId())
					->setCircleId($circleId);

		$syncManager = $this->federatedSyncService->initSyncManager($syncedItem);

		$this->debugService->info(
			'calling {`onShareCreation()} on {syncManager}',
			$syncedShare->getCircleId(),
			[
				'syncManager' => get_class($syncManager),
				'syncedItem' => $syncedItem,
				'syncedShare' => $syncedShare,
				'extraData' => $extraData,
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
			$circleId,
			[
				'syncedItem' => $syncedItem,
				'circleId' => $circleId,
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
	 * @param IFederatedUser $federatedUser
	 * @param SyncedItem $syncedItem
	 * @param string $circleId
	 * @param array $extraData
	 *
	 * @return bool
	 * @throws FederatedSyncManagerNotFoundException
	 * @throws FederatedSyncPermissionException
	 * @throws SyncedSharedAlreadyExistException
	 */
	private function isShareCreatable(
		IFederatedUser $federatedUser,
		SyncedItem $syncedItem,
		string $circleId,
		array $extraData = []
	): bool {
		try {
			$this->syncedShareRequest->getShare($syncedItem->getSingleId(), $circleId);
			throw new SyncedSharedAlreadyExistException('share already exists');
		} catch (SyncedShareNotFoundException $e) {
		}

		$syncManager = $this->federatedSyncService->initSyncManager($syncedItem);
		$ownerId = $syncManager->getOwner($syncedItem->getItemId());
		try {
			$member = $this->membershipRequest->getMembership($circleId, $ownerId);
		} catch (MembershipNotFoundException $e) {
			throw new FederatedSyncPermissionException('owner of Item is not member of Circle');
		}

		$this->debugService->info(
			'sharing of SyncedItem {syncedItem.singleId} looks doable, calling {`isShareCreatable()} on {syncManager.class} for confirmation',
			$circleId,
			[
				'syncedItem' => $syncedItem,
				'syncManager' => ['class' => get_class($syncManager)]
			]
		);

		return $syncManager->isShareCreatable(
			$syncedItem->getItemId(),
			$circleId,
			$extraData,
			$federatedUser
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
