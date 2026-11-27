/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { RouteLocationRaw } from 'vue-router'
import type { TeamPage } from './api.ts'
import type { TeamResources } from './resourcesStore.ts'
import type { AppNavigationEntry } from './teamAppIntegrations/teamAppsStore.ts'

export interface NavigationEntry {
	id: string
	label: string
	icon?: string
	iconSvg?: string
	iconURL?: string
	to?: RouteLocationRaw
	href?: string
	openInNewTab?: boolean
	page?: TeamPage
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
export function isReferenceEntry(entry: Pick<AppNavigationEntry, 'reference'>): boolean {
	return entry.reference?.accessible === true && !!entry.reference.richObjectType && entry.reference.richObjectType !== 'open-graph'
}

/**
 *
 * @param resources Native team resources
 * @param appsSettled Whether provider loads have settled
 */
export function firstTabSettled(resources: TeamResources, appsSettled = true): boolean {
	return (resources.orderChecked || resources.orderError) && (resources.pagesChecked || resources.pagesError) && appsSettled
}

/**
 *
 * @param teamId Team to open
 * @param resources Native team resources
 * @param apps Provider-owned entries
 */
export function firstTabRoute(teamId: string, resources: TeamResources, apps: AppNavigationEntry[] = []): RouteLocationRaw {
	for (const item of resources.navigation) {
		if (!('providerId' in item)) {
			if (item.id.startsWith('page-')) {
				const page = resources.pages.find((page) => item.id === `page-${page.fileId}`)
				if (page) {
					return { name: 'team-page', params: { teamId, fileId: String(page.fileId) } }
				}
				continue
			}
			return { name: item.id === 'home' ? 'team-home' : 'team-folder', params: { teamId } }
		}
		const entry = apps.find(({ id }) => id === item.id)
		if (entry && isReferenceEntry(entry)) {
			return appEntryRoute(teamId, entry)
		}
	}
	return { name: 'team-folder', params: { teamId } }
}
