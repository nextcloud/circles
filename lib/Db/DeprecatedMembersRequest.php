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

use OCA\Circles\Tools\Traits\TStringTools;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Exception;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Model\DeprecatedMember;
use OCP\IGroup;

class DeprecatedMembersRequest extends DeprecatedMembersRequestBuilder {
	use TStringTools;


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
	 * @param string $instance
	 *
	 * @return DeprecatedMember
	 * @throws MemberDoesNotExistException
	 */
	public function forceGetMember($circleUniqueId, $userId, $type, string $instance = '') {
		$qb = $this->getMembersSelectSql();

		if ($this->configService->isLocalInstance($instance)) {
			$instance = '';
		}

		$this->limitToUserId($qb, $userId);
		$this->limitToUserType($qb, $type);
		$this->limitToInstance($qb, $instance);
		$this->limitToCircleId($qb, $circleUniqueId);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new MemberDoesNotExistException($this->l10n->t('This member does not exist'));
		}

		return $this->parseMembersSelectSql($data);
	}


	/**
	 * @param string $memberId
	 *
	 * @return DeprecatedMember
	 * @throws MemberDoesNotExistException
	 */
	public function forceGetMemberById(string $memberId): DeprecatedMember {
		$qb = $this->getMembersSelectSql();

		$this->limitToMemberId($qb, $memberId);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new MemberDoesNotExistException($this->l10n->t('This member does not exist'));
		}

		return $this->parseMembersSelectSql($data);
	}


	/**
	 * Returns members list of a circle, based on their level.
	 *
	 * WARNING: This function does not filters data regarding the current user/viewer.
	 *          In case of interaction with users, Please use getMembers() instead.
	 *
	 * @param string $circleUniqueId
	 * @param int $level
	 * @param int $type
	 * @param bool $incGroup
	 *
	 * @return DeprecatedMember[]
	 */
	public function forceGetMembers(
		string $circleUniqueId, $level = DeprecatedMember::LEVEL_MEMBER, int $type = 0, $incGroup = false
	) {
		$qb = $this->getMembersSelectSql();
		$this->limitToMembersAndAlmost($qb);
		$this->limitToLevel($qb, $level);

		if ($type > 0) {
			$this->limitToUserType($qb, $type);
		}

		$this->limitToCircleId($qb, $circleUniqueId);

		$members = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$members[] = $this->parseMembersSelectSql($data);
		}
		$cursor->closeCursor();

		try {
//			if ($this->configService->isLinkedGroupsAllowed() && $incGroup === true) {
//				$this->includeGroupMembers($members, $circleUniqueId, $level);
//			}
		} catch (GSStatusException $e) {
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
	 * @return DeprecatedMember[]
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
	 * Returns members generated from Contacts that are not 'checked' (as not sent existing shares).
	 *
	 *
	 * @return DeprecatedMember[]
	 */
	public function forceGetAllRecentContactEdit() {
		$qb = $this->getMembersSelectSql();
		$this->limitToUserType($qb, DeprecatedMember::TYPE_CONTACT);

		$expr = $qb->expr();
		$orX = $expr->orX();
		$orX->add($expr->isNull('contact_checked'));
		$orX->add($expr->neq('contact_checked', $qb->createNamedParameter('1')));
		$qb->andWhere($orX);

		$members = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$members[] = $this->parseMembersSelectSql($data);
		}
		$cursor->closeCursor();

		return $members;
	}


	/**
	 * @param DeprecatedMember $member
	 * @param bool $check
	 */
	public function checkMember(DeprecatedMember $member, bool $check) {
		$qb = $this->getMembersUpdateSql(
			$member->getCircleId(), $member->getUserId(), $member->getInstance(), $member->getType()
		);
		$qb->set('contact_checked', $qb->createNamedParameter(($check) ? 1 : 0));

		$qb->execute();
	}


	/**
	 * @param string $circleUniqueId
	 * @param DeprecatedMember $viewer
	 * @param bool $force
	 *
	 * @return DeprecatedMember[]
	 */
	public function getMembers(string $circleUniqueId, ?DeprecatedMember $viewer, bool $force = false) {
		try {
			if ($force === false) {
				$viewer->hasToBeMember();
			}

			$members = $this->forceGetMembers($circleUniqueId, DeprecatedMember::LEVEL_NONE);
			if ($force === false) {
				if (!$viewer->isLevel(DeprecatedMember::LEVEL_MODERATOR)) {
					array_map(
						function (DeprecatedMember $m) {
							$m->setNote('');
						}, $members
					);
				}
			}

			return $members;
		} catch (Exception $e) {
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
	 * @param string $instance
	 *
	 * @return DeprecatedMember
	 * @throws MemberDoesNotExistException
	 */
	public function forceGetGroup(string $circleUniqueId, string $groupId, string $instance) {
		$qb = $this->getMembersSelectSql();

		$this->limitToUserId($qb, $groupId);
		$this->limitToUserType($qb, DeprecatedMember::TYPE_GROUP);
		$this->limitToInstance($qb, $instance);
		$this->limitToCircleId($qb, $circleUniqueId);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();
		if ($data === false) {
			throw new MemberDoesNotExistException($this->l10n->t('This member does not exist'));
		}

		return $this->parseMembersSelectSql($data);
	}


	/**
	 * includeGroupMembers();
	 *
	 * This function will get members of a circle throw NCGroups and fill the result an existing
	 * Members List. In case of duplicate, higher level will be kept.
	 *
	 * @param DeprecatedMember[] $members
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
	 * @param DeprecatedMember[] $members
	 * @param DeprecatedMember[] $groupMembers
	 */
	public function avoidDuplicateMembers(array &$members, array $groupMembers) {
		foreach ($groupMembers as $member) {
			$index = $this->indexOfMember($members, $member->getUserId());
			if ($index === -1) {
				array_push($members, $member);
			} elseif ($members[$index]->getLevel() < $member->getLevel()) {
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
	 * @param int $type
	 *
	 * @param string $instance
	 *
	 * @return DeprecatedMember
	 */
	public function getFreshNewMember($circleUniqueId, string $name, int $type, string $instance) {
		try {
			$member = $this->forceGetMember($circleUniqueId, $name, $type, $instance);
		} catch (MemberDoesNotExistException $e) {
			$member = new DeprecatedMember($name, $type, $circleUniqueId);
			$member->setInstance($instance);
//			$member->setMemberId($this->token(14));
		}

//		if ($member->alreadyExistOrJoining()) {
//			throw new MemberAlreadyExistsException(
//				$this->l10n->t('This account is already a member of the circle')
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
	 * @return DeprecatedMember[]
	 */
	public function forceGetGroupMembers($circleUniqueId, $level = DeprecatedMember::LEVEL_MEMBER) {
		$qb = $this->getMembersSelectSql();

		$this->limitToUserType($qb, DeprecatedMember::TYPE_GROUP);
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
	 * @param DeprecatedMember $group
	 *
	 * @return DeprecatedMember[]
	 */
	public function getGroupMemberMembers(DeprecatedMember $group) {
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
			$member->setType(DeprecatedMember::TYPE_USER);
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
	 * @return DeprecatedMember
	 */
	public function forceGetHigherLevelGroupFromUser($circleUniqueId, $userId) {
		$qb = $this->getMembersSelectSql();

		$this->limitToUserType($qb, DeprecatedMember::TYPE_GROUP);
		$this->limitToInstance($qb, '');
		$this->limitToCircleId($qb, $circleUniqueId);
		$this->limitToNCGroupUser($qb);

		$this->limitToNCGroupUser($qb, $userId);

		/** @var DeprecatedMember $group */
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
	 * @param DeprecatedMember $member
	 *
	 * @throws MemberAlreadyExistsException
	 */
	public function createMember(DeprecatedMember $member) {
		if ($member->getMemberId() === '') {
			$member->setMemberId($this->token(14));
		}

		$instance = $member->getInstance();
		if ($this->configService->isLocalInstance($instance)) {
			$instance = '';
		}

		try {
			$qb = $this->getMembersInsertSql();
			$qb->setValue('circle_id', $qb->createNamedParameter($member->getCircleId()))
			   ->setValue('user_id', $qb->createNamedParameter($member->getUserId()))
			   ->setValue('member_id', $qb->createNamedParameter($member->getMemberId()))
			   ->setValue('user_type', $qb->createNamedParameter($member->getType()))
			   ->setValue('cached_name', $qb->createNamedParameter($member->getCachedName()))
			   ->setValue('cached_update', $qb->createNamedParameter($this->timezoneService->getUTCDate()))
			   ->setValue('instance', $qb->createNamedParameter($instance))
			   ->setValue('level', $qb->createNamedParameter($member->getLevel()))
			   ->setValue('status', $qb->createNamedParameter($member->getStatus()))
			   ->setValue('contact_id', $qb->createNamedParameter($member->getContactId()))
			   ->setValue('note', $qb->createNamedParameter($member->getNote()));

			$qb->execute();
		} catch (UniqueConstraintViolationException $e) {
			throw new MemberAlreadyExistsException(
				$this->l10n->t('This account is already a member of the circle')
			);
		}
	}


	/**
	 * @param string $circleUniqueId
	 * @param DeprecatedMember $viewer
	 *
	 * @return DeprecatedMember[]
	 */
	public function getGroupsFromCircle($circleUniqueId, DeprecatedMember $viewer) {
		if ($viewer->getLevel() < DeprecatedMember::LEVEL_MEMBER) {
			return [];
		}

		$qb = $this->getMembersSelectSql();

		$this->limitToUserType($qb, DeprecatedMember::TYPE_GROUP);
		$this->limitToLevel($qb, DeprecatedMember::LEVEL_MEMBER);
		$this->limitToInstance($qb, '');
		$this->limitToCircleId($qb, $circleUniqueId);

		$cursor = $qb->execute();
		$groups = [];
		while ($data = $cursor->fetch()) {
			if ($viewer->getLevel() < DeprecatedMember::LEVEL_MODERATOR) {
				$data['note'] = '';
			}
			$groups[] = $this->parseGroupsSelectSql($data);
		}
		$cursor->closeCursor();

		return $groups;
	}


	/**
	 * update database entry for a specific Member.
	 *
	 * @param DeprecatedMember $member
	 */
	public function updateMemberLevel(DeprecatedMember $member) {
		$instance = $member->getInstance();
		if ($this->configService->isLocalInstance($instance)) {
			$instance = '';
		}

		$qb = $this->getMembersUpdateSql(
			$member->getCircleId(), $member->getUserId(), $instance, $member->getType()
		);
		$qb->set('level', $qb->createNamedParameter($member->getLevel()))
		   ->set('status', $qb->createNamedParameter($member->getStatus()));

		$qb->execute();
	}


	/**
	 * update database entry for a specific Member.
	 *
	 * @param DeprecatedMember $member
	 */
	public function updateMemberInfo(DeprecatedMember $member) {
		$instance = $member->getInstance();
		if ($this->configService->isLocalInstance($instance)) {
			$instance = '';
		}

		$qb = $this->getMembersUpdateSql(
			$member->getCircleId(), $member->getUserId(), $instance, $member->getType()
		);
		$qb->set('note', $qb->createNamedParameter($member->getNote()))
		   ->set('cached_name', $qb->createNamedParameter($member->getCachedName()))
		   ->set('cached_update', $qb->createNamedParameter($this->timezoneService->getUTCDate()));

		$qb->execute();
	}


	/**
	 * update database entry for a specific Member.
	 *
	 * @param DeprecatedMember $member
	 */
	public function updateContactMeta(DeprecatedMember $member) {
		$qb = $this->getMembersUpdateSql(
			$member->getCircleId(), $member->getUserId(), $member->getInstance(), $member->getType()
		);
		$qb->set('contact_meta', $qb->createNamedParameter(json_encode($member->getContactMeta())));

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
	 * @param DeprecatedMember $member
	 */
	public function removeAllMembershipsFromUser(DeprecatedMember $member) {
		if ($member->getUserId() === '') {
			return;
		}

		$instance = $member->getInstance();
		if ($this->configService->isLocalInstance($instance)) {
			$instance = '';
		}

		$qb = $this->getMembersDeleteSql();
		$expr = $qb->expr();

		$qb->where(
			$expr->andX(
				$expr->eq('user_id', $qb->createNamedParameter($member->getUserId())),
				$expr->eq('instance', $qb->createNamedParameter($instance)),
				$expr->eq('user_type', $qb->createNamedParameter(DeprecatedMember::TYPE_USER))
			)
		);

		$qb->execute();
	}


	/**
	 * remove member, identified by its id, type and circleId
	 *
	 * @param DeprecatedMember $member
	 */
	public function removeMember(DeprecatedMember $member) {
		$instance = $member->getInstance();
		if ($this->configService->isLocalInstance($instance)) {
			$instance = '';
		}

		$qb = $this->getMembersDeleteSql();
		$this->limitToCircleId($qb, $member->getCircleId());
		$this->limitToUserId($qb, $member->getUserId());
		$this->limitToInstance($qb, $instance);
		$this->limitToUserType($qb, $member->getType());
		if ($member->getContactId() !== '') {
			$this->limitToContactId($qb, $member->getContactId());
		}

		$qb->execute();
	}

	/**
	 * update database entry for a specific Group.
	 *
	 * @param DeprecatedMember $member
	 *
	 * @return bool
	 */
	public function updateGroup(DeprecatedMember $member) {
		$qb = $this->getMembersUpdateSql(
			$member->getCircleId(), $member->getUserId(), $member->getInstance(), $member->getType()
		);
		$qb->set('level', $qb->createNamedParameter($member->getLevel()));
		$qb->execute();

		return true;
	}


	public function unlinkAllFromGroup($groupId) {
		$qb = $this->getMembersDeleteSql();

		$this->limitToUserId($qb, $groupId);
		$this->limitToUserType($qb, DeprecatedMember::TYPE_GROUP);
		$this->limitToInstance($qb, '');

		$qb->execute();
	}


	/**
	 * @param string $contactId
	 *
	 * @return DeprecatedMember[]
	 */
	public function getMembersByContactId(string $contactId = ''): array {
		$qb = $this->getMembersSelectSql();
		if ($contactId === '') {
			$expr = $qb->expr();
			$qb->andWhere($expr->neq('contact_id', $qb->createNamedParameter('')));
		} else {
			$this->limitToContactId($qb, $contactId);
		}

		$members = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$member = $this->parseMembersSelectSql($data);
			$members[] = $member;
		}
		$cursor->closeCursor();

		return $members;
	}


	/**
	 * @param string $circleId
	 * @param string $contactId
	 *
	 * @return DeprecatedMember
	 * @throws MemberDoesNotExistException
	 */
	public function getContactMember(string $circleId, string $contactId): DeprecatedMember {
		$qb = $this->getMembersSelectSql();
		$this->limitToContactId($qb, $contactId);
		$this->limitToCircleId($qb, $circleId);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new MemberDoesNotExistException($this->l10n->t('This member does not exist'));
		}

		return $this->parseMembersSelectSql($data);
	}


	/**
	 * @param string $contactId
	 *
	 * @return DeprecatedMember[]
	 */
	public function getLocalContactMembers(string $contactId): array {
		$qb = $this->getMembersSelectSql();
		$this->limitToContactId($qb, $contactId);
		$this->limitToUserType($qb, DeprecatedMember::TYPE_USER);

		$members = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$members[] = $this->parseMembersSelectSql($data);
		}
		$cursor->closeCursor();

		return $members;
	}


	/**
	 * @param string $contactId
	 * @param int $type
	 */
	public function removeMembersByContactId(string $contactId, int $type = 0) {
		$this->miscService->log($contactId);
		if ($contactId === '') {
			return;
		}

		$qb = $this->getMembersDeleteSql();
		$this->limitToContactId($qb, $contactId);
		if ($type > 0) {
			$this->limitToUserType($qb, $type);
		}

		$qb->execute();
	}
}
