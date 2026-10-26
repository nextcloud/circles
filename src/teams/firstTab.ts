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
	to?: RouteLocationRaw
	href?: string
	referenceUrl?: string
	openInNewTab?: boolean
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
 * Whether an app entry should open as an embedded reference preview.
 *
 * @param entry Provider-owned navigation entry
 */
export function isReferenceEntry(entry: Pick<AppNavigationEntry, 'openMode' | 'referenceUrl'>): boolean {
	return Boolean(entry.referenceUrl) && entry.openMode !== 'link'
}

/**
 *
 * @param entries Entries in their natural order
 * @param order Saved canonical IDs
 */
export function orderEntries<T extends { id: string }>(entries: T[], order: string[]): T[] {
	const position = new Map(order.map((id, index) => [id, index]))
	const rank = (entry: T): number => position.get(entry.id) ?? Infinity
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
		...apps.filter(isReferenceEntry).map((entry) => ({ ...entry, to: appEntryRoute(teamId, entry) })),
		{ id: 'home', to: { name: 'team-home', params: { teamId } } as RouteLocationRaw },
	]
	return orderEntries(targets, resources.tabOrder)[0]!.to
}
