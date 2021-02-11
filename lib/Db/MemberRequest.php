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
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\RemoteInstance;
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
	 *
	 * @throws InvalidIdException
	 */
	public function save(Member $member): void {
		// TODO: check singleId is not empty
//		$this->confirmValidIds([$member->getCircleId(), $member->getSingleId(), $member->getId()]);
		$this->confirmValidIds([$member->getCircleId(), $member->getId()]);

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
	 * @param Member $member
	 *
	 * @throws InvalidIdException
	 */
	public function update(Member $member): void {
		// TODO: check singleId is not empty
//		$this->confirmValidIds([$member->getCircleId(), $member->getSingleId(), $member->getId()]);
		$this->confirmValidIds([$member->getCircleId(), $member->getId()]);

		$qb = $this->getMemberUpdateSql();
		$qb->set('member_id', $qb->createNamedParameter($member->getId()))
		   ->set('cached_name', $qb->createNamedParameter($member->getCachedName()))
		   ->set('cached_update', $qb->createNamedParameter($this->timezoneService->getUTCDate()))
		   ->set('level', $qb->createNamedParameter($member->getLevel()))
		   ->set('status', $qb->createNamedParameter($member->getStatus()))
		   ->set('contact_id', $qb->createNamedParameter($member->getContactId()))
		   ->set('note', $qb->createNamedParameter($member->getNote()));

		$qb->limitToCircleId($member->getCircleId());
		$qb->limitToUserId($member->getUserId());
		$qb->limitToUserType($member->getUserType());
		$qb->limitToInstance($qb->getInstance($member));
//		$qb->limitToSingleId($federatedUser->getSingleId());

		$qb->execute();
	}


	/**
	 * @param Member $member
	 *
	 * @throws InvalidIdException
	 */
	public function insertOrUpdate(Member $member): void {
		try {
			$this->searchMember($member);
			$this->update($member);
		} catch (MemberNotFoundException $e) {
			$this->save($member);
		}
	}


	/**
	 * @param Member $member
	 */
	public function delete(Member $member) {
		$qb = $this->getMemberDeleteSql();
		$qb->limitToCircleId($member->getCircleId());
		$qb->limitToUserId($member->getUserId());
		$qb->limitToUserType($member->getUserType());
		$qb->limitToInstance($qb->getInstance($member));
//		$qb->limitToSingleId($federatedUser->getSingleId());

		$qb->execute();
	}


	/**
	 * @param Member $member
	 */
	public function updateLevel(Member $member): void {
		$qb = $this->getMemberUpdateSql();
		$qb->set('level', $qb->createNamedParameter($member->getLevel()));

		$qb->limitToMemberId($member->getId());
		$qb->limitToCircleId($member->getCircleId());
		$qb->limitToUserId($member->getUserId());
		$qb->limitToUserType($member->getUserType());
		$qb->limitToInstance($qb->getInstance($member));
		//		$qb->limitToSingleId($member->getSingleId());

		$qb->execute();
	}


	/**
	 * @param string $circleId
	 * @param IFederatedUser|null $initiator
	 * @param RemoteInstance|null $remoteInstance
	 *
	 * @return Circle[]
	 */
	public function getMembers(
		string $circleId,
		?IFederatedUser $initiator = null,
		?RemoteInstance $remoteInstance = null
	): array {
		$qb = $this->getMemberSelectSql();
		$qb->limitToCircleId($circleId);
		$qb->leftJoinCircle($initiator);

		if (!is_null($remoteInstance)) {
			$qb->limitToRemoteInstance($remoteInstance->getInstance(), true);
		}

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


	/**
	 * @param string $singleId
	 *
	 * @return Member[]
	 */
	public function getMembersBySingleId(string $singleId): array {
		$qb = $this->getMemberSelectSql();
		$qb->limitToSingleId($singleId);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param Member $member
	 * @param FederatedUser|null $initiator
	 *
	 * @return Member
	 * @throws MemberNotFoundException
	 */
	public function searchMember(Member $member, ?FederatedUser $initiator = null): Member {
		$qb = $this->getMemberSelectSql();
		$qb->limitToCircleId($member->getCircleId());
		$qb->limitToUserId($member->getUserId());
		$qb->limitToUserType($member->getUserType());
		$qb->limitToInstance($qb->getInstance($member));
//		$qb->limitToSingleId($federatedUser->getSingleId());

		if (!is_null($initiator)) {
			$qb->leftJoinCircle($initiator);
		}

		return $this->getItemFromRequest($qb);
	}

}

