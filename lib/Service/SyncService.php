<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

	/**
	 * SyncService constructor.
	 *
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param CircleRequest $circleRequest
	 * @param MemberRequest $memberRequest
	 * @param FederatedUserService $federatedUserService
	 * @param FederatedEventService $federatedEventService
	 * @param CircleService $circleService
	 * @param MembershipService $membershipService
	 * @param OutputService $outputService
	 * @param ConfigService $configService
	 */
	public function __construct(
		private IUserManager $userManager,
		private IGroupManager $groupManager,
		private CircleRequest $circleRequest,
		private MemberRequest $memberRequest,
		private FederatedUserService $federatedUserService,
		private FederatedEventService $federatedEventService,
		private CircleService $circleService,
		private MembershipService $membershipService,
		private OutputService $outputService,
		private ConfigService $configService,
	) {
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

		// This is useless and too heavy on load
		// we keep it available when running ./occ circles:sync --users
		if ($sync === self::SYNC_USERS) {
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
			} catch (Exception) {
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
			} catch (Exception) {
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
		$this->circleService->updateDisplayName($circle->getSingleId(), $this->groupManager->getDisplayName($groupId));

		$members = array_map(
			fn (Member $member): string => $member->getSingleId(),
			$this->memberRequest->getMembers($circle->getSingleId())
		);

		$group = $this->groupManager->get($groupId);
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
			} catch (Exception) {
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
		} catch (SingleCircleNotFoundException) {
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
			} catch (Exception) {
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
		} catch (CircleNotFoundException) {
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
