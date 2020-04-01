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

namespace OCA\Circles\Service;

// use Exception;
use OCP\App\ManagerEvent;
use OCA\Circles\Db\CirclesRequest;
use OCA\Circles\Db\MembersRequest;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCP\EventDispatcher\GenericEvent;
use Symfony\Component\EventDispatcher\GenericEvent as SymfonyGenericEvent;
use OCP\IGroup;
use OCP\IUser;
use OCP\IGroupManager;
use OCP\IUserManager;

/**
 * Class GroupsBackendService
 *
 * @package OCA\Circles\Service
 */
class GroupsBackendService {

	/** @var string */
	protected $userId;

	/** @var Circle */
	protected $circle;

	/** @var Member */
	protected $member;

	/** @var IGroup */
	protected $group;

	/** @var IUser */
	protected $user;

	/** @var ConfigService */
	protected $configService;

	/** @var MiscService */
	protected $miscService;

	/** @var CirclesRequest */
	protected $circlesRequest;

	/** @var MembersRequest */
	protected $membersRequest;

	/** @var IGroupManager */
	protected $groupManager;

	/** @var IUserManager */
	protected $userManager;

	/**
	 * GroupsBackendService constructor.
	 *
	 * @param string $userId
	 * @param CirclesRequest $circlesRequest
	 * @param MembersRequest $membersRequest
	 * @param IGroupManager $groupManager
	 * @param IUserManager $userManager
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		$userId,
		CirclesRequest $circlesRequest,
		MembersRequest $membersRequest,
		IGroupManager $groupManager,
		IUserManager $userManager,
		ConfigService $configService,
		MiscService $miscService
	) {
		$this->userId = $userId;
		$this->circlesRequest = $circlesRequest;
		$this->membersRequest = $membersRequest;
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}

	/**
	 * @param ManagerEvent $event
	 */
	public function onAppEnabled(ManagerEvent $event) {
		if ($event->getAppID() !== 'circles') {
			return;
		}
	}

	/**
	 * @param GenericEvent $event
	 */
	public function onCircleCreation(GenericEvent $event) {
		$this->circle = $event->getArgument('circle');
		// '\OC\Group', 'postDelete'
		// '\OC\Group', 'postAddUser'
		// '\OC\Group', 'postRemoveUser'
		// $eventName ='\OC\Group::postCreate';

		// $listeners = $this->eventDispatcher->getSymfonyDispatcher()->getListeners($eventName);
		// $this->miscService->log('number of listeners: '. count($listeners), 1);

		// foreach ($listeners as $listener) {
		// 	$this->miscService->log('remove listener: '. json_encode($listener), 1);
		// 	$this->eventDispatcher->getSymfonyDispatcher()->removeListener($eventName, $listener);
		// }

		$this->group = $this->groupManager->createGroup($this->getCircleGroupName());

		// foreach ($listeners as $listener) {
		// 	$this->miscService->log('add listener: '. json_encode($listener), 1);
		// 	$this->eventDispatcher->getSymfonyDispatcher()->addListener($eventName, $listener);
		// }

		if ($this->group) {
			$this->member = $this->circle->getOwner();

			$this->circle->setGroupId($this->group->getGID());
			$this->circlesRequest->updateCircle($this->circle, $this->member->getUserId());

			if ($this->member->getType() === Member::TYPE_USER) {
				$this->user = $this->userManager->get($this->member->getUserId());
				if ($this->user) {
					$this->group->addUser($this->user);
				}
			}
		}

		$this->miscService->log('onCircleCreation: '. json_encode($this->circle), 1);
	}

	/**
	 * @param GenericEvent $event
	 */
	public function onCircleDestruction(GenericEvent $event) {
		$this->circle = $event->getArgument('circle');
		$gid = $this->circle->getGroupId();
		$this->group = $this->groupManager->get($gid);

		if ($this->group) {
			$this->group->delete();
		}

		$this->miscService->log('onCircleDestruction: '. json_encode($this->circle), 1);
	}

