<?php
/**
 * Circles - bring cloud-users closer
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

namespace OCA\Circles\Service;

use OCA\Circles\Db\DeprecatedCirclesRequest;
use OCA\Circles\Db\DeprecatedMembersRequest;
use OCA\Circles\Exceptions\CircleTypeNotValidException;
use OCA\Circles\Exceptions\GroupCannotBeOwnerException;
use OCA\Circles\Exceptions\GroupDoesNotExistException;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Model\DeprecatedMember;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IUserManager;

/**
 * Class GroupsService
 * @deprecated
 * @package OCA\Circles\Service
 */
class GroupsService {
	/** @var string */
	private $userId;

	/** @var IL10N */
	private $l10n;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IUserManager */
	private $userManager;

	/** @var ConfigService */
	private $configService;

	/** @var DeprecatedCirclesRequest */
	private $circlesRequest;

	/** @var DeprecatedMembersRequest */
	private $membersRequest;

	/** @var CirclesService */
	private $circlesService;

	/** @var EventsService */
	private $eventsService;

	/** @var MiscService */
	private $miscService;

	/**
	 * GroupsService constructor.
	 *
	 * @param string $userId
	 * @param IL10N $l10n
	 * @param IGroupManager $groupManager
	 * @param IUserManager $userManager
	 * @param ConfigService $configService
	 * @param DeprecatedCirclesRequest $circlesRequest
	 * @param DeprecatedMembersRequest $membersRequest
	 * @param CirclesService $circlesService
	 * @param EventsService $eventsService
	 * @param MiscService $miscService
	 */
	public function __construct(
		$userId, IL10N $l10n, IGroupManager $groupManager, IUserManager $userManager,
		ConfigService $configService, DeprecatedCirclesRequest $circlesRequest,
		DeprecatedMembersRequest $membersRequest, CirclesService $circlesService,
		EventsService $eventsService, MiscService $miscService
	) {
		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
		$this->configService = $configService;
		$this->circlesRequest = $circlesRequest;
		$this->membersRequest = $membersRequest;
		$this->circlesService = $circlesService;
		$this->eventsService = $eventsService;
		$this->miscService = $miscService;
	}


	/**
	 * @param string $circleUniqueId
	 * @param string $groupId
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function linkGroup($circleUniqueId, $groupId) {
		try {
			$circle = $this->circlesRequest->getCircle($circleUniqueId, $this->userId);
			$this->circlesService->hasToBeAdmin($circle->getHigherViewer());

			$allMembers =
				$this->membersRequest->forceGetMembers($circleUniqueId, DeprecatedMember::LEVEL_MEMBER, 0, true);

			$group = $this->groupManager->get($groupId);
			$count = $group->count();

			foreach ($allMembers as $member) {
				if ($member->getType() !== DeprecatedMember::TYPE_USER) {
					continue;
				}

				$user = $this->userManager->get($member->getUserId());
				if ($group->inGroup($user)) {
					continue;
				}

				$count++;
			}

			$limit = (int)$circle->getSetting('members_limit');
			if ($limit === 0) {
				$limit = $this->configService->getAppValue(ConfigService::MEMBERS_LIMIT);
			}

			if ($limit !== -1 && $count > $limit) {
				throw new \Exception($this->l10n->t('Group contains too many members'));
			}

			$group = $this->getFreshNewMember($circleUniqueId, $groupId);
		} catch (\Exception $e) {
			throw $e;
		}

		$group->setLevel(DeprecatedMember::LEVEL_MEMBER);
		$this->membersRequest->updateGroup($group);

		$this->eventsService->onGroupLink($circle, $group);

		return $this->membersRequest->getGroupsFromCircle(
			$circleUniqueId, $circle->getHigherViewer()
		);
	}


	/**
	 * Check if a fresh member can be generated (by linkGroup)
	 *
	 * @param $circleId
	 * @param $groupId
	 *
	 * @return null|DeprecatedMember
	 * @throws MemberAlreadyExistsException
	 * @throws GroupDoesNotExistException
	 */
	private function getFreshNewMember($circleId, $groupId) {
		if (!$this->groupManager->groupExists($groupId)) {
			throw new GroupDoesNotExistException($this->l10n->t("This group does not exist"));
		}

		try {
			$instance = ''; // TODO: group are not used in GS yet.
			$member = $this->membersRequest->forceGetGroup($circleId, $groupId, $instance);
		} catch (MemberDoesNotExistException $e) {
			$member = new DeprecatedMember($groupId, DeprecatedMember::TYPE_GROUP, $circleId);
			$this->membersRequest->createMember($member);
//			$this->membersRequest->insertGroup($member);
		}

		if ($member->getLevel() > DeprecatedMember::LEVEL_NONE) {
			throw new MemberAlreadyExistsException(
				$this->l10n->t('This group is already linked to the circle')
			);
		}

		return $member;
	}


