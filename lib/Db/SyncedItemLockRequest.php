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

use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\SyncedItemNotFoundException;
use OCA\Circles\Model\SyncedItemLock;
use OCA\Circles\Tools\Exceptions\InvalidItemException;

/**
 * Class SyncedItemLockRequest
 *
 * @package OCA\Circles\Db
 */
class SyncedItemLockRequest extends SyncedItemLockRequestBuilder {


	/**
	 * @param SyncedItemLock $lock
	 *
	 * @throws InvalidIdException
	 */
	public function save(SyncedItemLock $lock): void {
		$qb = $this->getSyncedItemLockInsertSql();
		$qb->setValue('update_type', $qb->createNamedParameter($lock->getUpdateType()))
		   ->setValue('update_type_id', $qb->createNamedParameter($lock->getUpdateTypeId()))
		   ->setValue('time', $qb->createNamedParameter(time()));

		$qb->execute();
	}


	/**
	 * @param SyncedItemLock $syncedLock
	 */
	public function remove(SyncedItemLock $syncedLock): void {
		$qb = $this->getSyncedItemLockDeleteSql();

		$qb->limit('update_type', $syncedLock->getUpdateType());
		$qb->limit('update_type_id', $syncedLock->getUpdateTypeId());

		$qb->executeStatement();
	}

	/**
	 * @param SyncedItemLock $syncedLock
	 *
	 * @return SyncedItemLock
	 * @throws SyncedItemNotFoundException
	 * @throws InvalidItemException
	 */
	public function getSyncedItemLock(SyncedItemLock $syncedLock): SyncedItemLock {
		$qb = $this->getSyncedItemLockSelectSql();

		$qb->limit('update_type', $syncedLock->getUpdateType());
		$qb->limit('update_type_id', $syncedLock->getUpdateTypeId());

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @param int $time
	 */
	public function clean(int $time = 10): void {
		$qb = $this->getSyncedItemLockDeleteSql();
		$qb->lt('time', (time() - $time));

		$qb->executeStatement();
	}

}
