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
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Model\Probes\MemberProbe;

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
		$this->confirmValidIds([$member->getCircleId(), $member->getSingleId(), $member->getId()]);

		$qb = $this->getMemberInsertSql();
		$qb->setValue('circle_id', $qb->createNamedParameter($member->getCircleId()))
		   ->setValue('single_id', $qb->createNamedParameter($member->getSingleId()))
		   ->setValue('member_id', $qb->createNamedParameter($member->getId()))
		   ->setValue('user_id', $qb->createNamedParameter($member->getUserId()))
		   ->setValue('user_type', $qb->createNamedParameter($member->getUserType()))
		   ->setValue('cached_name', $qb->createNamedParameter($member->getDisplayName()))
		   ->setValue('cached_update', $qb->createNamedParameter($this->timezoneService->getUTCDate()))
		   ->setValue('instance', $qb->createNamedParameter($qb->getInstance($member)))
		   ->setValue('level', $qb->createNamedParameter($member->getLevel()))
		   ->setValue('status', $qb->createNamedParameter($member->getStatus()))
		   ->setValue('contact_id', $qb->createNamedParameter($member->getContactId()))
		   ->setValue('note', $qb->createNamedParameter(json_encode($member->getNotes())));

		if ($member->hasInvitedBy()) {
			$qb->setValue('invited_by', $qb->createNamedParameter($member->getInvitedBy()->getSingleId()));
		}

		$qb->execute();
	}


	/**
	 * @param Member $member
	 *
	 * @throws InvalidIdException
	 */
	public function update(Member $member): void {
		$this->confirmValidIds([$member->getCircleId(), $member->getSingleId(), $member->getId()]);

		$qb = $this->getMemberUpdateSql();
		$qb->set('member_id', $qb->createNamedParameter($member->getId()))
		   ->set('cached_name', $qb->createNamedParameter($member->getDisplayName()))
		   ->set('cached_update', $qb->createNamedParameter($this->timezoneService->getUTCDate()))
		   ->set('level', $qb->createNamedParameter($member->getLevel()))
		   ->set('status', $qb->createNamedParameter($member->getStatus()))
		   ->set('contact_id', $qb->createNamedParameter($member->getContactId()))
		   ->set('note', $qb->createNamedParameter(json_encode($member->getNotes())));

		$qb->limitToCircleId($member->getCircleId());
		$qb->limitToSingleId($member->getSingleId());

		$qb->execute();
	}


	/**
	 * @param Member $member
	 *
	 * @throws InvalidIdException
	 * @throws RequestBuilderException
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
	 * @param string $singleId
	 * @param string $displayName
	 * @param string $circleId
	 */
	public function updateDisplayName(string $singleId, string $displayName, string $circleId = ''): void {
		$qb = $this->getMemberUpdateSql();
		$qb->set('cached_name', $qb->createNamedParameter($displayName))
		   ->set('cached_update', $qb->createNamedParameter($this->timezoneService->getUTCDate()));

		$qb->limitToSingleId($singleId);
		if ($circleId !== '') {
			$qb->limitToCircleId($circleId);
		}

		$qb->execute();
	}


	/**
	 * @param Member $member
	 */
	public function delete(Member $member) {
		$qb = $this->getMemberDeleteSql();
		$qb->limitToCircleId($member->getCircleId());
		$qb->limitToSingleId($member->getSingleId());

		$qb->execute();
	}


	/**
	 * @param IFederatedUser $federatedUser
	 */
	public function deleteFederatedUser(IFederatedUser $federatedUser): void {
		$qb = $this->getMemberDeleteSql();
		$qb->limitToSingleId($federatedUser->getSingleId());
		$qb->limitToMemberId($federatedUser->getSingleId());
		$qb->limitToCircleId($federatedUser->getSingleId());

		$qb->execute();
	}


	/**
	 * @param IFederatedUser $federatedUser
	 * @param Circle $circle
	 */
	public function deleteFederatedUserFromCircle(IFederatedUser $federatedUser, Circle $circle): void {
		$qb = $this->getMemberDeleteSql();
		$qb->limitToSingleId($federatedUser->getSingleId());
		$qb->limitToCircleId($circle->getSingleId());

		$qb->execute();
	}


	/**
	 *
	 * @param Circle $circle
	 */
	public function deleteAllFromCircle(Circle $circle) {
		$qb = $this->getMemberDeleteSql();
		$qb->andWhere(
			$qb->expr()->orX(
				$qb->exprLimit('single_id', $circle->getSingleId()),
				$qb->exprLimit('circle_id', $circle->getSingleId())
			)
		);

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
		$qb->limitToSingleId($member->getSingleId());

		$qb->execute();
	}


	/**
	 * @param string $singleId
	 * @param IFederatedUser|null $initiator
	 * @param MemberProbe|null $probe
	 *
	 * @return Member[]
	 * @throws RequestBuilderException
	 */
	public function getMembers(
		string $singleId,
		?IFederatedUser $initiator = null,
		?MemberProbe $probe = null
	): array {
		if (is_null($probe)) {
			$probe = new MemberProbe();
		}

		$qb = $this->getMemberSelectSql($initiator);
		$qb->limitToCircleId($singleId);

		$qb->setOptions(
			[CoreQueryBuilder::MEMBER],
			array_merge(
				$probe->getAsOptions(),
				['viewableThroughKeyhole' => true]
			)
		);

		$qb->leftJoinCircle(CoreQueryBuilder::MEMBER, $initiator);
		$qb->leftJoinInvitedBy(CoreQueryBuilder::MEMBER);

		if ($probe->hasFilterRemoteInstance()) {
			$aliasCircle = $qb->generateAlias(CoreQueryBuilder::MEMBER, CoreQueryBuilder::CIRCLE);
			$qb->limitToRemoteInstance(
				CoreQueryBuilder::MEMBER,
				$probe->getFilterRemoteInstance(),
				true,
				$aliasCircle
			);
		}

		if ($probe->hasFilterMember()) {
			$qb->filterDirectMembership(CoreQueryBuilder::MEMBER, $probe->getFilterMember());
		}

		$qb->orderBy($qb->getDefaultSelectAlias() . '.level', 'desc');
		$qb->addOrderBy($qb->getDefaultSelectAlias() . '.cached_name', 'asc');

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param string $singleId
	 * @param bool $getData
	 * @param int $level
	 *
	 * @return Member[]
	 * @throws RequestBuilderException
	 */
	public function getInheritedMembers(string $singleId, bool $getData = false, int $level = 0): array {
		$qb = $this->getMemberSelectSql(null, $getData);

		if ($getData) {
			$qb->leftJoinCircle(CoreQueryBuilder::MEMBER);
			$qb->setOptions([CoreQueryBuilder::MEMBER], ['getData' => $getData]);
		}

		$qb->limitToMembersByInheritance(CoreQueryBuilder::MEMBER, $singleId, $level);

		$aliasMembership = $qb->generateAlias(CoreQueryBuilder::MEMBER, CoreQueryBuilder::MEMBERSHIPS);
		$qb->orderBy($aliasMembership . '.inheritance_depth', 'asc');

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param string $circleId
	 * @param string $singleId
	 * @param CircleProbe|null $probe
	 *
	 * @return Member
	 * @throws MemberNotFoundException
	 * @throws RequestBuilderException
	 */
	public function getMember(string $circleId, string $singleId, ?CircleProbe $probe = null): Member {
		$qb = $this->getMemberSelectSql();
		$qb->limitToCircleId($circleId);
		$qb->limitToSingleId($singleId);

		if (!is_null($probe)) {
			$qb->setOptions([CoreQueryBuilder::MEMBER], $probe->getAsOptions());
		}

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @param string $memberId
	 * @param FederatedUser|null $initiator
	 * @param MemberProbe|null $probe
	 *
	 * @return Member
	 * @throws MemberNotFoundException
	 * @throws RequestBuilderException
	 */
	public function getMemberById(
		string $memberId,
		?FederatedUser $initiator = null,
		?MemberProbe $probe = null
	): Member {
		if (is_null($probe)) {
			$probe = new MemberProbe();
		}

		$qb = $this->getMemberSelectSql();
		$qb->limitToMemberId($memberId);
		$qb->setOptions([CoreQueryBuilder::MEMBER], $probe->getAsOptions());

		if (!is_null($initiator)) {
			$qb->leftJoinCircle(CoreQueryBuilder::MEMBER, $initiator);
		}

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @param string $circleId
	 *
	 * @return array
	 * @throws RequestBuilderException
	 */
	public function getMemberInstances(string $circleId): array {
		$qb = $this->getMemberSelectSql();
		$qb->limitToCircleId($circleId);

		$qb->andwhere($qb->expr()->nonEmptyString(CoreQueryBuilder::MEMBER . '.instance'));

		return array_map(
			function (Member $member): string {
				return $member->getInstance();
			}, $this->getItemsFromRequest($qb)
		);
	}


	/**
	 * @param string $singleId
	 *
	 * @return Member[]
	 * @throws RequestBuilderException
	 */
	public function getMembersBySingleId(string $singleId): array {
		$qb = $this->getMemberSelectSql();
		$qb->leftJoinCircle(CoreQueryBuilder::MEMBER);

		$qb->limitToSingleId($singleId);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param Member $member
	 * @param FederatedUser|null $initiator
	 *
	 * @return Member
	 * @throws MemberNotFoundException
	 * @throws RequestBuilderException
	 */
	public function searchMember(Member $member, ?FederatedUser $initiator = null): Member {
		$qb = $this->getMemberSelectSql();
		$qb->limitToCircleId($member->getCircleId());
		$qb->limitToSingleId($member->getSingleId());

		$qb->leftJoinCircle(CoreQueryBuilder::MEMBER, $initiator);

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @param string $needle
	 *
	 * @return FederatedUser[]
	 * @throws RequestBuilderException
	 */
	public function searchFederatedUsers(string $needle): array {
		$qb = $this->getMemberSelectSql();
		$qb->searchInDBField('user_id', '%' . $needle . '%');

		return $this->getItemsFromRequest($qb, true);
	}


	/**
	 * @param IFederatedUser $federatedUser
	 *
	 * @return Member[]
	 * @throws RequestBuilderException
	 */
	public function getAlternateSingleId(IFederatedUser $federatedUser): array {
		$qb = $this->getMemberSelectSql();
		$qb->limitToSingleId($federatedUser->getSingleId());

		$qb->leftJoinRemoteInstance(CoreQueryBuilder::MEMBER);
//		$expr = $qb->expr();
//		$orX = $expr->orX(
//			$qb->exprFilter('user_id', $federatedUser->getUserId()),
//			$qb->exprFilterInt('user_type', $federatedUser->getUserType()),
//			$qb->exprFilter('instance', $qb->getInstance($federatedUser), '', false)
//		);
//
//		$qb->andWhere($orX);

		return $this->getItemsFromRequest($qb);
	}
}
