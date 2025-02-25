<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Api\v2;

use OCA\Circles\Model\TeamEntity;
use OCP\IUser;

interface ITeamEntityOperation {
	public function getFromLocalUser(string $userId): TeamEntity;
	public function getFromTeam(string $singleId): TeamEntity;
	public function getFromApp(string $appId): TeamEntity;
	public function getFromOcc(): TeamEntity;
	public function getFromSuperAdmin(): TeamEntity;
	public function getFromUser(IUser $user): TeamEntity;
}
