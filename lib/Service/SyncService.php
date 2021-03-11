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
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\GroupNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MigrationTo22Exception;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedUser;
use OCP\IGroupManager;
use OCP\IUserManager;

/**
 * Class SyncService
 *
 * @package OCA\Circles\Service
 */
class SyncService {


	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var GroupService */
	private $groupService;

	/** @var ConfigService */
	private $configService;


	/**
	 * SyncService constructor.
	 *
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param FederatedUserService $federatedUserService
	 * @param GroupService $groupService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IUserManager $userManager,
		IGroupManager $groupManager,
		FederatedUserService $federatedUserService,
		GroupService $groupService,
		ConfigService $configService
	) {
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->federatedUserService = $federatedUserService;
		$this->groupService = $groupService;
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


	public function syncAll(): void {
		$this->syncNextcloudUsers();
		$this->syncNextcloudGroups();
		$this->syncContacts();
		$this->syncGlobalScale();
		$this->syncRemote();
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
				$this->syncNextcloudUser($group->getGID());
			} catch (Exception $e) {
			}
		}
	}

	/**
	 * @param string $groupId
	 *
	 * @return Circle
	 * @throws GroupNotFoundException
	 */
	public function syncNextcloudGroup(string $groupId): Circle {
		return $this->groupService->getGroupCircle($groupId);
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

