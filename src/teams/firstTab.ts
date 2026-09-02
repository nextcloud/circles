/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { RouteLocationRaw } from 'vue-router'
import type { TeamResources } from './resourcesStore.ts'

/**
 * Whether enough of a team's resource state is known to resolve the first
 * navigation entry: the tab order and the pages, the only order-dependent
 * entries with in-app routes.
 *
 * @param resources - The team's resource state
 */
export function firstTabSettled(resources: TeamResources): boolean {
	const orderSettled = resources.orderChecked || resources.orderError
	const pagesSettled = resources.pagesChecked || resources.pagesError || resources.folderError
	return orderSettled && pagesSettled
}

/**
 * The route of the first entry in the team navigation, following the saved
 * tab order like the sidebar does; entries the order does not know keep
 * their natural order behind the known ones. External entries (collective,
 * boards) cannot be a landing page and are skipped.
 *
 * @param teamId - The team whose navigation is resolved
 * @param resources - The team's resource state
 */
export function firstTabRoute(teamId: string, resources: TeamResources): RouteLocationRaw {
	const targets = new Map<string, RouteLocationRaw>()
	targets.set('team-folder', { name: 'team-folder', params: { teamId } })
	for (const page of resources.pages) {
		targets.set(`page-${page.fileId}`, { name: 'team-page', params: { teamId, fileId: String(page.fileId) } })
	}
	targets.set('home', { name: 'team-home', params: { teamId } })

	const known = resources.tabOrder.filter((id) => targets.has(id))
	const unknown = [...targets.keys()].filter((id) => !resources.tabOrder.includes(id))
	return targets.get([...known, ...unknown][0]!)!
}
