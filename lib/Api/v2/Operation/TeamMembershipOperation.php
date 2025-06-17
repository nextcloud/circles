<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Api\v2\Operation;

use OCA\Circles\Api\v2\ITeamMembershipOperation;
use OCA\Circles\Managers\TeamMembershipManager;
use OCA\Circles\Service\TeamMembershipService;

class TeamMembershipOperation extends CoreOperation implements ITeamMembershipOperation {
	public function __construct(
		private readonly TeamMembershipService $teamMembershipService,
		private readonly TeamMembershipManager $teamMembershipManager,
	) {
	}

	public function syncTeamMemberships(string $singleId): void {
		$this->lowPriorityProcess();
		$this->teamMembershipService->syncTeamMemberships($this->getSession(), $singleId);
	}


}
