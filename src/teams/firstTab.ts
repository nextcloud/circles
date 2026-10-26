/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { RouteLocationRaw } from 'vue-router'
import type { AppNavigationEntry } from './integrations/store.ts'
import type { TeamResources } from './resourcesStore.ts'

export interface NavigationEntry {
	id: string
	label: string
	icon?: string
	iconSvg?: string
	iconEmoji?: string
	to?: RouteLocationRaw
	href?: string
	legacyIds?: string[]
	actions?: AppNavigationEntry['actions']
}

/**
 *
 * @param teamId Team owning the entry
 * @param entry Provider-owned navigation entry
 */
export function appEntryRoute(teamId: string, entry: AppNavigationEntry): RouteLocationRaw {
	return { name: 'team-app', params: { teamId, providerId: entry.providerId, entryId: entry.entryId } }
}

/**
 *
 * @param entries Entries in their natural order
 * @param order Saved canonical or legacy IDs
 */
export function orderEntries<T extends { id: string, legacyIds?: string[] }>(entries: T[], order: string[]): T[] {
	const position = new Map(order.map((id, index) => [id, index]))
	const rank = (entry: T): number => Math.min(...[entry.id, ...entry.legacyIds ?? []]
		.map((id) => position.get(id) ?? Infinity))
	return [...entries].sort((a, b) => rank(a) - rank(b))
}

/**
 *
 * @param resources Native team resources
 * @param appsSettled Whether provider loads have settled
 */
export function firstTabSettled(resources: TeamResources, appsSettled = true): boolean {
	return (resources.orderChecked || resources.orderError) && appsSettled
}

/**
 *
 * @param teamId Team to open
 * @param resources Native team resources
 * @param apps Provider-owned entries
 */
export function firstTabRoute(teamId: string, resources: TeamResources, apps: AppNavigationEntry[] = []): RouteLocationRaw {
	const targets = [
		{ id: 'team-folder', to: { name: 'team-folder', params: { teamId } } as RouteLocationRaw },
		...apps.filter((entry) => !entry.href).map((entry) => ({ ...entry, to: appEntryRoute(teamId, entry) })),
		{ id: 'home', to: { name: 'team-home', params: { teamId } } as RouteLocationRaw },
	]
	return orderEntries(targets, resources.tabOrder)[0]!.to
}
