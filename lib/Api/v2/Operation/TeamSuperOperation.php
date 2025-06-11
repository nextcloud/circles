<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Api\v2\Operation;

use NCU\Config\IUserConfig;
use OCA\Circles\Api\v2\ITeamSuperOperation;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Enum\TeamEntityType;
use OCA\Circles\Managers\TeamEntityManager;
use OCA\Circles\Managers\TeamManager;
use OCA\Circles\Managers\TeamMemberManager;
use OCA\Circles\Managers\TeamMembershipManager;
use OCP\IAppConfig;

class TeamSuperOperation extends CoreOperation implements ITeamSuperOperation {
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly IUserConfig $userConfig,
		private readonly TeamManager $teamManager,
		private readonly TeamEntityManager $teamEntityManager,
		private readonly TeamMemberManager $teamMemberManager,
		private readonly TeamMembershipManager $teamMembershipManager,
	) {
	}

	public function dropAllData(): void {
		$this->lowPriorityProcess([TeamEntityType::SUPER_ADMIN]);

		$this->teamManager->dropAll();
		$this->teamEntityManager->dropAll();
		$this->teamMemberManager->dropAll();
		$this->teamMembershipManager->dropAll();

		$this->userConfig->deleteKey(Application::APP_ID, 'teamSingleId');
		$this->appConfig->deleteKey(Application::APP_ID, 'occSingleId');
		$this->appConfig->deleteKey(Application::APP_ID, 'superAdminSingleId');
		foreach($this->appConfig->searchValues('teamSingleId') as $appId => $value) {
			$this->appConfig->deleteKey($appId, 'teamSingleId');
		}
	}
}
