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

use OCA\Circles\Tools\Exceptions\RowNotFoundException;
use OCA\Circles\Exceptions\FederatedShareNotFoundException;
use OCA\Circles\Model\Federated\FederatedShare;

/**
 * Class ShareRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class ShareLockRequestBuilder extends CoreRequestBuilder {
	/**
	 * @return CoreQueryBuilder
	 */
	protected function getShareLockInsertSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_SHARE_LOCK);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getShareLockSelectSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();

		$qb->select('s.id', 's.item_id', 's.circle_id', 's.instance')
		   ->from(self::TABLE_SHARE_LOCK, 's')
		   ->setDefaultSelectAlias('s');

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getShareLockUpdateSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_SHARE_LOCK);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getShareDeleteSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_SHARE_LOCK);

		return $qb;
	}


	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return FederatedShare
	 * @throws FederatedShareNotFoundException
	 */
	public function getItemFromRequest(CoreQueryBuilder $qb): FederatedShare {
		/** @var FederatedShare $circle */
		try {
			$circle = $qb->asItem(FederatedShare::class);
		} catch (RowNotFoundException $e) {
			throw new FederatedShareNotFoundException();
		}

		return $circle;
	}

	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return FederatedShare[]
	 */
	public function getItemsFromRequest(CoreQueryBuilder $qb): array {
		/** @var FederatedShare[] $result */
		return $qb->asItems(FederatedShare::class);
	}
}
