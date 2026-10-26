/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TeamResources } from './resourcesStore.ts'

import { describe, expect, it } from 'vitest'
import { firstTabRoute, firstTabSettled, orderEntries } from './firstTab.ts'

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

function page(fileId: number, title: string) {
	return {
		id: `text-${fileId}`,
		entryId: String(fileId),
		providerId: 'text',
		label: title,
		href: `/f/${fileId}`,
		referenceUrl: `https://cloud.example/f/${fileId}`,
	}
}

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
			tabOrder: ['text-7', 'team-folder', 'home'],
		})
		expect(firstTabRoute(TEAM, state, [page(7, 'Handbook')]))
			.toEqual({ name: 'team-app', params: { teamId: TEAM, providerId: 'text', entryId: '7' } })
	})

	it('skips external entries at the front of the order', () => {
		const state = resources({ tabOrder: ['collectives-1', 'deck-3', 'home', 'team-folder'] })
		expect(firstTabRoute(TEAM, state, [{ id: 'collectives-1', entryId: '1', providerId: 'collectives', label: 'Collective', href: '/apps/collectives/1' }]))
			.toEqual({ name: 'team-home', params: { teamId: TEAM } })
	})

	it('skips pages the order knows but no longer exist', () => {
		const state = resources({ tabOrder: ['text-9', 'home', 'team-folder'] })
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

describe('orderEntries', () => {
	it('orders provider-id IDs and keeps unknown entries in their natural order', () => {
		const entries = [{ id: 'text-7' }, { id: 'home' }, { id: 'deck-3' }, { id: 'collectives-1' }]
		expect(orderEntries(entries, ['deck-3', 'missing', 'text-7']))
			.toEqual([entries[2], entries[0], entries[1], entries[3]])
		expect(entries.map((entry) => entry.id)).toEqual(['text-7', 'home', 'deck-3', 'collectives-1'])
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
