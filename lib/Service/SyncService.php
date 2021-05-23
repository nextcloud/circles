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


use daita\MySmallPhpTools\Traits\TStringTools;
use Exception;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\GroupNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotConfirmedException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MigrationTo22Exception;
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
use OCP\IGroupManager;
use OCP\IUserManager;


/**
 * Class SyncService
 *
 * @package OCA\Circles\Service
 */
class SyncService {


	use TStringTools;


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

	/** @var MemberService */
	private $memberService;

	/** @var MembershipService */
	private $membershipService;

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
	 * @param MemberService $memberService
	 * @param MembershipService $membershipService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IUserManager $userManager,
		IGroupManager $groupManager,
		CircleRequest $circleRequest,
		MemberRequest $memberRequest,
		FederatedUserService $federatedUserService,
		federatedEventService $federatedEventService,
		MemberService $memberService,
		MembershipService $membershipService,
		ConfigService $configService
	) {
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->circleRequest = $circleRequest;
		$this->memberRequest = $memberRequest;
		$this->federatedUserService = $federatedUserService;
		$this->federatedEventService = $federatedEventService;
		$this->memberService = $memberService;
		$this->membershipService = $membershipService;
		$this->configService = $configService;
	}


	/**
	 * @return bool
	 * @throws MigrationTo22Exception
	 */
	public function migration(): bool {
		if ($this->configService->getAppValueBool(ConfigService::MIGRATION_22)) {
			return false;
		}

		$this->migrationTo22();
		$this->configService->setAppValue(ConfigService::MIGRATION_22, '1');

		return true;

	}

	/**
	 * @return void
	 * @throws MigrationTo22Exception
	 */
	private function migrationTo22(): void {
		throw new MigrationTo22Exception('migration failed');
	}


	/**
	 * @return void
	 */
	public function syncAll(): void {
		$this->syncNextcloudUsers();
		$this->syncGlobalScale();
		$this->syncRemote();
		$this->syncNextcloudGroups();
		$this->syncContacts();
	}


	/**
	 * @return void
	 */
	public function syncNextcloudUsers(): void {
		foreach ($this->userManager->search('') as $user) {
			try {
				$this->syncNextcloudUser($user->getUID());
			} catch (Exception $e) {
			}
		}
	}

	/**
	 * @param string $userId
	 *
	 * @return FederatedUser
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws SingleCircleNotFoundException
	 * @throws RequestBuilderException
	 */
	public function syncNextcloudUser(string $userId): FederatedUser {
		return $this->federatedUserService->getLocalFederatedUser($userId);
	}


	/**
	 * @return void
	 */
	public function syncNextcloudGroups(): void {
		foreach ($this->groupManager->search('') as $group) {
			try {
				$this->syncNextcloudGroup($group->getGID());
			} catch (Exception $e) {
			}
		}
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
		$circle = $this->federatedUserService->getGroupCircle($groupId);
		$group = $this->groupManager->get($groupId);
		foreach ($group->getUsers() as $user) {
			$member = $this->generateGroupMember($circle, $user->getUID());
			$event = new FederatedEvent(SingleMemberAdd::class);
			$event->setCircle($circle);
			$event->setMember($member);
			$this->federatedEventService->newEvent($event);

//			$this->memberRequest->insertOrUpdate($member);
		}

//		$this->membershipService->onUpdate($circle->getSingleId());

		return $circle;
	}


	/**
	 * @param string $userId
	 *
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 */
	public function userDeleted(string $userId): void {
		$federatedUser = $this->federatedUserService->getLocalFederatedUser($userId);

		// TODO: check existing circle generated by user !
		$this->circleRequest->delete($federatedUser->getBasedOn());
		$this->memberRequest->deleteAllFromCircle($federatedUser->getBasedOn());
		$this->membershipService->onUpdate($federatedUser->getSingleId());
	}


	/**
	 * @param string $groupId
	 *
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

		$owner = $this->federatedUserService->getAppInitiator(Application::APP_ID, Member::APP_CIRCLES);

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
		$member->setId($this->uuid(ManagedModel::ID_LENGTH));
		$member->setCircleId($circle->getSingleId());
		$member->setLevel(Member::LEVEL_MEMBER);
		$member->setStatus(Member::STATUS_MEMBER);

		return $member;
	}


	/**
	 * @param string $groupId
	 * @param string $userId
	 *
	 * @return Member
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

//		$this->memberRequest->insertOrUpdate($member);

//		$this->membershipService->onUpdate($member->getSingleId());
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
	}


	/**
	 * @return void
	 */
	public function syncGlobalScale(): void {
	}


	/**
	 * @return void
	 */
	public function syncRemote(): void {
	}


	/**
	 * @param string $circleId
	 *
	 * @return void
	 */
	public function syncRemoteCircle(string $circleId): void {
	}


}

