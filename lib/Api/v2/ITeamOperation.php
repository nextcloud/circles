<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Api\v2;

use OCA\Circles\Exceptions\TeamEntityNotFoundException;
use OCA\Circles\Model\Team;
use OCA\Circles\Model\TeamEntity;
use OCA\Circles\Model\TeamMember;

interface ITeamOperation {

	/**
	 * @throws TeamEntityNotFoundException
	 */
	public function createTeam(string $teamName, ?TeamEntity $realOwner = null): Team;

	/**
	 * get list of teams current entity is member of
	 *
	 * @return TeamMember[]
	 */
	public function getTeams(): array;
	public function getAvailableTeams(): array;
	public function getTeam(string $singleId): Team;
}
