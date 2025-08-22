<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Api\v2\Operation;

use OCA\Circles\Api\v2\ITeamEntityOperation;
use OCA\Circles\Model\TeamEntity;
use OCA\Circles\Service\TeamEntityService;
use OCP\IUser;

class TeamEntityOperation extends CoreOperation implements ITeamEntityOperation {
	public function __construct(
		private readonly TeamEntityService $teamEntityService,
	) {
	}

	public function getFromLocalUser(string $userId): TeamEntity {
		return $this->teamEntityService->generateTeamEntityFromLocalUser($userId);
	}

	public function getFromTeam(string $singleId): TeamEntity {
		return $this->teamEntityService->generateTeamEntityFromTeam($singleId);
	}

	public function getFromApp(string $appId): TeamEntity {
		return $this->teamEntityService->generateTeamEntityFromApp($appId);
	}

	public function getFromOcc(): TeamEntity {
		return $this->teamEntityService->generateTeamEntityFromOcc();
	}

	public function getFromSuperAdmin(): TeamEntity {
		return $this->teamEntityService->generateTeamEntityFromSuperAdmin();
	}

	public function getFromUser(IUser $user): TeamEntity {
		return $this->teamEntityService->generateTeamEntityFromUser($user);
	}
}
