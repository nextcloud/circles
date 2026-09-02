/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { SharedResource } from './types.ts'

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useTeamResourcesStore } from './resourcesStore.ts'

const fetchTeamResources = vi.hoisted(() => vi.fn<(teamId: string) => Promise<SharedResource[]>>(async () => []))
const fetchTeamDeckBoards = vi.hoisted(() => vi.fn(async () => [] as { id: number, title: string, url: string }[]))

vi.mock('./api.ts', () => ({
	getTeamFolder: vi.fn(async () => null),
	upgradeTeamFolder: vi.fn(),
	fetchTeamPages: vi.fn(async () => []),
	fetchTeamResources,
	fetchTeamDeckBoards,
	fetchTabOrder: vi.fn(async () => []),
	saveTabOrder: vi.fn(),
}))

const TEAM = 'team-1'

const deckResource: SharedResource = {
	id: '5',
	label: 'Roadmap',
	url: 'https://cloud.example/apps/deck/board/5',
	provider: { id: 'deck', name: 'Deck' },
}
const talkResource: SharedResource = {
	id: 'token',
	label: 'General',
	url: 'https://cloud.example/call/token',
	provider: { id: 'talk', name: 'Talk' },
}

describe('teamResources store: team boards', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		setActivePinia(createPinia())
	})

	it('passes only deck resources as candidates and stores the boards', async () => {
		fetchTeamResources.mockResolvedValue([talkResource, deckResource])
		const board = { id: 5, title: 'Roadmap', url: deckResource.url }
		fetchTeamDeckBoards.mockResolvedValue([board])

		const store = useTeamResourcesStore()
		await store.ensureBoards(TEAM)

		expect(fetchTeamDeckBoards).toHaveBeenCalledWith(TEAM, [deckResource])
		expect(store.forTeam(TEAM).boards).toEqual([board])
		expect(store.forTeam(TEAM).boardsChecked).toBe(true)
	})

	it('caches until asked to refresh', async () => {
		const store = useTeamResourcesStore()
		await store.ensureBoards(TEAM)
		await store.ensureBoards(TEAM)
		expect(fetchTeamDeckBoards).toHaveBeenCalledTimes(1)

		await store.ensureBoards(TEAM, true)
		expect(fetchTeamDeckBoards).toHaveBeenCalledTimes(2)
	})

	it('marks the slot checked with no boards when the lookup fails', async () => {
		fetchTeamResources.mockResolvedValue([deckResource])
		fetchTeamDeckBoards.mockRejectedValue(new Error('deck went away'))

		const store = useTeamResourcesStore()
		await store.ensureBoards(TEAM)

		expect(store.forTeam(TEAM).boards).toEqual([])
		expect(store.forTeam(TEAM).boardsChecked).toBe(true)
	})

	it('loads the boards from the resources fetched by loadTeam', async () => {
		fetchTeamResources.mockResolvedValue([deckResource])

		const store = useTeamResourcesStore()
		await store.loadTeam(TEAM)

		expect(fetchTeamResources).toHaveBeenCalledTimes(1)
		expect(fetchTeamDeckBoards).toHaveBeenCalledWith(TEAM, [deckResource])
	})
})
