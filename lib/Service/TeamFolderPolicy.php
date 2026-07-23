<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use OCA\Circles\Model\Circle;

/**
 * Policy interface for team folder creation.
 *
 * The circles app owns the *policy* (auto-create toggle, default quota, which
 * circle types qualify); the groupfolders app owns the *orchestration*
 * (creating, unlinking, removing folders) and consumes this interface to ask
 * circles whether a given circle should get a team folder and what quota to
 * assign, without duplicating the policy logic. The circles app keeps no
 * reference to the groupfolders app.
 *
 * Implementation: {@see TeamFolderService} in the circles app.
 */
interface TeamFolderPolicy {
	/**
	 * Whether a team folder should be auto-created for the given circle.
	 *
	 * Personal, hidden, system, and backend circles are excluded; the
	 * `team_folder_auto_create` app config must be enabled.
	 */
	public function shouldCreateTeamFolder(Circle $circle): bool;

	/**
	 * The configured default quota in bytes for auto-created team folders.
	 *
	 * A value of 0 or less means unlimited.
	 */
	public function getDefaultQuota(): int;
}
