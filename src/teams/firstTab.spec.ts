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
		tabOrder: [],
		orderChecked: true,
		orderError: false,
		...overrides,
	}
}

const page = (fileId: number, title: string) => ({
	id: `text:${fileId}`, entryId: String(fileId), providerId: 'text', label: title, legacyIds: [`page-${fileId}`],
})

describe('firstTabRoute', () => {
	it('falls back to the team folder without a saved order', () => {
		expect(firstTabRoute(TEAM, resources()))
			.toEqual({ name: 'team-folder', params: { teamId: TEAM } })
	})

	it('follows the saved order', () => {
		const state = resources({ tabOrder: ['home', 'team-folder'] })
		expect(firstTabRoute(TEAM, state))
			.toEqual({ name: 'team-home', params: { teamId: TEAM } })
	})

	it('resolves a page as the first entry', () => {
		const state = resources({
			tabOrder: ['page-7', 'team-folder', 'home'],
		})
		expect(firstTabRoute(TEAM, state, [page(7, 'Handbook')]))
			.toEqual({ name: 'team-app', params: { teamId: TEAM, providerId: 'text', entryId: '7' } })
	})

	it('skips external entries at the front of the order', () => {
		const state = resources({ tabOrder: ['collective', 'board-3', 'home', 'team-folder'] })
		expect(firstTabRoute(TEAM, state))
			.toEqual({ name: 'team-home', params: { teamId: TEAM } })
	})

	it('skips pages the order knows but no longer exist', () => {
		const state = resources({ tabOrder: ['page-9', 'home', 'team-folder'] })
		expect(firstTabRoute(TEAM, state))
			.toEqual({ name: 'team-home', params: { teamId: TEAM } })
	})

	it('places entries missing from the order behind the known ones', () => {
		const state = resources({
			tabOrder: ['home'],
		})
		expect(firstTabRoute(TEAM, state))
			.toEqual({ name: 'team-home', params: { teamId: TEAM } })
	})
})

describe('firstTabSettled', () => {
	it('waits for the order and registered providers', () => {
		expect(firstTabSettled(resources({ orderChecked: false }))).toBe(false)
		expect(firstTabSettled(resources(), false)).toBe(false)
		expect(firstTabSettled(resources())).toBe(true)
	})

	it('settles on errors instead of waiting forever', () => {
		expect(firstTabSettled(resources({ orderChecked: false, orderError: true }))).toBe(true)
		expect(firstTabSettled(resources({ folderError: true }))).toBe(true)
	})
})
