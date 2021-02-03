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


use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;


/**
 * Class MemberRequest
 *
 * @package OCA\Circles\Db
 */
class MemberRequest extends MemberRequestBuilder {


	/**
	 * @param Member $member
	 */
	public function save(Member $member): void {
		$qb = $this->getMemberInsertSql();
		$qb->setValue('circle_id', $qb->createNamedParameter($member->getCircleId()))
		   ->setValue('single_id', $qb->createNamedParameter($member->getSingleId()))
		   ->setValue('member_id', $qb->createNamedParameter($member->getId()))
		   ->setValue('user_id', $qb->createNamedParameter($member->getUserId()))
		   ->setValue('user_type', $qb->createNamedParameter($member->getUserType()))
		   ->setValue('cached_name', $qb->createNamedParameter($member->getCachedName()))
		   ->setValue('cached_update', $qb->createNamedParameter($this->timezoneService->getUTCDate()))
		   ->setValue('instance', $qb->createNamedParameter($qb->getInstance($member)))
		   ->setValue('level', $qb->createNamedParameter($member->getLevel()))
		   ->setValue('status', $qb->createNamedParameter($member->getStatus()))
		   ->setValue('contact_id', $qb->createNamedParameter($member->getContactId()))
		   ->setValue('note', $qb->createNamedParameter($member->getNote()));

		$qb->execute();
	}


	/**
	 * @param string $circleId
	 *
	 * @return Circle[]
	 */
	public function getMembers(string $circleId): array {
		$qb = $this->getMemberSelectSql();
		$qb->limitToCircleId($circleId);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param string $memberId
	 * @param FederatedUser|null $initiator
	 *
	 * @return Member
	 * @throws MemberNotFoundException
	 */
	public function getMember(string $memberId, ?FederatedUser $initiator = null): Member {
		$qb = $this->getMemberSelectSql();
		$qb->limitToMemberId($memberId);

		if (!is_null($initiator)) {
			$qb->leftJoinCircle($initiator);
		}


		return $this->getItemFromRequest($qb);
	}

}

