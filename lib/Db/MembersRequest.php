<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
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


use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Model\Member;
use OCP\IGroup;
use OCA\Circles\Model\Timezone;

class MembersRequest extends MembersRequestBuilder {


	/**
	 * Returns information about a member.
	 *
	 * WARNING: This function does not filters data regarding the current user/viewer.
	 *          In case of interaction with users, Please use MembersService->getMember() instead.
	 *
	 * @param string $circleUniqueId
	 * @param string $userId
	 * @param $type
	 *
	 * @return Member
	 * @throws MemberDoesNotExistException
	 */
	public function forceGetMember($circleUniqueId, $userId, $type) {
		$qb = $this->getMembersSelectSql();

		$this->limitToUserId($qb, $userId);
		$this->limitToUserType($qb, $type);
		$this->limitToCircleId($qb, $circleUniqueId);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new MemberDoesNotExistException($this->l10n->t('This member does not exist'));
		}

		$member = $this->parseMembersSelectSql($data);

		return $member;
	}


	/**
	 * Returns members list of a circle, based on their level.
	 *
	 * WARNING: This function does not filters data regarding the current user/viewer.
	 *          In case of interaction with users, Please use getMembers() instead.
	 *
	 * @param string $circleUniqueId
	 * @param int $level
	 * @param bool $incGroup
	 *
	 * @return Member[]
	 */
	public function forceGetMembers($circleUniqueId, $level = Member::LEVEL_MEMBER, $incGroup = false) {

		$qb = $this->getMembersSelectSql();
		$this->limitToMembersAndAlmost($qb);
		$this->limitToLevel($qb, $level);

		$this->limitToCircleId($qb, $circleUniqueId);

		$members = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$members[] = $this->parseMembersSelectSql($data);
		}
		$cursor->closeCursor();

		if ($this->configService->isLinkedGroupsAllowed() && $incGroup === true) {
			$this->includeGroupMembers($members, $circleUniqueId, $level);
		}

		return $members;
	}


	/**
	 * Returns all members.
	 *
	 * WARNING: This function does not filters data regarding the current user/viewer.
	 *          In case of interaction with users, Please use getMembers() instead.
	 *
	 *
	 * @return Member[]
	 */
	public function forceGetAllMembers() {

		$qb = $this->getMembersSelectSql();

		$members = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$members[] = $this->parseMembersSelectSql($data);
		}
		$cursor->closeCursor();

		return $members;
	}


	/**
	 * @param string $circleUniqueId
	 * @param Member $viewer
	 *
	 * @return Member[]
	 * @throws \Exception
	 */
	public function getMembers($circleUniqueId, Member $viewer) {
		try {
			$viewer->hasToBeMember();

			$members = $this->forceGetMembers($circleUniqueId, Member::LEVEL_NONE);
			if (!$viewer->isLevel(Member::LEVEL_MODERATOR)) {
				array_map(
					function(Member $m) {
						$m->setNote('');
					}, $members
				);
			}

			return $members;
		} catch (\Exception $e) {
			return [];
		}
	}


	/**
	 * forceGetGroup();
	 *
	 * returns group information as a member within a Circle.
	 *
	 * WARNING: This function does not filters data regarding the current user/viewer.
	 *          In case of interaction with users, Please use getGroup() instead.
	 *
	 * @param string $circleUniqueId
	 * @param string $groupId
	 *
	 * @return Member
	 * @throws MemberDoesNotExistException
	 */
	public function forceGetGroup($circleUniqueId, $groupId) {
		$qb = $this->getGroupsSelectSql();

		$this->limitToGroupId($qb, $groupId);
		$this->limitToCircleId($qb, $circleUniqueId);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		if ($data === false) {
			throw new MemberDoesNotExistException($this->l10n->t('This member does not exist'));
		}

		$group = $this->parseGroupsSelectSql($data);
		$cursor->closeCursor();

		return $group;
	}


	/**
	 * includeGroupMembers();
	 *
	 * This function will get members of a circle throw NCGroups and fill the result an existing
	 * Members List. In case of duplicate, higher level will be kept.
	 *
	 * @param Member[] $members
	 * @param string $circleUniqueId
	 * @param int $level
	 */
	private function includeGroupMembers(array &$members, $circleUniqueId, $level) {

		$groupMembers = $this->forceGetGroupMembers($circleUniqueId, $level);
		$this->avoidDuplicateMembers($members, $groupMembers);
	}


	/**
	 * avoidDuplicateMembers();
	 *
	 * Use this function to add members to the list (1st argument), keeping the higher level in case
	 * of duplicate
	 *
	 * @param Member[] $members
	 * @param Member[] $groupMembers
	 */
	public function avoidDuplicateMembers(array &$members, array $groupMembers) {
		foreach ($groupMembers as $member) {
			$index = $this->indexOfMember($members, $member->getUserId());
			if ($index === -1) {
				array_push($members, $member);
			} else if ($members[$index]->getLevel() < $member->getLevel()) {
				$members[$index] = $member;
			}
		}
	}


	/**
	 * returns the index of a specific UserID in a Members List
	 *
	 * @param array $members
	 * @param $userId
	 *
	 * @return int
	 */
	private function indexOfMember(array $members, $userId) {

		foreach ($members as $k => $member) {
			if ($member->getUserId() === $userId) {
				return intval($k);
			}
		}

		return -1;
	}


	/**
	 * Check if a fresh member can be generated (by addMember/joinCircle)
	 *
	 * @param string $circleUniqueId
	 * @param string $name
	 * @param $type
	 *
	 * @return Member
	 * @throws MemberAlreadyExistsException
	 * @throws \Exception
	 */
	public function getFreshNewMember($circleUniqueId, $name, $type) {

		try {

			$member = $this->forceGetMember($circleUniqueId, $name, $type);

		} catch (MemberDoesNotExistException $e) {
			$member = new Member($name, $type, $circleUniqueId);
			$this->createMember($member);
		}

//		if ($member->alreadyExistOrJoining()) {
//			throw new MemberAlreadyExistsException(
//				$this->l10n->t('This user is already a member of the circle')
//			);
//		}

		return $member;
	}


	/**
	 * Returns members list of all Group Members of a Circle. The Level of the linked group will be
	 * assigned to each entry
	 *
	 * NOTE: Can contains duplicate.
	 *
	 * WARNING: This function does not filters data regarding the current user/viewer.
	 *          Do not use in case of direct interaction with users.
	 *
	 * @param string $circleUniqueId
	 * @param int $level
	 *
	 * @return Member[]
	 */
	public function forceGetGroupMembers($circleUniqueId, $level = Member::LEVEL_MEMBER) {
		$qb = $this->getGroupsSelectSql();

		$this->limitToLevel($qb, $level);
		$this->limitToCircleId($qb, $circleUniqueId);
		$this->limitToNCGroupUser($qb);

		$members = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$members[] = $this->parseGroupsSelectSql($data);
		}
		$cursor->closeCursor();

		return $members;
	}


	/**
	 * returns all users from a Group as a list of Members.
	 *
	 * @param Member $group
	 *
	 * @return Member[]
	 */
	public function getGroupMemberMembers(Member $group) {
		/** @var IGroup $grp */
		$grp = $this->groupManager->get($group->getUserId());
		if ($grp === null) {
			return [];
		}

		$members = [];
		$users = $grp->getUsers();
		foreach ($users as $user) {
			$member = clone $group;
			//Member::fromJSON($this->l10n, json_encode($group));
			$member->setType(Member::TYPE_USER);
			$member->setUserId($user->getUID());
			$members[] = $member;
		}

		return $members;
	}


	/**
	 * return the higher level group linked to a circle, that include the userId.
	 *
	 * WARNING: This function does not filters data regarding the current user/viewer.
	 *          In case of direct interaction with users, Please don't use this.
	 *
	 * @param string $circleUniqueId
	 * @param string $userId
	 *
	 * @return Member
	 */
	public function forceGetHigherLevelGroupFromUser($circleUniqueId, $userId) {
		$qb = $this->getGroupsSelectSql();

		$this->limitToCircleId($qb, $circleUniqueId);
		$this->limitToNCGroupUser($qb, $userId);

		/** @var Member $group */
		$group = null;

		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$entry = $this->parseGroupsSelectSql($data);
			if ($group === null || $entry->getLevel() > $group->getLevel()) {
				$group = $entry;
			}
		}
		$cursor->closeCursor();

		return $group;
	}


	/**
	 * Insert Member into database.
	 *
	 * @param Member $member
	 *
	 * @throws MemberAlreadyExistsException
	 */
	public function createMember(Member $member) {

		try {
			$qb = $this->getMembersInsertSql();
			$qb->setValue('circle_id', $qb->createNamedParameter($member->getCircleId()))
			   ->setValue('user_id', $qb->createNamedParameter($member->getUserId()))
			   ->setValue('user_type', $qb->createNamedParameter($member->getType()))
			   ->setValue('level', $qb->createNamedParameter($member->getLevel()))
			   ->setValue('status', $qb->createNamedParameter($member->getStatus()))
			   ->setValue('note', $qb->createNamedParameter($member->getNote()))
			   ->setValue('joined', $qb->createNamedParameter(Timezone::getUTCTimestamp()));

			$qb->execute();
		} catch (UniqueConstraintViolationException $e) {
			throw new MemberAlreadyExistsException(
				$this->l10n->t('This user is already a member of the circle')
			);
		}
	}


	/**
	 * @param string $circleUniqueId
	 * @param Member $viewer
	 *
	 * @return Member[]
	 * @throws MemberDoesNotExistException
	 */
	public function getGroupsFromCircle($circleUniqueId, Member $viewer) {

		if ($viewer->getLevel() < Member::LEVEL_MEMBER) {
			return [];
		}

		$qb = $this->getGroupsSelectSql();
		$this->limitToCircleId($qb, $circleUniqueId);
		$this->limitToLevel($qb, Member::LEVEL_MEMBER);

		$cursor = $qb->execute();
		$groups = [];
		while ($data = $cursor->fetch()) {
			if ($viewer->getLevel() < Member::LEVEL_MODERATOR) {
				$data['note'] = '';
			}
			$groups[] = $this->parseGroupsSelectSql($data);
		}
		$cursor->closeCursor();

		return $groups;
	}


	/**
	 * Insert Member into database.
	 *
	 * @param Member $member
	 *
	 * @throws MemberAlreadyExistsException
	 */
	public function insertGroup(Member $member) {
		try {
			$qb = $this->getGroupsInsertSql();
			$qb->setValue('circle_id', $qb->createNamedParameter($member->getCircleId()))
			   ->setValue('group_id', $qb->createNamedParameter($member->getUserId()))
			   ->setValue('level', $qb->createNamedParameter($member->getLevel()))
			   ->setValue('note', $qb->createNamedParameter($member->getNote()));

			$qb->execute();
		} catch (UniqueConstraintViolationException $e) {
			throw new MemberAlreadyExistsException(
				$this->l10n->t('This user is already a member of the circle')
			);
		}
	}


	/**
	 * update database entry for a specific Member.
	 *
	 * @param Member $member
	 */
	public function updateMember(Member $member) {
		$qb = $this->getMembersUpdateSql($member->getCircleId(), $member);
		$qb->set('level', $qb->createNamedParameter($member->getLevel()))
		   ->set('status', $qb->createNamedParameter($member->getStatus()));

		$qb->execute();
	}


	/**
	 * removeAllFromCircle();
	 *
	 * Remove All members from a Circle. Used when deleting a Circle.
	 *
	 * @param string $uniqueCircleId
	 */
	public function removeAllFromCircle($uniqueCircleId) {
		$qb = $this->getMembersDeleteSql();
		$expr = $qb->expr();

		$qb->where($expr->eq('circle_id', $qb->createNamedParameter($uniqueCircleId)));
		$qb->execute();
	}


	/**
	 * removeAllMembershipsFromUser();
	 *
	 * remove All membership from a User. Used when removing a User from the Cloud.
	 *
	 * @param string $userId
	 */
	public function removeAllMembershipsFromUser($userId) {
		if ($userId === '') {
			return;
		}

		$qb = $this->getMembersDeleteSql();
		$expr = $qb->expr();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->where(
			$expr->andX(
				$expr->eq('user_id', $qb->createNamedParameter($userId)),
				$expr->eq('user_type', $qb->createNamedParameter(Member::TYPE_USER))
			)
		);

		$qb->execute();
	}


	/**
	 * remove member, identified by its id, type and circleId
	 *
	 * @param Member $member
	 */
	public function removeMember(Member $member) {
		$qb = $this->getMembersDeleteSql();
		$this->limitToCircleId($qb, $member->getCircleId());
		$this->limitToUserId($qb, $member->getUserId());
		$this->limitToUserType($qb, $member->getType());

		$qb->execute();
	}

	/**
	 * update database entry for a specific Group.
	 *
	 * @param Member $member
	 *
	 * @return bool
	 */
	public function updateGroup(Member $member) {

		$qb = $this->getGroupsUpdateSql($member->getCircleId(), $member->getUserId());
		$qb->set('level', $qb->createNamedParameter($member->getLevel()));
		$qb->execute();

		return true;
	}


	public function unlinkAllFromGroup($groupId) {
		$qb = $this->getGroupsDeleteSql($groupId);
		$qb->execute();
	}

}