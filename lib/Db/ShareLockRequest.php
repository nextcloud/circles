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

use OCA\Circles\Exceptions\FederatedShareNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Model\Federated\FederatedShare;

/**
 * Class ShareRequest
 *
 * @package OCA\Circles\Db
 */
class ShareLockRequest extends ShareLockRequestBuilder {
	/**
	 * @param FederatedShare $share
	 *
	 * @throws InvalidIdException
	 */
	public function save(FederatedShare $share): void {
		$this->confirmValidIds([$share->getItemId()]);

		$qb = $this->getShareLockInsertSql();
		$qb->setValue('item_id', $qb->createNamedParameter($share->getItemId()))
		   ->setValue('circle_id', $qb->createNamedParameter($share->getCircleId()))
		   ->setValue('instance', $qb->createNamedParameter($qb->getInstance($share)));

		$qb->execute();
	}


	/**
	 * @param string $itemId
	 * @param string $circleId
	 *
	 * @return FederatedShare
	 * @throws FederatedShareNotFoundException
	 */
	public function getShare(string $itemId, string $circleId = ''): FederatedShare {
		$qb = $this->getShareLockSelectSql();

		$qb->limitToItemId($itemId);
		if ($circleId !== '') {
			$qb->limitToCircleId($circleId);
		}

		return $this->getItemFromRequest($qb);
	}
}
