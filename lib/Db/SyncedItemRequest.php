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


namespace OCA\Circles\Db;

use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\SyncedItemNotFoundException;
use OCA\Circles\Model\SyncedItem;

/**
 * Class ShareRequest
 *
 * @package OCA\Circles\Db
 */
class SyncedItemRequest extends SyncedItemRequestBuilder {


	/**
	 * @param SyncedItem $item
	 *
	 * @throws InvalidIdException
	 */
	public function save(SyncedItem $item): void {
		$this->confirmValidId($item->getSingleId());

		$qb = $this->getSyncedItemInsertSql();
		$qb->setValue('single_id', $qb->createNamedParameter($item->getSingleId()))
		   ->setValue('instance', $qb->createNamedParameter($qb->getInstance($item)))
		   ->setValue('app_id', $qb->createNamedParameter($item->getAppId()))
		   ->setValue('item_type', $qb->createNamedParameter($item->getItemType()))
		   ->setValue('item_id', $qb->createNamedParameter($item->getItemId()))
		   ->setValue('checksum', $qb->createNamedParameter($item->getChecksum()))
		   ->setValue('deleted', $qb->createNamedParameter($item->isDeleted()));

		$qb->execute();
	}


	/**
	 * @param string $singleId
	 * @param string $checksum
	 */
	public function updateChecksum(string $singleId, string $checksum): void {
		$qb = $this->getSyncedItemUpdateSql();
		$qb->set('checksum', $qb->createNamedParameter($checksum));
		$qb->limitToSingleId($singleId);

		$qb->executeStatement();
	}


	/**
	 * @param string $singleId
	 *
	 * @return SyncedItem
	 * @throws SyncedItemNotFoundException
	 */
	public function getSyncedItemFromSingleId(string $singleId): SyncedItem {
		$qb = $this->getSyncedItemSelectSql();

		$qb->limitToSingleId($singleId);

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @param string $appId
	 * @param string $itemType
	 * @param string $itemId
	 *
	 * @return SyncedItem
	 * @throws SyncedItemNotFoundException
	 */
	public function getSyncedItem(string $appId, string $itemType, string $itemId): SyncedItem {
		$qb = $this->getSyncedItemSelectSql();

		$qb->limitToAppId($appId);
		$qb->limitToItemType($itemType);
		$qb->limitToItemId($itemId);

		return $this->getItemFromRequest($qb);
	}

}
