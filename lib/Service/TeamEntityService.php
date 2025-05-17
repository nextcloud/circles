<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use Closure;
use NCU\Config\IUserConfig;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Enum\TeamEntityType;
use OCA\Circles\Exceptions\TeamEntityNotFoundException;
use OCA\Circles\Managers\TeamEntityManager;
use OCA\Circles\Managers\TeamManager;
use OCA\Circles\Model\TeamEntity;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserManager;

class TeamEntityService {
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly IUserConfig $userConfig,
		private readonly IUserManager $userManager,
		private readonly TeamManager $teamManager,
		private readonly TeamEntityManager $teamEntityManager,
	) {
	}

	/**
	 * @throws TeamEntityNotFoundException
	 */
	public function getTeamEntity(string $singleId): TeamEntity {
		return $this->teamEntityManager->getTeamEntity($singleId);
	}

	public function generateTeamEntityFromLocalUser(string $userId): TeamEntity {
		$singleId = $this->userConfig->getValueString($userId, Application::APP_ID, 'teamSingleId');
		if ($singleId !== '') {
			return $this->generateTeamEntity($singleId, TeamEntityType::LOCAL_USER, $userId);
		}

		$user = $this->userManager->get($userId);
		if ($user === null) {
			throw new TeamEntityNotFoundException('local user not found');
		}
		return $this->generateTeamEntityFromUser($user);
	}

	/**
	 * @throws \OCA\Circles\Exceptions\TeamNotFoundException
	 */
	public function generateTeamEntityFromTeam(string $singleId): TeamEntity {
		return $this->teamManager->getTeam($singleId)->asEntity();
	}

	public function generateTeamEntityFromApp(string $appId): TeamEntity {
		$singleId = $this->appConfig->getValueString($appId, 'teamSingleId');
		if ($singleId !== '') {
			return $this->generateTeamEntity($singleId, TeamEntityType::APP, $appId, $appId);
		}

		try {
			$teamEntity = $this->teamEntityManager->searchTeamEntity(TeamEntityType::APP, $appId);
		} catch (TeamEntityNotFoundException) {
			$teamEntity = $this->teamEntityManager->createTeamEntity(TeamEntityType::APP, $appId, $appId);
		}
		$this->appConfig->setValueString($appId, 'teamSingleId', $teamEntity->getSingleId());

		return $teamEntity;
	}

	public function generateTeamEntityFromOcc(): TeamEntity {
		$singleId = $this->appConfig->getValueString(Application::APP_ID, 'occSingleId');
		if ($singleId !== '') {
			return $this->generateTeamEntity($singleId, TeamEntityType::OCC, 'occ', 'Occ Command');
		}

		try {
			$teamEntity = $this->teamEntityManager->searchTeamEntity(TeamEntityType::OCC, 'occ');
		} catch (TeamEntityNotFoundException) {
			$teamEntity = $this->teamEntityManager->createTeamEntity(TeamEntityType::OCC, 'occ', 'Occ Command');
		}
		$this->appConfig->setValueString(Application::APP_ID, 'occSingleId', $teamEntity->getSingleId());

		return $teamEntity;
	}

	public function generateTeamEntityFromSuperAdmin(): TeamEntity {
		$singleId = $this->appConfig->getValueString(Application::APP_ID, 'superAdminSingleId');
		if ($singleId !== '') {
			return $this->generateTeamEntity($singleId, TeamEntityType::SUPER_ADMIN, 'superAdmin', 'Super Admin Script');
		}

		try {
			$teamEntity = $this->teamEntityManager->searchTeamEntity(TeamEntityType::SUPER_ADMIN, 'superAdmin');
		} catch (TeamEntityNotFoundException) {
			$teamEntity = $this->teamEntityManager->createTeamEntity(TeamEntityType::SUPER_ADMIN, 'superAdmin', 'Super Admin Script');
		}
		$this->appConfig->setValueString(Application::APP_ID, 'superAdminSingleId', $teamEntity->getSingleId());

		return $teamEntity;
	}

	public function generateTeamEntityFromUser(IUser $user): TeamEntity {
		$singleId = $this->userConfig->getValueString($user->getUID(), Application::APP_ID, 'teamSingleId');
		if ($singleId === '') {
			try {
				$teamEntity = $this->teamEntityManager->searchTeamEntity(TeamEntityType::LOCAL_USER, $user->getUID());
			} catch (TeamEntityNotFoundException) {
				$teamEntity = $this->teamEntityManager->createTeamEntity(TeamEntityType::LOCAL_USER, $user->getUID(), $user->getDisplayName());
			}
			$this->userConfig->setValueString($user->getUID(), Application::APP_ID, 'teamSingleId', $teamEntity->getSingleId());
			return $teamEntity;
		}

		return $this->generateTeamEntity($singleId, TeamEntityType::LOCAL_USER, $user->getUID(), $user->getDisplayName());
	}

	private function generateTeamEntity(string $singleId, TeamEntityType $type, string $origId, string $displayName = ''): TeamEntity {
		$entity = new TeamEntity();
		$entity->setSingleId($singleId);
		$entity->setTeamEntityType($type);
		$entity->setOrigId($origId);
		$entity->setDisplayName($displayName);
		return $entity;
	}

}
