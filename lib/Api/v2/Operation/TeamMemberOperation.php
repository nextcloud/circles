<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Api\v2\Operation;

use OCA\Circles\Api\v2\ITeamMemberOperation;
use OCA\Circles\Managers\TeamMemberManager;
use OCA\Circles\Model\Team;
use OCA\Circles\Model\TeamEntity;
use OCA\Circles\Model\TeamMember;
use OCA\Circles\Service\TeamEntityService;
use OCA\Circles\Service\TeamMemberService;
use OCA\Circles\Service\TeamService;

/**
 * TeamMemberOperation uses heavier process to verify permissions when accessing data.
 *
 * It is strongly advised to use TeamMembershipOperation for most of your actions
 * as it manage direct and inherited memberships only.
 *
 */
class TeamMemberOperation extends CoreOperation implements ITeamMemberOperation {
	public function __construct(
		private readonly TeamService $teamService,
		private readonly TeamEntityService $teamEntityService,
		private readonly TeamMemberService $teamMemberService,
		private readonly TeamMemberManager $teamMemberManager,
	) {
	}

	public function getTeamMembers(string $singleId): array {
		$this->confirmSessionInitialized();
		return $this->teamMemberManager->getMembersFromTeam($this->getEntity(), $singleId);
	}

	public function addMember(Team|string $team, TeamEntity|string $entity): TeamMember {
		$this->confirmSessionInitialized();

		if (!($team instanceof Team)) {
			$team = $this->teamService->getTeam($this->getEntity(), $team);
		}
		if (!($entity instanceof TeamEntity)) {
			$entity = $this->teamEntityService->getTeamEntity($entity);
		}

		return $this->teamMemberService->addMember($this->getEntity(), $team, $entity);
	}
}