	/**
	 * @param GenericEvent $event
	 */
	public function onMemberNew(GenericEvent $event) {
		$this->circle = $event->getArgument('circle');
		$this->member = $event->getArgument('member');

		if ($this->member->getType() === Member::TYPE_USER) {
			$gid = $this->circle->getGroupId();
			$this->group = $this->groupManager->get($gid);
			$this->user = $this->userManager->get($this->member->getUserId());

			if ($this->group && $this->user) {
				$this->group->addUser($this->user);
			}
		}

		$this->miscService->log('onMemberNew: '. json_encode($this->circle).json_encode($this->member), 1);
	}

	/**
	 * @param GenericEvent $event
	 */
	public function onMemberInvited(GenericEvent $event) {
		$this->miscService->log('onMemberInvited: '. json_encode($event), 1);
	}

	/**
	 * @param GenericEvent $event
	 */
	public function onMemberRequesting(GenericEvent $event) {
		$this->miscService->log('onMemberRequesting: '. json_encode($event), 1);
	}

	/**
	 * @param GenericEvent $event
	 */
	public function onMemberLeaving(GenericEvent $event) {
		$this->circle = $event->getArgument('circle');
		$this->member = $event->getArgument('member');

		if ($this->member->getType() === Member::TYPE_USER) {
			$gid = $this->circle->getGroupId();
			$this->group = $this->groupManager->get($gid);
			$this->user = $this->userManager->get($this->member->getUserId());

			if ($this->group && $this->user) {
				$this->group->removeUser($this->user);
			}
		}

		$this->miscService->log('onMemberLeaving: '. json_encode($this->circle).json_encode($this->member), 1);
	}

	/**
	 * @param GenericEvent $event
	 */
	public function onMemberLevel(GenericEvent $event) {
		$this->miscService->log('onMemberLevel: '. json_encode($event), 1);
	}

	/**
	 * @param GenericEvent $event
	 */
	public function onMemberOwner(GenericEvent $event) {
		$this->miscService->log('onMemberOwner: '. json_encode($event), 1);
	}

	/**
	 * @param GenericEvent $event
	 */
	public function onGroupLink(GenericEvent $event) {
		$this->miscService->log('onGroupLink: '. json_encode($event), 1);
	}

	/**
	 * @param GenericEvent $event
	 */
	public function onGroupUnlink(GenericEvent $event) {
		$this->miscService->log('onGroupUnlink: '. json_encode($event), 1);
	}

	/**
	 * @param GenericEvent $event
	 */
	public function onGroupLevel(GenericEvent $event) {
		$this->miscService->log('onGroupLevel: '. json_encode($event), 1);
	}

	/**
	 * @param GenericEvent $event
	 */
	public function onLinkRequestSent(GenericEvent $event) {
		$this->miscService->log('onLinkRequestSent: '. json_encode($event), 1);
	}

	/**
	 * @param GenericEvent $event
	 */
	public function onLinkRequestReceived(GenericEvent $event) {
		$this->miscService->log('onLinkRequestReceived: '. json_encode($event), 1);
	}

	/**
	 * @param GenericEvent $event
	 */
	public function onLinkRequestRejected(GenericEvent $event) {
		$this->miscService->log('onLinkRequestRejected: '. json_encode($event), 1);
	}

	/**
	 * @param GenericEvent $event
	 */
	public function onLinkRequestCanceled(GenericEvent $event) {
		$this->miscService->log('onLinkRequestCanceled: '. json_encode($event), 1);
	}

	/**
	 * @param GenericEvent $event
	 */
	public function onLinkRequestAccepted(GenericEvent $event) {
		$this->miscService->log('onLinkRequestAccepted: '. json_encode($event), 1);
	}

	/**
	 * @param GenericEvent $event
	 */
	public function onLinkRequestAccepting(GenericEvent $event) {
		$this->miscService->log('onLinkRequestAccepting: '. json_encode($event), 1);
	}

	/**
	 * @param GenericEvent $event
	 */
	public function onLinkUp(GenericEvent $event) {
		$this->miscService->log('onLinkUp: '. json_encode($event), 1);
	}

	/**
	 * @param GenericEvent $event
	 */
	public function onLinkDown(GenericEvent $event) {
		$this->miscService->log('onLinkDown: '. json_encode($event), 1);
	}

