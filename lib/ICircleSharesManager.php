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


namespace OCA\Circles;

/**
 * Interface IShareManager
 *
 * @package OCA\Circles
 */
interface ICircleSharesManager {

	/**
	 * Register a IFederatedSyncManager
	 *
	 * This is the first step to set up in `Application.php` of any app willing to use the
	 * FederatedItem feature:
	 *
	 *    public function boot(IBootContext $context): void {
	 *        $circleManager = $context->getAppContainer()->get(CirclesManager::class);
	 *        $circleManager->getShareManager()
	 *                      ->registerFederatedSyncManager(TestFederatedSync::class);
	 *    }
	 *
	 * @param string $syncManager the class that implemented IFederatedSyncManager
	 */
	public function registerFederatedSyncManager(string $syncManager): void;

	/**
	 * Initiate a share of an item to a circle.
	 * Your app can add some extraData about the share that can be needed during the process
	 *
	 * @param string $itemId
	 * @param string $circleId
	 * @param array $extraData
	 */
	public function createShare(string $itemId, string $circleId, array $extraData = []): void;

	/**
	 * Initiate an update on a share. (ie. permissions)
	 * Your app can add some extraData about the share that can be needed during the process
	 *
	 * @param string $itemId
	 * @param string $circleId
	 * @param array $extraData
	 */
	public function updateShare(string $itemId, string $circleId, array $extraData = []): void;

	/**
	 * Initiate the deletion of a share
	 *
	 * @param string $itemId
	 * @param string $circleId
	 */
	public function deleteShare(string $itemId, string $circleId): void;

	/**
	 * Initiate the update of a share
	 * $serializedData contains the serialized data of the item that will be used during the process by your
	 * app to update that content in its table
	 *
	 * @param string $itemId
	 * @param array $extraData
	 */
	public function updateItem(
		string $itemId,
		string $updateType,
		string $updateTypeId,
		array $extraData,
		bool $sumCheck
	): void;

	/**
	 * Initiate the deletion of an Item
	 *
	 * @param string $itemId
	 */
	public function deleteItem(string $itemId): void;
}
