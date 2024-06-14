<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
