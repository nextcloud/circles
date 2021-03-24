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


use OCA\Circles\Model\Membership;


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
		$qb->setValue('single_id', $qb->createNamedParameter($membership->getSingleId()));
		$qb->setValue('circle_id', $qb->createNamedParameter($membership->getCircleId()));
		$qb->setValue('parent', $qb->createNamedParameter($membership->getParent()));
		$qb->setValue('level', $qb->createNamedParameter($membership->getLevel()));

		$qb->execute();
	}


	/**
	 * @param Membership $membership
	 */
	public function update(Membership $membership) {
		$qb = $this->getMembershipUpdateSql();
		$qb->set('level', $qb->createNamedParameter($membership->getLevel()));

		$qb->limitToSingleId($membership->getSingleId());
		$qb->limitToCircleId($membership->getCircleId());

		$qb->execute();
	}


	/**
	 * @param string $singleId
	 *
	 * @return Membership[]
	 */
	public function getMemberships(string $singleId): array {
		$qb = $this->getMembershipSelectSql();
		$qb->limitToSingleId($singleId);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param string $singleId
	 *
	 * @return Membership[]
	 */
	public function getChildren(string $singleId): array {
		$qb = $this->getMembershipSelectSql();
		$qb->limitToCircleId($singleId);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param string $singleId
	 *
	 * @return void
	 */
	public function removeBySingleId(string $singleId): void {
		$qb = $this->getMembershipDeleteSql();
		$qb->limitToSingleId($singleId);

		$qb->execute();
	}


	/**
	 * @param Membership $membership
	 */
	public function delete(Membership $membership) {
		$qb = $this->getMembershipDeleteSql();
		$qb->limitToSingleId($membership->getSingleId());
		$qb->limitToCircleId($membership->getCircleId());

		$qb->execute();
	}

}

