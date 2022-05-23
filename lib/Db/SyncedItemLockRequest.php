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
use OCA\Circles\Model\SyncedItemLock;

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
		$this->confirmValidIds([$lock->getSingleId()]);

		$qb = $this->getSyncedItemLockInsertSql();
		$qb->setValue('single_id', $qb->createNamedParameter($lock->getSingleId()))
		   ->setValue('update_type', $qb->createNamedParameter($lock->getUpdateType()))
		   ->setValue('update_type_id', $qb->createNamedParameter($lock->getUpdateTypeId()))
		   ->setValue('time', $qb->createNamedParameter($lock->getTime()));

		$qb->execute();
	}
}
