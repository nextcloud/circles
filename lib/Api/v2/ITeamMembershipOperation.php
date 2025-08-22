<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Api\v2;

/**
 * TeamMemberships is a caching system to help managing direct and inherited memberships
 * to lighten database request.
 */
interface ITeamMembershipOperation {
	/**
	 * create/remove entries in database using Teams and Teams' Members to keep Memberships up-to-date
	 *
	 * @param string $singleId
	 */
	public function syncTeamMemberships(string $singleId): void;
}
