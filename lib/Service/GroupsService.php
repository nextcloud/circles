<?php
/**
 * Circles - bring cloud-users closer
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

namespace OCA\Circles\Service;


use OC\User\NoUserException;
use OCA\Circles\Db\CirclesMapper;
use OCA\Circles\Db\MembersMapper;
use OCA\Circles\Exceptions\CircleTypeNotValid;
use OCA\Circles\Exceptions\GroupDoesNotExistException;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Model\Circle;
use \OCA\Circles\Model\Member;
use OCP\IL10N;
use OCP\IUserManager;

class GroupsService {


	/** @var IL10N */
	private $l10n;

	/** @var MiscService */
	private $miscService;

	public function __construct(IL10N $l10n, MiscService $miscService) {
		$this->l10n = $l10n;
		$this->miscService = $miscService;
	}


	/**
	 * @param $circleId
	 * @param $groupId
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function addGroup($circleId, $groupId) {
//
//		try {
//			$circle = $this->dbCircles->getDetailsFromCircle($circleId, $this->userId);
//			$this->dbMembers->getMemberFromCircle($circleId, $this->userId)
//							->hasToBeModerator();
//		} catch (\Exception $e) {
//			throw $e;
//		}
//
//		try {
//			$member = $this->getFreshNewMember($circleId, $name);
//		} catch (\Exception $e) {
//			throw $e;
//		}
//		$member->inviteToCircle($circle->getType());
//		$this->dbMembers->editMember($member);
//
//		$this->eventsService->onMemberNew($circle, $member);
		return array();
//		return $this->dbMembers->getGroupsFromCircle($circleId, $circle->getUser());
	}

	/**
	 * @param $circleId
	 * @param $groupId
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function removeGroup($circleId, $groupId) {
		return array();
//		try {
//			$isMod = $this->dbMembers->getMemberFromCircle($circleId, $this->userId);
//			$isMod->hasToBeModerator();
//
//			$member = $this->dbMembers->getMemberFromCircle($circleId, $name);
//			$member->cantBeOwner();
//
//			$isMod->hasToBeHigherLevel($member->getLevel());
//		} catch (\Exception $e) {
//			throw $e;
//		}
//
//		$member->setStatus(Member::STATUS_NONMEMBER);
//		$member->setLevel(Member::LEVEL_NONE);
//		$this->dbMembers->editMember($member);
//
//		$circle = $this->dbCircles->getDetailsFromCircle($circleId, $this->userId);
//		$this->eventsService->onMemberLeaving($circle, $member);
//
//		return $this->dbMembers->getMembersFromCircle($circleId, $circle->getUser());
	}


	/**
	 * When a group is removed, remove him from all Circles
	 *
	 * @param $groupId
	 */
//	public function removeGroup($groupId) {
//		$this->dbMembers->removeAllFromUserId($userId);
//	}


}