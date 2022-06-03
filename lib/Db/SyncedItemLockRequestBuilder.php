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
use OCA\Circles\Model\SyncedItemLock;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Exceptions\RowNotFoundException;

class SyncedItemLockRequestBuilder extends CoreRequestBuilder {


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getSyncedItemLockInsertSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_SYNC_LOCK);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getSyncedItemLockSelectSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(
			self::TABLE_SYNC_LOCK,
			self::$tables[self::TABLE_SYNC_LOCK],
			CoreQueryBuilder::SYNC_LOCK
		);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getSyncedItemLockUpdateSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_SYNC_LOCK);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getSyncedItemLockDeleteSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_SYNC_LOCK);

		return $qb;
	}


	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return SyncedItemLock
	 * @throws InvalidItemException
	 * @throws SyncedItemNotFoundException
	 */
	public function getItemFromRequest(CoreQueryBuilder $qb): SyncedItemLock {
		/** @var SyncedItemLock $lock */
		try {
			$lock = $qb->asItem(SyncedItemLock::class);
		} catch (RowNotFoundException $e) {
			throw new SyncedItemNotFoundException();
		}

		return $lock;
	}

	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return SyncedItemLock[]
	 */
	public function getItemsFromRequest(CoreQueryBuilder $qb): array {
		/** @var SyncedItemLock[] $result */
		return $qb->asItems(SyncedItemLock::class);
	}
}
