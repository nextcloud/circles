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


namespace OCA\Circles;

use Exception;
use OCA\Circles\Exceptions\CircleSharesManagerException;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\DebugService;
use OCA\Circles\Service\FederatedSyncItemService;
use OCA\Circles\Service\FederatedSyncService;
use OCA\Circles\Service\FederatedSyncShareService;
use OCA\Circles\Service\FederatedUserService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class CircleSharesManager
 *
 * @package OCA\Circles
 */
class CircleSharesManager implements ICircleSharesManager {


	private CircleService $circleService;
	private FederatedUserService $federatedUserService;
	private FederatedSyncService $federatedSyncService;
	private FederatedSyncItemService $federatedSyncItemService;
	private FederatedSyncShareService $federatedSyncShareService;
	private ConfigService $configService;
	private DebugService $debugService;

	private string $originAppId = '';
	private string $originItemType = '';


	/**
	 * @param CircleService $circleService
	 * @param FederatedUserService $federatedUserService
	 * @param FederatedSyncService $federatedSyncService
	 * @param FederatedSyncItemService $federatedSyncItemService
	 * @param FederatedSyncShareService $federatedSyncShareService
	 * @param ConfigService $configService
	 * @param DebugService $debugService
	 */
	public function __construct(
		CircleService $circleService,
		FederatedUserService $federatedUserService,
		FederatedSyncService $federatedSyncService,
		FederatedSyncItemService $federatedSyncItemService,
		FederatedSyncShareService $federatedSyncShareService,
		ConfigService $configService,
		DebugService $debugService
	) {
		$this->circleService = $circleService;
		$this->federatedUserService = $federatedUserService;
		$this->federatedSyncService = $federatedSyncService;
		$this->federatedSyncItemService = $federatedSyncItemService;
		$this->federatedSyncShareService = $federatedSyncShareService;
		$this->configService = $configService;
		$this->debugService = $debugService;
	}


	/**
	 * @param string $syncManager
	 *
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function registerFederatedSyncManager(string $syncManager): void {
		if ($this->originAppId !== '' || $this->originItemType !== '') {
			return;
		}

		$federatedSyncManager = \OC::$server->get($syncManager);
		if (!($federatedSyncManager instanceof IFederatedSyncManager)) {
			// log something
			return;
		}

		$this->federatedSyncService->addFederatedSyncManager($federatedSyncManager);
	}


	/**
	 * @param string $itemId
	 * @param string $circleId
	 * @param array $extraData
	 *
	 * @throws CircleSharesManagerException
	 * @throws Exceptions\CircleNotFoundException
	 * @throws Exceptions\FederatedSyncConflictException
	 * @throws Exceptions\FederatedSyncManagerNotFoundException
	 * @throws Exceptions\InitiatorNotFoundException
	 * @throws Exceptions\RequestBuilderException
	 * @throws Exceptions\SyncedSharedAlreadyExistException
	 */
	public function createShare(
		string $itemId,
		string $circleId,
		array $extraData = []
	): void {
		$this->debugService->setDebugType('federated_sync');
		$this->debugService->info(
			'{~New request to create a SyncedShare} based on {appId}.{itemType}.{itemId}', $circleId, [
																							 'appId' => $this->originAppId,
																							 'itemType' => $this->originItemType,
																							 'itemId' => $itemId,
																							 'extraData' => $extraData
																						 ]
		);

		try {
			$this->mustHaveOrigin();

			// TODO: verify rules that apply when sharing to a circle
			$probe = new CircleProbe();
			$probe->includeSystemCircles()
				  ->mustBeMember();

			$circle = $this->circleService->getCircle($circleId, $probe);

			// get valid SyncedItem based on appId, itemType, itemId
			$syncedItem = $this->federatedSyncItemService->initSyncedItem(
				$this->originAppId,
				$this->originItemType,
				$itemId,
				true
			);

			$this->debugService->info(
				'initiating the process of sharing {syncedItem.singleId} to {circle.id}',
				$circleId, [
					'circle' => $circle,
					'syncedItem' => $syncedItem,
					'extraData' => $extraData,
					'isLocal' => $syncedItem->isLocal()
				]
			);

			// confirm item is local
			if (!$syncedItem->isLocal()) {
				// TODO: sharing a remote item
				return;
			}

			$this->federatedSyncShareService->createShare($syncedItem, $circle, $extraData);
		} catch (Exception $e) {
			$this->debugService->exception($e, $circleId);
			throw $e;
		}
	}

	/**
	 * @param string $itemId
	 * @param string $circleId
	 * @param array $extraData
	 *
	 * @throws CircleSharesManagerException
	 */
	public function updateShare(
		string $itemId,
		string $circleId,
		array $extraData = []
	): void {
	}

	/**
	 * @param string $itemId
	 * @param string $circleId
	 *
	 * @throws CircleSharesManagerException
	 */
	public function deleteShare(string $itemId, string $circleId): void {
		$this->mustHaveOrigin();
	}

	/**
	 * @param string $itemId
	 * @param array $extraData
	 *
	 * @throws CircleSharesManagerException
	 */
	public function updateItem(
		string $itemId,
		array $extraData = []
	): void {
		$this->mustHaveOrigin();

		$this->debugService->setDebugType('federated_sync');
		$this->debugService->info(
			'{~New request to update a SyncedItem} based on {appId}.{itemType}.{itemId}',
			'',
			[
				'appId' => $this->originAppId,
				'itemType' => $this->originItemType,
				'itemId' => $itemId,
				'extraData' => $extraData
			]
		);

		try {
//			$this->mustHaveOrigin();

//			// TODO: verify rules that apply when sharing to a circle
//			$probe = new CircleProbe();
//			$probe->includeSystemCircles()
//				  ->mustBeMember();
//
//			$circle = $this->circleService->getCircle($circleId, $probe);
//
			// get valid SyncedItem based on appId, itemType, itemId
			$syncedItem = $this->federatedSyncItemService->initSyncedItem(
				$this->originAppId,
				$this->originItemType,
				$itemId
			);

			$this->debugService->info(
				'initiating the process of updating {syncedItem.singleId}',
				'', [
					'itemId' => $itemId,
					'syncedItem' => $syncedItem,
					'extraData' => $extraData,
					'isLocal' => $syncedItem->isLocal()
				]
			);

			$this->federatedSyncItemService->requestSyncedItemUpdate(
				$this->federatedUserService->getCurrentEntity(),
				$syncedItem,
				$extraData
			);
		} catch (Exception $e) {
			$this->debugService->exception($e);
			throw $e;
		}
	}


	/**
	 * @param string $itemId
	 *
	 * @throws CircleSharesManagerException
	 */
	public function deleteItem(string $itemId): void {
		$this->mustHaveOrigin();
	}


	/**
	 * @param string $appId
	 * @param string $itemType
	 */
	public function setOrigin(string $appId, string $itemType) {
		$this->originAppId = $appId;
		$this->originItemType = $itemType;
	}

	/**
	 * @throws CircleSharesManagerException
	 */
	private function mustHaveOrigin(): void {
		if ($this->originAppId !== '' && $this->originItemType !== '') {
			return;
		}

		throw new CircleSharesManagerException(
			'ICirclesManager::getShareManager(appId, itemType) used empty params'
		);
	}
}
