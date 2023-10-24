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


namespace OCA\Circles\Service;

use Exception;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\ContactAddressBookNotFoundException;
use OCA\Circles\Exceptions\ContactFormatException;
use OCA\Circles\Exceptions\ContactNotFoundException;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\GroupNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotConfirmedException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\FederatedItems\SingleMemberAdd;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\ManagedModel;
use OCA\Circles\Model\Member;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\IGroupManager;
use OCP\IUserManager;

/**
 * Class SyncService
 *
 * @package OCA\Circles\Service
 */
class SyncService {
	use TStringTools;
	use TNCLogger;


	public const SYNC_APPS = 1;
	public const SYNC_USERS = 2;
	public const SYNC_GROUPS = 4;
	public const SYNC_GLOBALSCALE = 8;
	public const SYNC_REMOTES = 16;
	public const SYNC_CONTACTS = 32;
	public const SYNC_ALL = 63;


	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager */
	private $groupManager;


	/** @var CircleRequest */
	private $circleRequest;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var federatedEventService */
	private $federatedEventService;

	/** @var CircleService */
	private $circleService;

	/** @var MembershipService */
	private $membershipService;

	/** @var OutputService */
	private $outputService;

	/** @var ConfigService */
	private $configService;


	/**
	 * SyncService constructor.
	 *
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param CircleRequest $circleRequest
	 * @param MemberRequest $memberRequest
	 * @param FederatedUserService $federatedUserService
	 * @param federatedEventService $federatedEventService
	 * @param CircleService $circleService
	 * @param MembershipService $membershipService
	 * @param OutputService $outputService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IUserManager $userManager,
		IGroupManager $groupManager,
		CircleRequest $circleRequest,
		MemberRequest $memberRequest,
		FederatedUserService $federatedUserService,
		federatedEventService $federatedEventService,
		CircleService $circleService,
		MembershipService $membershipService,
		OutputService $outputService,
		ConfigService $configService
	) {
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->circleRequest = $circleRequest;
		$this->memberRequest = $memberRequest;
		$this->federatedUserService = $federatedUserService;
		$this->federatedEventService = $federatedEventService;
		$this->circleService = $circleService;
		$this->membershipService = $membershipService;
		$this->outputService = $outputService;
		$this->configService = $configService;

		$this->setup('app', Application::APP_ID);
	}


	/**
	 * @param int $sync
	 *
	 * @return void
	 */
	public function sync(int $sync = self::SYNC_ALL): void {
		if ($this->shouldSync(self::SYNC_APPS, $sync)) {
			$this->syncApps();
		}

		if ($this->shouldSync(self::SYNC_USERS, $sync)) {
			$this->syncNextcloudUsers();
		}

		if ($this->shouldSync(self::SYNC_GROUPS, $sync)) {
			$this->syncNextcloudGroups();
		}

		if ($this->shouldSync(self::SYNC_CONTACTS, $sync)) {
			$this->syncContacts();
		}

		if ($this->shouldSync(self::SYNC_GLOBALSCALE, $sync)) {
			$this->syncGlobalScale();
		}

		if ($this->shouldSync(self::SYNC_REMOTES, $sync)) {
			$this->syncRemote();
		}
	}


	/**
	 * @param int $item
	 * @param int $all
	 *
	 * @return bool
	 */
	private function shouldSync(int $item, int $all): bool {
		return (($item & $all) !== 0);
	}


	/**
	 */
	public function syncApps(): void {
		$this->outputService->output('Syncing Nextcloud Apps');

		try {
			$this->federatedUserService->getAppInitiator('circles', Member::APP_CIRCLES);
			$this->federatedUserService->getAppInitiator('occ', Member::APP_OCC);
		} catch (Exception $e) {
			$this->e($e);
		}
	}


	/**
	 * @return void
	 */
	public function syncNextcloudUsers(): void {
		$this->outputService->output('Syncing Nextcloud Accounts');

		$users = $this->userManager->search('');
		$this->outputService->startMigrationProgress(count($users));

		foreach ($users as $user) {
			try {
				$this->syncNextcloudUser($user->getUID());
			} catch (Exception $e) {
			}
		}

		$this->outputService->finishMigrationProgress();
	}

	/**
	 * @param string $userId
	 *
	 * @return FederatedUser
	 * @throws ContactAddressBookNotFoundException
	 * @throws ContactFormatException
	 * @throws ContactNotFoundException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 */
	public function syncNextcloudUser(string $userId): FederatedUser {
		$this->outputService->output('Syncing Nextcloud Account \'' . $userId . '\'', true);

		return $this->federatedUserService->getLocalFederatedUser($userId, false, true);
	}


	/**
	 * @return void
	 */
	public function syncNextcloudGroups(): void {
		$this->outputService->output('Syncing Nextcloud Groups');

		$groups = $this->groupManager->search('');
		$this->outputService->startMigrationProgress(count($groups));
		foreach ($groups as $group) {
			try {
				$this->syncNextcloudGroup($group->getGID());
			} catch (Exception $e) {
			}
		}

		$this->outputService->finishMigrationProgress();
	}

