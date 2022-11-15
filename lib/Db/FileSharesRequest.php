<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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

use OCA\Circles\Model\DeprecatedMember;

/**
 * @deprecated
 * Class SharesRequest
 *
 * @package OCA\Circles\Db
 */
class FileSharesRequest extends FileSharesRequestBuilder {
	/**
	 * remove shares from a member to a circle
	 *
	 * @param DeprecatedMember $member
	 */
	public function removeSharesFromMember(DeprecatedMember $member): void {
		$qb = $this->getFileSharesDeleteSql();
		$expr = $qb->expr();

		$andX = $expr->andX();
		$andX->add($expr->eq('share_type', $qb->createNamedParameter(self::SHARE_TYPE)));
		$andX->add($expr->eq('share_with', $qb->createNamedParameter($member->getCircleId())));
		$andX->add($expr->eq('uid_initiator', $qb->createNamedParameter($member->getUserId())));
		$qb->andWhere($andX);

		$qb->execute();
	}


	/**
	 * @param string $circleId
	 */
	public function removeSharesToCircleId(string $circleId): void {
		$qb = $this->getFileSharesDeleteSql();
		$expr = $qb->expr();

		$andX = $expr->andX();
		$andX->add($expr->eq('share_type', $qb->createNamedParameter(self::SHARE_TYPE)));
		$andX->add($expr->eq('share_with', $qb->createNamedParameter($circleId)));
		$qb->andWhere($andX);

		$qb->execute();
	}


	/**
	 * @param string $circleId
	 *
	 * @return array
	 */
	public function getSharesForCircle(string $circleId): array {
		$qb = $this->getFileSharesSelectSql();

		$this->limitToShareWith($qb, $circleId);
		$this->limitToShareType($qb, self::SHARE_TYPE);

		$shares = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$shares[] = $data;
		}
		$cursor->closeCursor();

		return $shares;
	}


	/**
	 * @return array
	 */
	public function getShares(): array {
		$qb = $this->getFileSharesSelectSql();

		$expr = $qb->expr();

		$this->limitToShareType($qb, self::SHARE_TYPE);
		$qb->andWhere($expr->isNull($this->default_select_alias . '.parent'));

		$shares = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$shares[] = $data;
		}
		$cursor->closeCursor();

		return $shares;
	}
}
