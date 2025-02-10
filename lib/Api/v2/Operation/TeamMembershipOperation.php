<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Api\v2\Operation;

class TeamMembershipOperation extends CoreOperation implements ITeamMembershipOperation {
	public function __construct() {
	}

	public function syncTeamMemberships(string $singleId): void {
		$this->confirmEntityInitialized();

	}

}
