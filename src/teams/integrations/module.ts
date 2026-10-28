/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TeamAppView } from './types.ts'

/**
 * Load the ES module declared by a PHP provider.
 *
 * @param url Same-origin module URL
 */
export async function loadTeamAppModule(url: string): Promise<{ apiVersion: number, default: TeamAppView }> {
	return import(/* @vite-ignore */ url)
}
