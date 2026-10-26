/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TeamResources } from './resourcesStore.ts'

import { describe, expect, it } from 'vitest'
import { firstTabRoute, firstTabSettled } from './firstTab.ts'

const TEAM = 'team-1'

function resources(overrides: Partial<TeamResources> = {}): TeamResources {
	return {
		folder: null,
		folderChecked: true,
		folderError: false,
		resources: [],
		resourcesChecked: true,
		resourcesError: false,
		pages: [],
		pagesChecked: true,
		pagesError: false,
		navigation: [{ id: 'team-folder' }, { id: 'home' }],
		orderChecked: true,
		orderError: false,
		...overrides,
	}
}

function page(fileId: number, title: string) {
	return {
		id: `text-${fileId}`,
		entryId: String(fileId),
		providerId: 'text',
		label: title,
		href: `/f/${fileId}`,
		referenceUrl: `/f/${fileId}`,
		reference: {
			accessible: true,
			richObjectType: 'file',
			richObject: {},
			openGraphObject: { id: String(fileId), name: title, description: null, thumb: null, link: `/f/${fileId}` },
		},
	}
}

describe('firstTabRoute', () => {
	it('falls back to the team folder without a saved order', () => {
		expect(firstTabRoute(TEAM, resources()))
			.toEqual({ name: 'team-folder', params: { teamId: TEAM } })
	})

	it('follows the saved order', () => {
		const state = resources({ navigation: [{ id: 'home' }, { id: 'team-folder' }] })
		expect(firstTabRoute(TEAM, state))
			.toEqual({ name: 'team-home', params: { teamId: TEAM } })
	})

	it('opens a saved native page and skips deleted pages', () => {
		const state = resources({
			navigation: [{ id: 'page-99' }, { id: 'page-42' }, { id: 'home' }],
			pages: [{ fileId: 42, title: 'Guide', filePath: '/Team/.system/teams/pages/Guide.md' }],
		})
		expect(firstTabRoute(TEAM, state)).toEqual({ name: 'team-page', params: { teamId: TEAM, fileId: '42' } })
	})

	it('resolves a page as the first entry', () => {
		const state = resources({
			navigation: [{ id: 'text-7', providerId: 'text', entryId: '7' }, { id: 'team-folder' }, { id: 'home' }],
		})
		expect(firstTabRoute(TEAM, state, [page(7, 'Handbook')]))
			.toEqual({ name: 'team-app', params: { teamId: TEAM, providerId: 'text', entryId: '7' } })
	})

	it('skips entries with only a generic Open Graph preview', () => {
		const state = resources({ navigation: [{ id: 'calendar-1', providerId: 'calendar', entryId: '1' }, { id: 'home' }, { id: 'team-folder' }] })
		expect(firstTabRoute(TEAM, state, [{ ...page(1, 'Calendar'), id: 'calendar-1', providerId: 'calendar', reference: { ...page(1, 'Calendar').reference, richObjectType: 'open-graph' } }]))
			.toEqual({ name: 'team-home', params: { teamId: TEAM } })
	})

	it('skips entries whose provider metadata is unavailable', () => {
		const state = resources({ navigation: [{ id: 'text-9', providerId: 'text', entryId: '9' }, { id: 'home' }, { id: 'team-folder' }] })
		expect(firstTabRoute(TEAM, state))
			.toEqual({ name: 'team-home', params: { teamId: TEAM } })
	})

	it('follows navigation without sorting it against provider entries', () => {
		const state = resources({
			navigation: [{ id: 'home' }, { id: 'team-folder' }, { id: 'text-7', providerId: 'text', entryId: '7' }],
		})
		expect(firstTabRoute(TEAM, state, [page(7, 'Handbook')]))
			.toEqual({ name: 'team-home', params: { teamId: TEAM } })
	})
})

describe('firstTabSettled', () => {
	it('waits for the order and registered providers', () => {
		expect(firstTabSettled(resources({ orderChecked: false }))).toBe(false)
		expect(firstTabSettled(resources(), false)).toBe(false)
		expect(firstTabSettled(resources({ pagesChecked: false }))).toBe(false)
		expect(firstTabSettled(resources({ pagesChecked: false, pagesError: true }))).toBe(true)
		expect(firstTabSettled(resources())).toBe(true)
	})

	it('settles on errors instead of waiting forever', () => {
		expect(firstTabSettled(resources({ orderChecked: false, orderError: true }))).toBe(true)
		expect(firstTabSettled(resources({ folderError: true }))).toBe(true)
	})
})
