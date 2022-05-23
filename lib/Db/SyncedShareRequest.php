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
use OCA\Circles\Exceptions\SyncedShareNotFoundException;
use OCA\Circles\Model\SyncedShare;
use OCP\DB\Exception;

/**
 * Class ShareRequest
 *
 * @package OCA\Circles\Db
 */
class SyncedShareRequest extends SyncedShareRequestBuilder {


	/**
	 * @param SyncedShare $share
	 *
	 * @throws InvalidIdException
	 * @throws Exception
	 */
	public function save(SyncedShare $share): void {
		$this->confirmValidIds([$share->getSingleId(), $share->getCircleId()]);

		$qb = $this->getSyncedShareInsertSql();
		$qb->setValue('single_id', $qb->createNamedParameter($share->getSingleId()))
		   ->setValue('circle_id', $qb->createNamedParameter($share->getCircleId()));

		$qb->executeStatement();
	}


	/**
	 * @param string $singleId
	 *
	 * @return SyncedShare[]
	 */
	public function getShares(string $singleId): array {
		$qb = $this->getSyncedShareSelectSql();

		$qb->limitToSingleId($singleId);

		return $this->getItemsFromRequest($qb);
	}

	/**
	 * @param string $itemSingleId
	 * @param string $circleId
	 *
	 * @return SyncedShare
	 * @throws SyncedShareNotFoundException
	 */
	public function getShare(string $itemSingleId, string $circleId): SyncedShare {
		$qb = $this->getSyncedShareSelectSql();

		$qb->limitToSingleId($itemSingleId);
		$qb->limitToCircleId($circleId);

		return $this->getItemFromRequest($qb);
	}

}