	/**
	 * @param GenericEvent $event
	 */
	public function onLinkRemove(GenericEvent $event) {
		$this->miscService->log('onLinkRemove: '. json_encode($event), 1);
	}

	/**
	 * @param GenericEvent $event
	 */
	public function onSettingsChange(GenericEvent $event) {
		$this->circle = $event->getArgument('circle');
		$oldSettings = $event->getArgument('oldSettings');

		$this->circle = $event->getArgument('circle');
		$gid = $this->circle->getGroupId();
		$this->group = $this->groupManager->get($gid);

		$this->setCircleGroupName($this->getCircleGroupName());

		$this->miscService->log('onSettingsChange: '. json_encode($this->circle).json_encode($oldSettings), 1);
	}

	/**
	 * When a group add a user, add it as a member of the associate Circle
	 *
	 * @param SymfonyGenericEvent $event
	 */
	public function onGroupPostAddUser(SymfonyGenericEvent $event) {
		$this->group = $event->getSubject();
		$this->user = $event->getArgument('user');

		$this->miscService->log('onGroupPostAddUser: '.json_encode($event).json_encode($this->group).json_encode($this->user), 1);
		if ($this->group instanceof IGroup && $this->group->getGID()) {
			$this->circle = $this->circlesRequest->forceGetCircleByGroupId($this->group->getGID());
			if ($this->circle) {
				$this->member = $this->membersRequest->getFreshNewMember(
					$this->circle->getUniqueId(), $this->user->getUID(), Member::TYPE_USER
				);
				$this->member->addMemberToCircle();
				$this->membersRequest->updateMember($this->member);
			}
		}
	}

	/**
	 * When a group remove a user, remove it as a member of the associate Circle
	 *
	 * @param SymfonyGenericEvent $event
	 */
	public function onGroupPostRemoveUser(SymfonyGenericEvent $event) {
		$this->group = $event->getSubject();
		$this->user = $event->getArgument('user');

		$this->miscService->log('onGroupPostRemoveUser: '.json_encode($event).json_encode($this->group).json_encode($this->user), 1);
		if ($this->group instanceof IGroup && $this->group->getGID()) {
			$this->circle = $this->circlesRequest->forceGetCircleByGroupId($this->group->getGID());
			if ($this->circle) {
				try {
					$this->member = $this->membersRequest->forceGetMember(
						$this->circle->getUniqueId(), $this->user->getUID(), Member::TYPE_USER
					);
					$this->member->hasToBeMember();
					$this->member->cantBeOwner();
				} catch (MemberDoesNotExistException $e) {
					$this->member = null;
				} catch (MemberIsOwnerException $e) {
					$this->member = null;
				}
				if ($this->member) {
					$this->membersRequest->removeMember($this->member);
				}
			}
		}
	}

	/**
	 * When a group is removed, remove its associated Circle, if any
	 *
	 * @param SymfonyGenericEvent $event
	 */
	public function onGroupPostDelete(SymfonyGenericEvent $event) {
		$this->group = $event->getSubject();
		$this->miscService->log('onGroupPostDelete: '.json_encode($event).json_encode($this->group), 1);
		if ($this->group instanceof IGroup && $this->group->getGID()) {
			$circle = $this->circlesRequest->forceGetCircleByGroupId($this->group->getGID());
			if ($circle) {
				$this->circlesRequest->destroyCircle($circle->getUniqueId());
			}
		}
	}

	/**
	 * @return string|null
	 */
	protected function getCircleGroupName()
	{
		if ($this->circle instanceof Circle) {
			return $this->configService->getGroupBackendNamePrefix().
					$this->circle->getName().
					$this->configService->getGroupBackendNameSuffix();
		}

		return;
	}

	/**
	 * @param  string $displayName
	 * @return bool
	 */
	protected function setCircleGroupName($displayName)
	{
		if ($this->group && method_exists($this->group, 'setDisplayName')) {
			$this->miscService->log('setCircleGroupName: '. json_encode($displayName), 1);
			return $this->group->setDisplayName($displayName);
		}

		return false;
	}
}
