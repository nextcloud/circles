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

use JsonSerializable;
use OCA\Circles\Exceptions\SyncedItemNotFoundException;
use OCA\Circles\Model\FederatedUser;

/**
 * Interface IFederatedSyncManager
 *
 * @package OCA\Circles
 */
interface IFederatedSyncManager {


	/**
	 * The string that id the app, must be unique.
	 *
	 * @return string
	 */
	public function getAppId(): string;

	/**
	 * The same app can manage FederatedItem on multiple types of items.
	 * If this is the case, your app will need to register as many IFederatedSyncManager than types
	 * of items to be managed.
	 *
	 * Each IFederatedSyncManager will use a different string to identify each type of items.
	 *
	 * @return string
	 */
	public function getItemType(): string;

	/**
	 * because there can be exchange between different version of your app you can keep trace of
	 * used version
	 *
	 * @return int
	 */
	public function getApiVersion(): int;

	/**
	 * limit to exchange only with Api that are equal or above this value
	 *
	 * @return int
	 */
	public function getApiLowerBackCompatibility(): int;

	/**
	 * return true if FullSupport
	 *
	 * @return bool
	 */
	public function isFullSupport(): bool;


	/**
	 * The method is called during data synchronisation, to serialize an item.
	 * Your app need to return the item as an array based on itemId
	 *
	 * @param string $itemId
	 *
	 * @return array
	 * @throws SyncedItemNotFoundException
	 */
	public function serializeItem(string $itemId): array;


	/**
	 * This method is called when data with different checksum is received from the instance that created the
	 * shared item.
	 * $serializedData is the array returned by serializeItem(itemId).
	 *
	 * Your app needs to update the information related to the item identified by itemId in its own
	 * table.
	 *
	 * IMPORTANT: If itemId is contained within the serialized data of an item, your app needs to
	 * compare $itemId with the itemId stored within the $serializedData
	 *
	 * @param string $itemId
	 * @param array $serializedData
	 */
	public function syncItem(string $itemId, array $serializedData): void;


	/**
	 * Your app returns details about a share.
	 * Method will be called to re-sync a share on a remote instance
	 *
	 * @param string $itemId
	 * @param string $circleId
	 *
	 * @return array
	 */
	public function getShareDetails(string $itemId, string $circleId): array;

	/**
	 * Force an update of the share, based on the $extraData returned by getShareDetails()
	 *
	 * @param string $itemId
	 * @param string $circleId
	 * @param array $extraData
	 */
	public function syncShare(string $itemId, string $circleId, array $extraData): void;


	/**
	 * Your app returns if the share is creatable at that point.
	 * Method is only called on the instance that owns the shared item
	 *
	 * @param string $itemId
	 * @param string $circleId
	 * @param array $extraData
	 * @param FederatedUser $federatedUser
	 *
	 * @return bool
	 */
	public function isShareCreatable(
		string $itemId,
		string $circleId,
		array $extraData,
		IFederatedUser $federatedUser
	): bool;


	/**
	 * Is called when the share looks valid.
	 * Method is called on every instance.
	 *
	 * In this method, your app needs to create its own entries in its table regarding the new share
	 *
	 * $extraData is the array sent when your app initiated the process with
	 * ICircleSharesManager::createShare(itemId, circleId, extraData);
	 *
	 * Note: In case of isFullSupport() returns false, it will not be executed on the instance that owns the
	 * item.
	 *
	 * $membership contains enough data about the author of the share for your app to generate its
	 * own event (activity, mail, ...)
	 *
	 * @param string $itemId
	 * @param string $circleId
	 * @param array $extraData
	 * @param FederatedUser $federatedUser
	 */
	public function onShareCreation(
		string $itemId,
		string $circleId,
		array $extraData,
		IFederatedUser $federatedUser
	): void;


	/**
	 * Your app returns if the share is modifiable at that point.
	 * Method is only called on the instance that owns the shared item
	 *
	 * @param string $itemId
	 * @param string $circleId
	 * @param array $extraData
	 * @param IFederatedUser $federatedUser
	 *
	 * @return bool
	 */
	public function isShareModifiable(
		string $itemId,
		string $circleId,
		array $extraData,
		IFederatedUser $federatedUser
	): bool;


	/**
	 * Is called when the share looks valid.
	 * Method is called on every instance.
	 *
	 * In this method, your app needs to update its own entries in its table the modified share
	 *
	 * $extraData is the array sent when your app initiated the process with
	 * ICircleSharesManager::updateShare(itemId, circleId, extraData);
	 *
	 * Note: In case of $fullSupport===false when registering the IFederateShareManager, it will not be
	 * executed on the instance that owns the item.
	 *
	 * $membership contains enough data about the author of the share for your app to generate its
	 * own event (activity, mail, ...)
	 *
	 * @param string $itemId
	 * @param string $circleId
	 * @param array $extraData
	 * @param IFederatedUser $federatedUser
	 */
	public function onShareModification(
		string $itemId,
		string $circleId,
		array $extraData,
		IFederatedUser $federatedUser
	): void;


	/**
	 * Your app returns if the share is deletable at that point.
	 * Method is only called on the instance that owns the shared item
	 *
	 * @param string $itemId
	 * @param string $circleId
	 * @param IFederatedUser $federatedUser
	 *
	 * @return bool
	 */
	public function isShareDeletable(
		string $itemId,
		string $circleId,
		IFederatedUser $federatedUser
	): bool;


	/**
	 * Is called when the share is supposed to be deleted.
	 * Method is called on every instance.
	 *
	 * In this method, your app needs to delete its own entries in its table about the deleted share
	 *
	 * Note: In case of $fullSupport===false when registering the IFederateShareManager, it will not be
	 * executed on the instance that owns the item.
	 *
	 * $membership contains enough data about the author of the share for your app to generate its
	 * own event (activity, mail, ...)
	 *
	 * @param string $itemId
	 * @param string $circleId
	 * @param IFederatedUser $federatedUser
	 */
	public function onShareDeletion(
		string $itemId,
		string $circleId,
		IFederatedUser $federatedUser
	): void;


	/**
	 * Confirm that partial update is doable, throw Exception if not.
	 * Must returns the serialized data of the future version of the Item.
	 *
	 * Your app should not update anything in database at this point.
	 *
	 * The serialized data will be used on the next call of syncItem() and store during this next step.
	 *
	 * Membership of $federatedUser can go through multiple paths as the same item can be shared to different
	 * circles $federatedUser is a member. Meaning multiple permissions needs to be checked.
	 *
	 * Method is only called on the instance that owns the shared item
	 *
	 * @param string $itemId
	 * @param array $extraData
	 * @param FederatedUser $federatedUser
	 *
	 * @return array
	 */
	public function isItemUpdatable(
		string $itemId,
		array $extraData,
		IFederatedUser $federatedUser
	): array;

}
