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


use ArtificialOwl\MySmallPhpTools\Model\SimpleDataStore;
use ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc22\TNC22Logger;
use ArtificialOwl\MySmallPhpTools\Traits\TStringTools;
use Exception;
use OC;
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
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\MigrationException;
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
use OCA\DAV\CardDAV\ContactsManager;
use OCP\Contacts\IManager;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class SyncService
 *
 * @package OCA\Circles\Service
 */
class SyncService {


	use TStringTools;
	use TNC22Logger;


	const SYNC_APPS = 1;
	const SYNC_USERS = 2;
	const SYNC_GROUPS = 4;
	const SYNC_GLOBALSCALE = 8;
	const SYNC_REMOTES = 16;
	const SYNC_CONTACTS = 32;
	const SYNC_ALL = 63;


	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IDBConnection */
	private $dbConnection;

	/** @var IURLGenerator */
	private $urlGenerator;

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

	/** @var MemberService */
	private $memberService;

	/** @var MembershipService */
	private $membershipService;

	/** @var ContactService */
	private $contactService;

	/** @var TimezoneService */
	private $timezoneService;

	/** @var ConfigService */
	private $configService;


	/** @var IOutput */
	private $migrationOutput;

	/** @var OutputInterface */
	private $occOutput;


	/**
	 * SyncService constructor.
	 *
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param IDBConnection $dbConnection
	 * @param IURLGenerator $urlGenerator
	 * @param CircleRequest $circleRequest
	 * @param MemberRequest $memberRequest
	 * @param FederatedUserService $federatedUserService
	 * @param federatedEventService $federatedEventService
	 * @param CircleService $circleService
	 * @param MemberService $memberService
	 * @param MembershipService $membershipService
	 * @param ContactService $contactService
	 * @param TimezoneService $timezoneService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IUserManager $userManager,
		IGroupManager $groupManager,
		IDBConnection $dbConnection,
		IURLGenerator $urlGenerator,
		CircleRequest $circleRequest,
		MemberRequest $memberRequest,
		FederatedUserService $federatedUserService,
		federatedEventService $federatedEventService,
		CircleService $circleService,
		MemberService $memberService,
		MembershipService $membershipService,
		ContactService $contactService,
		TimezoneService $timezoneService,
		ConfigService $configService
	) {
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->dbConnection = $dbConnection;
		$this->urlGenerator = $urlGenerator;
		$this->circleRequest = $circleRequest;
		$this->memberRequest = $memberRequest;
		$this->federatedUserService = $federatedUserService;
		$this->federatedEventService = $federatedEventService;
		$this->circleService = $circleService;
		$this->memberService = $memberService;
		$this->membershipService = $membershipService;
		$this->contactService = $contactService;
		$this->timezoneService = $timezoneService;
		$this->configService = $configService;

		$this->setup('app', Application::APP_ID);
	}


	/**
	 * @param OutputInterface $output
	 */
	public function setOccOutput(OutputInterface $output): void {
		$this->occOutput = $output;
	}

	/**
	 * @param IOutput $output
	 */
	public function setMigrationOutput(IOutput $output): void {
		$this->migrationOutput = $output;
	}


