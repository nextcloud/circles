<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Api\v2\Operation;

use OCA\Circles\Api\v2\ITeamMemberOperation;
use OCA\Circles\Managers\TeamMemberManager;
use OCA\Circles\Model\TeamEntity;

/**
 * TeamMemberOperation uses heavier process to verify permissions when accessing data.
 *
 * It is strongly advised to use TeamMembershipOperation for most of your actions
 * as it manage direct and inherited memberships only.
 *
 */
class TeamMemberOperation extends CoreOperation implements ITeamMemberOperation {
	public function __construct(
		private readonly TeamMemberManager $teamMemberManager,
	) {
	}

	public function getTeamMembers(string $singleId): array {
		$this->confirmEntityInitialized();
		return $this->teamMemberManager->getMembersFromTeam($this->entity, $singleId);
	}
}
