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

class MembersRequest extends MembersRequestBuilder {


	public function getMember($circleId, $userId) {

	}


	/**
	 * forceGetGroup();
	 *
	 * returns group information as a member within a Circle.
	 *
	 * WARNING: This function does not filters data regarding the current user/viewer.
	 *          In case of interaction with users, Please use getGroup() instead.
	 *
	 * @param int $circleId
	 * @param string $groupId
	 *
	 * @return Member
	 * @throws MemberDoesNotExistException
	 */
	public function forceGetGroup($circleId, $groupId) {
		$qb = $this->getGroupsSelectSql();

		$this->limitToGroupId($qb, $groupId);
		$this->limitToCircleId($qb, $circleId);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		if ($data === false) {
			throw new MemberDoesNotExistException($this->l10n->t('This member does not exist'));
		}

		$group = Member::fromArray($this->l10n, $data);
		$cursor->closeCursor();

		return $group;
	}


	/**
	 * return the higher level group linked to a circle, that include the userId.
	 *
	 * WARNING: This function does not filters data regarding the current user/viewer.
	 *          In case of interaction with users, Please don't use this.
	 *
	 * @param int $circleId
	 * @param string $userId
	 *
	 * @return Member
	 */
	public function forceGetHigherLevelGroupFromUser($circleId, $userId) {
		$qb = $this->getGroupsSelectSql();

		$this->limitToCircleId($qb, $circleId);
		$this->limitToNCGroupUser($qb, $userId);

		/** @var Member $group */
		$group = null;

		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$entry = Member::fromArray($this->l10n, $data);
			if ($group === null || $entry->getLevel() > $group->getLevel()) {
				$group = $entry;
			}
		}
		$cursor->closeCursor();

		return $group;
	}



	/**
	 * @param int $circleId
	 * @param Member $viewer
	 *
	 * @return Member[]
	 * @throws MemberDoesNotExistException
	 */
	public function getGroups($circleId, Member $viewer) {

		if ($viewer->getLevel() < Member::LEVEL_MEMBER) {
			return [];
		}

		$qb = $this->getGroupsSelectSql();
		$this->limitToCircleId($qb, $circleId);
		$this->limitToLevel($qb, Member::LEVEL_MEMBER);

		$cursor = $qb->execute();
		$groups = [];
		while ($data = $cursor->fetch()) {
			if ($viewer->getLevel() < Member::LEVEL_MODERATOR) {
				$data['note'] = '';
			}

			$groups[] = Member::fromArray($this->l10n, $data);
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
			   ->setValue('group_id', $qb->createNamedParameter($member->getGroupId()))
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
	 * update database entry for a specific Group.
	 *
	 * @param Member $member
	 *
	 * @return bool
	 */
	public function editGroup(Member $member) {

		$qb = $this->getGroupsUpdateSql($member->getCircleId(), $member->getGroupId());
		$qb->set('level', $qb->createNamedParameter($member->getLevel()));
		$qb->execute();

		return true;
	}


	public function unlinkAllFromGroupId($groupId) {
		$qb = $this->getGroupsDeleteSql($groupId);
		$qb->execute();
	}

}