	/**
	 * @param int $sync
	 *
	 * @return void
	 */
	public function sync(int $sync = self::SYNC_ALL): void {
		if (!is_null($this->migrationOutput)) {
			$this->migrationOutput->startProgress(7);
		}

		if ($this->shouldSync(self::SYNC_APPS, $sync)) {
			$this->syncApps();
		}

		if ($this->shouldSync(self::SYNC_USERS, $sync)) {
			$this->syncNextcloudUsers();
		}

		if ($this->shouldSync(self::SYNC_GROUPS, $sync)) {
			$this->syncNextcloudGroups();
		}

		if ($this->shouldSync(self::SYNC_GLOBALSCALE, $sync)) {
			$this->syncGlobalScale();
		}

		if ($this->shouldSync(self::SYNC_REMOTES, $sync)) {
			$this->syncRemote();
		}

		if ($this->shouldSync(self::SYNC_CONTACTS, $sync)) {
			$this->syncContacts();
		}

		if (!is_null($this->migrationOutput)) {
			$this->migrationOutput->finishProgress();
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
		$this->output('Syncing Nextcloud Apps', true);

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
		$this->output('Syncing Nextcloud Users', true);

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
		return $this->federatedUserService->getLocalFederatedUser($userId);
	}


	/**
	 * @return void
	 */
	public function syncNextcloudGroups(): void {
		$this->output('Syncing Nextcloud Groups', true);

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
		$this->output('Syncing Contacts', true);
	}


	/**
	 * @return void
	 */
	public function syncGlobalScale(): void {
		$this->output('Syncing GlobalScale', true);
	}


	/**
	 * @return void
	 */
	public function syncRemote(): void {
		$this->output('Syncing Remote Instance', true);
	}


	/**
	 * @param string $circleId
	 *
	 * @return void
	 */
	public function syncRemoteCircle(string $circleId): void {
	}


	/**
	 * @param bool $force
	 *
	 * @throws MigrationException
	 * @throws RequestBuilderException
	 */
	public function migration(bool $force = false): void {
		if ($this->configService->getAppValueBool(ConfigService::MIGRATION_RUN)) {
			throw new MigrationException('A migration process is already running');
		}
		$this->configService->setAppValue(ConfigService::MIGRATION_RUN, '1');

		if ($force) {
			$this->configService->setAppValue(ConfigService::MIGRATION_22, '0');
//			$this->configService->setAppValue(ConfigService::MIGRATION_23, '0');
		}

		$this->migrationTo22();
		//	$this->migrationTo23();

		$this->configService->setAppValue(ConfigService::MIGRATION_RUN, '0');
	}

	/**
	 * @return void
	 * @throws RequestBuilderException
	 */
	private function migrationTo22(): void {
		if ($this->configService->getAppValueBool(ConfigService::MIGRATION_22)) {
			return;
		}

		$this->output('Migrating to 22');

		if (!is_null($this->migrationOutput)) {
			$this->migrationOutput->startProgress(2);
		}

		$this->migrationTo22_Circles();
		$this->migrationTo22_Members();

		if (!is_null($this->migrationOutput)) {
			$this->migrationOutput->finishProgress();
		}

		$this->configService->setAppValue(ConfigService::MIGRATION_22, '1');
	}


	/**
	 *
	 * @throws RequestBuilderException
	 */
	private function migrationTo22_Circles(): void {
		$this->output('Migrating Circles', true);

		$circles = [];

		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('*')->from('circle_circles');

		try {
			$cursor = $qb->executeQuery();
			while ($row = $cursor->fetch()) {
				$data = new SimpleDataStore($row);

				$circle = new Circle();
				$circle->setSingleId($data->g('unique_id'))
					   ->setName($data->g('name'))
					   ->setDisplayName($data->g('display_name'))
					   ->setSettings($data->gArray('settings'))
					   ->setDescription($data->g('description'))
					   ->setContactAddressBook($data->gInt('contact_addressbook'))
					   ->setContactGroupName($data->g('contact_groupname'))
					   ->setSource(Member::TYPE_CIRCLE);

				$dTime = $this->timezoneService->getDateTime($data->g('creation'));
				$circle->setCreation($dTime->getTimestamp());

				if ($circle->getDisplayName() === '') {
					$circle->setDisplayName($circle->getName());
				}

				$this->circleService->generateSanitizedName($circle);
				switch ($data->gInt('type')) {
					case 1: // personal
						$config = Circle::CFG_PERSONAL;
						break;

					case 2: // secret
						$config = Circle::CFG_CIRCLE;
						break;

					case 4: // closed
						$config = Circle::CFG_OPEN + Circle::CFG_REQUEST;
						break;

					case 8: // public
						$config = Circle::CFG_OPEN;
						break;
				}

				$circles[] = $circle;
			}

			$cursor->closeCursor();
		} catch (\OCP\DB\Exception $e) {
		}

		foreach ($circles as $circle) {
			/** @var Circle $circle */
			try {
				try {
					$this->circleRequest->getCircle($circle->getSingleId());
				} catch (CircleNotFoundException $e) {
					$this->circleRequest->save($circle);
					usleep(50);
				}
			} catch (InvalidIdException $e) {
			}
		}
	}


	/**
	 * @throws ContactAddressBookNotFoundException
	 * @throws ContactFormatException
	 * @throws ContactNotFoundException
	 * @throws FederatedUserException
	 * @throws InvalidIdException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 */
	private function migrationTo22_Members(): void {
		$this->output('Migrating Members', true);

		$members = [];

		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('*')->from('circle_members');

		$appCircle = $this->federatedUserService->getAppInitiator(
			Application::APP_ID,
			Member::APP_CIRCLES
		);

		try {
			$cursor = $qb->executeQuery();
			while ($row = $cursor->fetch()) {
				$data = new SimpleDataStore($row);

				$member = new Member();

				$member->setCircleId($data->g('circle_id'))
					   ->setId($data->g('member_id'))
					   ->setUserId($data->g('user_id'))
					   ->setInstance($data->g('instance'))
					   ->setDisplayName($data->g('cached_name'))
					   ->setLevel($data->gInt('level'))
					   ->setStatus($data->g('status'))
					   ->setContactMeta($data->g('contact_meta'))
					   ->setContactId($data->g('contact_id'))
					   ->setInvitedBy($appCircle);

				switch ($data->gInt('user_type')) {
					case 1:
						$member->setUserType(1);
						break;
					case 2:
						$member->setUserType(2);
						break;
					case 3:
						$member->setUserType(4);
						break;
					case 4:
						$member->setUserType(8);
						$this->fixContactId($member);
						break;
				}

				try {
					$singleMember = $this->federatedUserService->getFederatedUser(
						$member->getUserId(),
						$member->getUserType()
					);
				} catch (ContactFormatException $e) {
					continue;
				}
				$member->setSingleId($singleMember->getSingleId());

//					"cached_update":"2021-05-02 12:13:22",
//					"joined":"2021-05-02 12:13:22",
//					"contact_checked":null,"
//					single_id":"wt6WQYYCry3EOud",
//					"circle_source":null}

				$members[] = $member;
			}
			$cursor->closeCursor();
		} catch (\OCP\DB\Exception $e) {
		}

		foreach ($members as $member) {
			try {
				$this->memberRequest->getMemberById($member->getId());
			} catch (MemberNotFoundException $e) {
				$this->memberRequest->save($member);
			}
		}
	}

	/**
	 * @param string $message
	 * @param bool $advance
	 */
	private function output(string $message, bool $advance = false): void {
		if (!is_null($this->occOutput)) {
			$this->occOutput->writeln((($advance) ? '+' : '-') . ' ' . $message);
		}

		if (!is_null($this->migrationOutput)) {
			if ($advance) {
				$this->migrationOutput->advance(1, '(Circles) ' . $message);
			} else {
				$this->migrationOutput->info('(Circles) ' . $message);
			}
		}
	}


	/**
	 * @param Member $member
	 *
	 * @throws ContactAddressBookNotFoundException
	 */
	private function fixContactId(Member $member) {
		list($userId, $contactId) = explode(':', $member->getUserId());

		$contactsManager = OC::$server->get(ContactsManager::class);

		/** @var IManager $cm */
		$cm = OC::$server->get(IManager::class);
		$contactsManager->setupContactsProvider($cm, $userId, $this->urlGenerator);

		$contact = $cm->search($contactId, ['UID']);
		if (sizeof($contact) === 1) {
			$entry = array_shift($contact);
			$addressBook =
				$this->contactService->getAddressBoxById($cm, $this->get('addressbook-key', $entry));

			$member->setUserId($userId . '/' . $addressBook->getUri() . '/' . $contactId);
		}

		echo $member->getUserId() . "\n";
	}


}

