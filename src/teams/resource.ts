/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { RouteLocationRaw } from 'vue-router'

/**
 * Open a shared resource without leaving the selected team.
 *
 * @param teamId - Active team identifier.
 * @param providerId - Resource provider identifier.
 * @param resourceId - Identifier within that provider.
 */
export function teamResourceRoute(teamId: string, providerId: string, resourceId: string | number): RouteLocationRaw {
	return {
		name: 'team-resource',
		params: { teamId, providerId, resourceId: String(resourceId) },
	}
}
