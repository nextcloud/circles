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
const fetchResourceFavorites = vi.hoisted(() => vi.fn(async () => ['page-1']))
const saveResourceFavorites = vi.hoisted(() => vi.fn<(teamId: string, favorites: string[]) => Promise<void>>(async () => {}))

vi.mock('./api.ts', () => ({
	getTeamFolder: vi.fn(async () => null),
	upgradeTeamFolder: vi.fn(),
	fetchTeamPages: vi.fn(async () => []),
	fetchTeamResources,
	fetchTeamDeckBoards,
	fetchTabOrder: vi.fn(async () => []),
	saveTabOrder: vi.fn(),
	fetchResourceFavorites,
	saveResourceFavorites,
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

describe('teamResources store: favorites', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		fetchResourceFavorites.mockReset().mockResolvedValue(['page-1'])
		setActivePinia(createPinia())
	})

	it('loads the favorites with the team', async () => {
		const store = useTeamResourcesStore()
		await store.loadTeam(TEAM)

		expect(store.forTeam(TEAM).favorites).toEqual(['page-1'])
		expect(store.forTeam(TEAM).favoritesChecked).toBe(true)
	})

	it('caches favorites until asked to refresh', async () => {
		const store = useTeamResourcesStore()
		await store.ensureFavorites(TEAM)
		await store.ensureFavorites(TEAM)
		expect(fetchResourceFavorites).toHaveBeenCalledTimes(1)
		expect(fetchResourceFavorites).toHaveBeenCalledWith(TEAM)

		fetchResourceFavorites.mockResolvedValueOnce(['board-2'])
		await store.ensureFavorites(TEAM, true)

		expect(fetchResourceFavorites).toHaveBeenCalledTimes(2)
		expect(store.forTeam(TEAM).favorites).toEqual(['board-2'])
	})

	it('leaves a failed initial fetch unchecked so it can be retried', async () => {
		fetchResourceFavorites.mockRejectedValueOnce(new Error('offline'))
		const store = useTeamResourcesStore()

		await expect(store.ensureFavorites(TEAM)).resolves.toBeUndefined()
		expect(store.forTeam(TEAM).favorites).toEqual([])
		expect(store.forTeam(TEAM).favoritesChecked).toBe(false)

		await store.ensureFavorites(TEAM)

		expect(fetchResourceFavorites).toHaveBeenCalledTimes(2)
		expect(store.forTeam(TEAM).favorites).toEqual(['page-1'])
		expect(store.forTeam(TEAM).favoritesChecked).toBe(true)
	})

	it('preserves cached favorites when refreshing fails', async () => {
		const store = useTeamResourcesStore()
		await store.ensureFavorites(TEAM)
		fetchResourceFavorites.mockRejectedValueOnce(new Error('offline'))

		await expect(store.ensureFavorites(TEAM, true)).resolves.toBeUndefined()

		expect(store.forTeam(TEAM).favorites).toEqual(['page-1'])
		expect(store.forTeam(TEAM).favoritesChecked).toBe(true)
	})

	it('updates favorites before the save completes and keeps them on success', async () => {
		const store = useTeamResourcesStore()
		await store.ensureFavorites(TEAM)

		const saving = store.saveFavorites(TEAM, ['board-2', 'page-1'])
		expect(store.forTeam(TEAM).favorites).toEqual(['board-2', 'page-1'])
		await saving

		expect(saveResourceFavorites).toHaveBeenCalledWith(TEAM, ['board-2', 'page-1'])
		expect(store.forTeam(TEAM).favorites).toEqual(['board-2', 'page-1'])
	})

	it('saves optimistically and rolls back on failure', async () => {
		const store = useTeamResourcesStore()
		await store.ensureFavorites(TEAM)
		saveResourceFavorites.mockRejectedValueOnce(new Error('offline'))

		await expect(store.saveFavorites(TEAM, ['board-2', 'page-1'])).rejects.toThrow('offline')
		expect(saveResourceFavorites).toHaveBeenCalledWith(TEAM, ['board-2', 'page-1'])
		expect(store.forTeam(TEAM).favorites).toEqual(['page-1'])
	})
})