	/**
	 * @param string $groupId
	 *
	 * @return Circle
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws GroupNotFoundException
	 * @throws InvalidIdException
	 * @throws SingleCircleNotFoundException
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws RequestBuilderException
	 */
	public function syncNextcloudGroup(string $groupId): Circle {
		$this->outputService->output('Syncing Nextcloud Group \'' . $groupId . '\'', true);

		$circle = $this->federatedUserService->getGroupCircle($groupId);
		$members = array_map(function (Member $member): string {
			return $member->getSingleId();
		}, $this->memberRequest->getMembers($circle->getSingleId()));

		$group = $this->groupManager->get($groupId);
		if ($group->count() <= count($members)) {
			return $circle;
		}

		foreach ($group->getUsers() as $user) {
			$member = $this->generateGroupMember($circle, $user->getUID());
			if (in_array($member->getSingleId(), $members)) {
				continue;
			}

			$event = new FederatedEvent(SingleMemberAdd::class);
			$event->setCircle($circle);
			$event->setMember($member);

			try {
				$this->federatedEventService->newEvent($event);
			} catch (Exception $e) {
			}
		}

		return $circle;
	}


	/**
	 * @param string $userId
	 *
	 * @throws ContactAddressBookNotFoundException
	 * @throws ContactFormatException
	 * @throws ContactNotFoundException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws RequestBuilderException
	 */
	public function userDeleted(string $userId): void {
		try {
			$federatedUser = $this->federatedUserService->getLocalFederatedUser($userId, false);
		} catch (SingleCircleNotFoundException $e) {
			return;
		}

		$this->federatedUserService->setCurrentUser($federatedUser);

		$memberships = $federatedUser->getMemberships();
		foreach ($memberships as $membership) {
			if ($membership->getInheritanceDepth() > 1) {
				continue;
			}

			try {
				$this->circleService->circleLeave($membership->getCircleId(), true);
			} catch (Exception $e) {
			}
		}

		$this->federatedUserService->deleteFederatedUser($federatedUser);
	}


	/**
	 * @param string $groupId
	 *
	 * @throws ContactAddressBookNotFoundException
	 * @throws ContactFormatException
	 * @throws ContactNotFoundException
	 * @throws FederatedUserException
	 * @throws InvalidIdException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 */
	public function groupDeleted(string $groupId): void {
		$circle = new Circle();
		$circle->setName('group:' . $groupId)
			   ->setConfig(Circle::CFG_SYSTEM | Circle::CFG_NO_OWNER | Circle::CFG_HIDDEN)
			   ->setSource(Member::TYPE_GROUP);

		$owner = $this->federatedUserService->getAppInitiator(
			Application::APP_ID,
			Member::APP_CIRCLES,
			Application::APP_NAME
		);

		$member = new Member();
		$member->importFromIFederatedUser($owner);
		$member->setLevel(Member::LEVEL_OWNER)
			   ->setStatus(Member::STATUS_MEMBER);
		$circle->setOwner($member);

		try {
			$circle = $this->circleRequest->searchCircle($circle);
		} catch (CircleNotFoundException $e) {
			return;
		}

		$this->circleRequest->delete($circle);
		$this->memberRequest->deleteAllFromCircle($circle);

		$this->membershipService->onUpdate($circle->getSingleId());
	}


	/**
	 * @param Circle $circle
	 * @param string $userId
	 *
	 * @return Member
	 * @throws ContactAddressBookNotFoundException
	 * @throws ContactFormatException
	 * @throws ContactNotFoundException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 */
	private function generateGroupMember(Circle $circle, string $userId): Member {
		$federatedUser = $this->federatedUserService->getLocalFederatedUser($userId);

		$member = new Member();
		$member->importFromIFederatedUser($federatedUser);
		$member->setId($this->token(ManagedModel::ID_LENGTH));
		$member->setCircleId($circle->getSingleId());
		$member->setLevel(Member::LEVEL_MEMBER);
		$member->setStatus(Member::STATUS_MEMBER);
		$member->setInvitedBy($this->federatedUserService->getCurrentApp());

		return $member;
	}


	/**
	 * @param string $groupId
	 * @param string $userId
	 *
	 * @return void
	 * @throws ContactAddressBookNotFoundException
	 * @throws ContactFormatException
	 * @throws ContactNotFoundException
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws GroupNotFoundException
	 * @throws InitiatorNotConfirmedException
	 * @throws InvalidIdException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 * @throws UnknownRemoteException
	 */
	public function groupMemberAdded(string $groupId, string $userId): void {
		$circle = $this->federatedUserService->getGroupCircle($groupId);
		$member = $this->generateGroupMember($circle, $userId);

		$event = new FederatedEvent(SingleMemberAdd::class);
		$event->setCircle($circle);
		$event->setMember($member);
		$this->federatedEventService->newEvent($event);
	}


	/**
	 * @param string $groupId
	 * @param string $userId
	 *
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws GroupNotFoundException
	 * @throws InitiatorNotConfirmedException
	 * @throws InvalidIdException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 * @throws UnknownRemoteException
	 */
	public function groupMemberRemoved(string $groupId, string $userId): void {
		$circle = $this->federatedUserService->getGroupCircle($groupId);
		$federatedUser = $this->federatedUserService->getLocalFederatedUser($userId);

		$this->memberRequest->deleteFederatedUserFromCircle($federatedUser, $circle);
		$this->membershipService->onUpdate($federatedUser->getSingleId());
	}


	/**
	 * @return void
	 */
	public function syncContacts(): void {
		$this->outputService->output('Syncing Contacts');
	}


	/**
	 * @return void
	 */
	public function syncGlobalScale(): void {
		$this->outputService->output('Syncing GlobalScale');
	}


	/**
	 * @return void
	 */
	public function syncRemote(): void {
		$this->outputService->output('Syncing Remote Instance');
	}


	/**
	 * @param string $circleId
	 *
	 * @return void
	 */
	public function syncRemoteCircle(string $circleId): void {
	}
}
