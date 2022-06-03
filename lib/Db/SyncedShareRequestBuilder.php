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

use OCA\Circles\Exceptions\SyncedShareNotFoundException;
use OCA\Circles\Model\SyncedShare;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Exceptions\RowNotFoundException;

/**
 * Class ShareRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class SyncedShareRequestBuilder extends CoreRequestBuilder {


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getSyncedShareInsertSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_SYNC_SHARE);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getSyncedShareSelectSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(
			self::TABLE_SYNC_SHARE,
			self::$tables[self::TABLE_SYNC_SHARE],
			CoreQueryBuilder::SYNC_SHARE
		);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getSyncedShareUpdateSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_SYNC_SHARE);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getSyncedShareDeleteSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_SYNC_SHARE);

		return $qb;
	}


	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return SyncedShare
	 * @throws SyncedShareNotFoundException
	 */
	public function getItemFromRequest(CoreQueryBuilder $qb): SyncedShare {
		/** @var SyncedShare $lock */
		try {
			$lock = $qb->asItem(SyncedShare::class);
		} catch (RowNotFoundException | InvalidItemException $e) {
			throw new SyncedShareNotFoundException();
		}

		return $lock;
	}

	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return SyncedShare[]
	 */
	public function getItemsFromRequest(CoreQueryBuilder $qb): array {
		/** @var SyncedShare[] $result */
		return $qb->asItems(SyncedShare::class);
	}
}
