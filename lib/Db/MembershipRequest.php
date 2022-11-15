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

use OCA\Circles\Exceptions\MembershipNotFoundException;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Membership;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class MembershipRequest
 *
 * @package OCA\Circles\Db
 */
class MembershipRequest extends MembershipRequestBuilder {
	/**
	 * @param Membership $membership
	 */
	public function insert(Membership $membership) {
		$qb = $this->getMembershipInsertSql();
		$qb->setValue('circle_id', $qb->createNamedParameter($membership->getCircleId()));
		$qb->setValue('single_id', $qb->createNamedParameter($membership->getSingleId()));
		$qb->setValue('level', $qb->createNamedParameter($membership->getLevel()));
		$qb->setValue('inheritance_first', $qb->createNamedParameter($membership->getInheritanceFirst()));
		$qb->setValue('inheritance_last', $qb->createNamedParameter($membership->getInheritanceLast()));
		$qb->setValue(
			'inheritance_path',
			$qb->createNamedParameter(json_encode($membership->getInheritancePath(), JSON_UNESCAPED_SLASHES))
		);
		$qb->setValue('inheritance_depth', $qb->createNamedParameter($membership->getInheritanceDepth()));

		$qb->execute();
	}


	/**
	 * @param Membership $membership
	 */
	public function update(Membership $membership) {
		$qb = $this->getMembershipUpdateSql();
		$qb->set('level', $qb->createNamedParameter($membership->getLevel()));
		$qb->set('inheritance_last', $qb->createNamedParameter($membership->getInheritanceLast()));
		$qb->set('inheritance_first', $qb->createNamedParameter($membership->getInheritanceFirst()));
		$qb->set(
			'inheritance_path',
			$qb->createNamedParameter(json_encode($membership->getInheritancePath(), JSON_UNESCAPED_SLASHES))
		);
		$qb->set('inheritance_depth', $qb->createNamedParameter($membership->getInheritanceDepth()));

		$qb->limitToSingleId($membership->getSingleId());
		$qb->limitToCircleId($membership->getCircleId());

		$qb->execute();
	}


	/**
	 * @param string $circleId
	 * @param string $singleId
	 *
	 * @return Membership
	 * @throws MembershipNotFoundException
	 */
	public function getMembership(string $circleId, string $singleId): Membership {
		$qb = $this->getMembershipSelectSql();
		$qb->limitToCircleId($circleId);
		$qb->limitToSingleId($singleId);
		$qb->leftJoinCircleConfig(self::TABLE_MEMBERSHIP);

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @param string $singleId
	 *
	 * @return Membership[]
	 */
	public function getMemberships(string $singleId): array {
		$qb = $this->getMembershipSelectSql();
		$qb->limitToSingleId($singleId);
		$qb->leftJoinCircleConfig(CoreQueryBuilder::MEMBERSHIPS);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param string $singleId
	 * @param int $level
	 *
	 * @return Membership[]
	 */
	public function getInherited(string $singleId, int $level = 0): array {
		$qb = $this->getMembershipSelectSql();
		$qb->limitToCircleId($singleId);
		$qb->leftJoinCircleConfig(self::TABLE_MEMBERSHIP);

		if ($level > 1) {
			$expr = $qb->expr();
			$qb->andWhere($expr->gte('level', $qb->createNamedParameter($level, IQueryBuilder::PARAM_INT)));
		}

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param string $singleId
	 * @param bool $all
	 *
	 * @return void
	 */
	public function removeBySingleId(string $singleId, bool $all = false): void {
		$qb = $this->getMembershipDeleteSql();

		if (!$all) {
			$qb->limitToSingleId($singleId);
		}

		$qb->execute();
	}


	/**
	 * @param Membership $membership
	 */
	public function delete(Membership $membership): void {
		$qb = $this->getMembershipDeleteSql();
		$qb->limitToSingleId($membership->getSingleId());
		$qb->limitToCircleId($membership->getCircleId());

		$qb->execute();
	}


	/**
	 * @param FederatedUser $federatedUser
	 */
	public function deleteFederatedUser(FederatedUser $federatedUser): void {
		$qb = $this->getMembershipDeleteSql();
		$qb->limitToSingleId($federatedUser->getSingleId());

		$qb->execute();
	}
}