	/**
	 * @param string $circleUniqueId
	 * @param string $groupId
	 * @param int $level
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function levelGroup($circleUniqueId, $groupId, $level) {
		$level = (int)$level;
		try {
			$circle = $this->circlesRequest->getCircle($circleUniqueId, $this->userId);
			if ($circle->getType() === DeprecatedCircle::CIRCLES_PERSONAL) {
				throw new CircleTypeNotValidException(
					$this->l10n->t('You cannot edit level in a personal circle')
				);
			}

			$instance = ''; // TODO: group are not used in GS yet.
			$group = $this->membersRequest->forceGetGroup($circle->getUniqueId(), $groupId, $instance);
			if ($group->getLevel() !== $level) {
				if ($level === DeprecatedMember::LEVEL_OWNER) {
					throw new GroupCannotBeOwnerException(
						$this->l10n->t('Group cannot be set as owner of a circle')
					);
				} else {
					$this->editGroupLevel($circle, $group, $level);
				}

				$this->eventsService->onGroupLevel($circle, $group);
			}

			return $this->membersRequest->getGroupsFromCircle(
				$circle->getUniqueId(), $circle->getHigherViewer()
			);
		} catch (\Exception $e) {
			throw $e;
		}
	}


	/**
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $group
	 * @param $level
	 *
	 * @throws \Exception
	 */
	private function editGroupLevel(DeprecatedCircle $circle, DeprecatedMember $group, $level) {
		try {
			$isMod = $circle->getHigherViewer();
			$this->circlesService->hasToBeAdmin($isMod);
			$isMod->hasToBeHigherLevel($level);

			$group->hasToBeMember();
			$group->cantBeOwner();
			$isMod->hasToBeHigherLevel($group->getLevel());

			$group->setLevel($level);
			$this->membersRequest->updateGroup($group);
		} catch (\Exception $e) {
			throw $e;
		}
	}


	/**
	 * @param string $circleUniqueId
	 * @param string $groupId
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function unlinkGroup($circleUniqueId, $groupId) {
		try {
			$circle = $this->circlesRequest->getCircle($circleUniqueId, $this->userId);
			$higherViewer = $circle->getHigherViewer();
			$this->circlesService->hasToBeAdmin($higherViewer);

			$instance = ''; // TODO: group are not used in GS yet.
			$group = $this->membersRequest->forceGetGroup($circleUniqueId, $groupId, $instance);
			$group->cantBeOwner();
			$higherViewer->hasToBeHigherLevel($group->getLevel());

			$this->membersRequest->removeMember($group);
			$this->eventsService->onGroupUnlink($circle, $group);

			return $this->membersRequest->getGroupsFromCircle($circleUniqueId, $higherViewer);
		} catch (\Exception $e) {
			throw $e;
		}
	}


	/**
	 * When a group is removed, remove it from all Circles
	 *
	 * @param string $groupId
	 */
	public function onGroupRemoved($groupId) {
		$this->membersRequest->unlinkAllFromGroup($groupId);
	}
}
