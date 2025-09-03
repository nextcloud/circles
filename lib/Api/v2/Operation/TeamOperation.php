<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Api\v2\Operation;

use OCA\Circles\Api\v2\ITeamOperation;
use OCA\Circles\Enum\TeamEntityType;
use OCA\Circles\Exceptions\TeamEntityNotFoundException;
use OCA\Circles\Exceptions\TeamNotFoundException;
use OCA\Circles\Managers\TeamManager;
use OCA\Circles\Model\Team;
use OCA\Circles\Model\TeamEntity;
use OCA\Circles\Model\TeamMember;
use OCA\Circles\Service\TeamService;

class TeamOperation extends CoreOperation implements ITeamOperation {
	public function __construct(
		private TeamService $teamService,
		private TeamManager $teamManager,
	) {
	}

	/**
	 * @throws TeamEntityNotFoundException
	 */
	public function createTeam(string $teamName, ?TeamEntity $realOwner = null): Team {
		$this->confirmSessionInitialized();

		// only APP, OCC and SUPER_ADMIN can create a team under the name of someone else
		if (!in_array($this->getEntity()->getTeamEntityType(), [
			TeamEntityType::APP,
			TeamEntityType::OCC,
			TeamEntityType::SUPER_ADMIN,
		],true)) {
			$realOwner = null;
		}

		return $this->teamService->create($this->getEntity(), $teamName, $realOwner ?? null);
	}

	/**
	 * @throws TeamNotFoundException
	 */
	public function getTeam(string $singleId): Team {
		$this->confirmSessionInitialized();
		return $this->teamManager->getTeam($singleId);
	}

	/**
	 * @return TeamMember[]
	 */
	public function getTeams(): array {
		$this->confirmSessionInitialized();
		return [];
	}

	public function getAvailableTeams(): array {
		$this->confirmSessionInitialized();
		return [];
	}
}
