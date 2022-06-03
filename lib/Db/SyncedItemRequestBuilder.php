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


namespace OCA\Circles\Db;

use OCA\Circles\Exceptions\SyncedItemNotFoundException;
use OCA\Circles\Model\SyncedItem;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Exceptions\RowNotFoundException;

class SyncedItemRequestBuilder extends CoreRequestBuilder {


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getSyncedItemInsertSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_SYNC_ITEM);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getSyncedItemSelectSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(
			self::TABLE_SYNC_ITEM,
			self::$tables[self::TABLE_SYNC_ITEM],
			CoreQueryBuilder::SYNC_ITEM
		);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getSyncedItemUpdateSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_SYNC_ITEM);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getSyncedItemDeleteSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_SYNC_ITEM);

		return $qb;
	}


	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return SyncedItem
	 * @throws SyncedItemNotFoundException
	 */
	public function getItemFromRequest(CoreQueryBuilder $qb): SyncedItem {
		/** @var SyncedItem $item */
		try {
			$item = $qb->asItem(SyncedItem::class);
		} catch (InvalidItemException | RowNotFoundException $e) {
			throw new SyncedItemNotFoundException(get_class($e));
		}

		return $item;
	}

	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return SyncedItem[]
	 */
	public function getItemsFromRequest(CoreQueryBuilder $qb): array {
		/** @var SyncedItem[] $result */
		return $qb->asItems(SyncedItem::class);
	}
}
