/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TeamsOverview } from './api.ts'
import type { DiscoverableTeam, Team } from './types.ts'

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import * as api from './api.ts'
import { useTeamsStore } from './store.ts'

const fetchTeams = vi.hoisted(() => vi.fn<() => Promise<TeamsOverview>>())
const joinTeam = vi.hoisted(() => vi.fn<(id: string) => Promise<'joined' | 'requested'>>())
const setTeamFavorite = vi.hoisted(() => vi.fn<(id: string, isFavorite: boolean) => Promise<string[]>>())
const reorderFavoriteTeams = vi.hoisted(() => vi.fn<(ids: string[], expectedIds: string[]) => Promise<string[]>>())

vi.mock('./api.ts', () => ({ fetchTeams, joinTeam, setTeamFavorite, reorderFavoriteTeams }))
vi.mock('../logger.ts', () => ({ logger: { error: vi.fn(), warn: vi.fn() } }))

function team(id: string, position: number | null): Team {
	return { id, displayName: id, description: '', isFavorite: position !== null, favoritePosition: position, memberCount: 0, myRole: 'member', members: [], resources: [] }
}

describe('favorite teams', () => {
	beforeEach(() => {
		vi.resetAllMocks()
		setActivePinia(createPinia())
	})

	it('normalizes positions after removal and appends new favorites', async () => {
		const store = useTeamsStore()
		store.teams = [team('D', null), team('A', 0), team('B', 1), team('C', 2)]
		vi.mocked(api.setTeamFavorite).mockResolvedValueOnce(['B', 'C']).mockResolvedValueOnce(['B', 'C', 'D'])
		await store.toggleFavorite('A')
		await store.toggleFavorite('D')
		expect(store.favoriteTeams.map((item) => item.id)).toEqual(['B', 'C', 'D'])
		expect(store.favoriteTeams.map((item) => item.favoritePosition)).toEqual([0, 1, 2])
	})

	it('sends the last canonical order for conflict detection', async () => {
		const store = useTeamsStore()
		store.teams = [team('A', 0), team('B', 1)]
		vi.mocked(api.reorderFavoriteTeams).mockResolvedValue(['B', 'A'])
		await store.reorderFavoriteTeams(['B', 'A'])
		expect(api.reorderFavoriteTeams).toHaveBeenCalledWith(['B', 'A'], ['A', 'B'])
		expect(store.favoriteTeams.map((item) => item.id)).toEqual(['B', 'A'])
	})

	it('blocks overlapping favorite mutations', async () => {
		const store = useTeamsStore()
		store.teams = [team('A', null)]
		let resolveRequest!: (ids: string[]) => void
		vi.mocked(api.setTeamFavorite).mockReturnValue(new Promise((resolve) => {
			resolveRequest = resolve
		}))
		const pending = store.toggleFavorite('A')
		await store.toggleFavorite('A')
		await store.reorderFavoriteTeams([])
		expect(api.setTeamFavorite).toHaveBeenCalledTimes(1)
		expect(api.reorderFavoriteTeams).not.toHaveBeenCalled()
		expect(store.favoritesUpdating).toBe(true)
		resolveRequest(['A'])
		await pending
		expect(store.favoritesUpdating).toBe(false)
	})

	it('reloads the server state on conflicts and releases the busy state', async () => {
		const store = useTeamsStore()
		store.teams = [team('A', 0), team('B', 1)]
		vi.mocked(api.reorderFavoriteTeams).mockRejectedValue(new Error('conflict'))
		vi.mocked(api.fetchTeams).mockResolvedValue({
			teams: [team('A', 0), team('B', 1), team('C', 2)],
			discoverableTeams: [],
		})
		await expect(store.reorderFavoriteTeams(['B', 'A'])).rejects.toThrow('conflict')
		expect(store.favoriteTeams.map((item) => item.id)).toEqual(['A', 'B', 'C'])
		expect(store.favoritesUpdating).toBe(false)
	})
})

const memberTeam: Team = {
	id: 'member-team',
	displayName: 'Product',
	description: '',
	isFavorite: false,
	favoritePosition: null,
	memberCount: 2,
	myRole: 'member',
	members: [],
	resources: [],
}

const openTeam: DiscoverableTeam = {
	id: 'open-team',
	displayName: 'Alpha team',
	description: '',
	memberCount: 4,
	canJoin: true,
	pending: false,
}

const closedTeam: DiscoverableTeam = {
	id: 'closed-team',
	displayName: 'Beta team',
	description: '',
	memberCount: 7,
	canJoin: false,
	pending: false,
}

const overview: TeamsOverview = {
	teams: [memberTeam],
	discoverableTeams: [openTeam, closedTeam],
}

describe('teams store: discoverable teams', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		setActivePinia(createPinia())
	})

	it('loads active and discoverable teams from one overview', async () => {
		fetchTeams.mockResolvedValue(overview)

		const store = useTeamsStore()
		await store.loadTeams()

		expect(fetchTeams).toHaveBeenCalledTimes(1)
		expect(store.teams).toEqual([memberTeam])
		expect(store.discoverableTeams).toEqual([openTeam, closedTeam])
	})

	it('keeps both lists when loading fails', async () => {
		fetchTeams.mockRejectedValueOnce(new Error('network error'))

		const store = useTeamsStore()
		store.teams = [memberTeam]
		store.discoverableTeams = [openTeam]
		await store.loadTeams()

		expect(store.teams).toEqual([memberTeam])
		expect(store.discoverableTeams).toEqual([openTeam])
		expect(store.loadError).toBe(true)
	})

	it('filters discoverable teams by a trimmed case-insensitive name', () => {
		const store = useTeamsStore()
		store.discoverableTeams = [openTeam, closedTeam]

		expect(store.searchDiscoverableTeams('  ALPHA ')).toEqual([openTeam])
		expect(store.searchDiscoverableTeams('')).toEqual([openTeam, closedTeam])
	})

	it('joins a team, reloads the lists, and returns the API result', async () => {
		const events: string[] = []
		joinTeam.mockImplementation(async (id) => {
			events.push(`join:${id}`)
			return 'requested'
		})
		fetchTeams.mockImplementation(async () => {
			events.push('fetchTeams')
			return overview
		})

		const store = useTeamsStore()

		await expect(store.joinTeam('open-team')).resolves.toBe('requested')

		expect(events).toEqual(['join:open-team', 'fetchTeams'])
		expect(store.discoverableTeams).toEqual([openTeam, closedTeam])
	})

	it('does not reload when joining fails', async () => {
		joinTeam.mockRejectedValueOnce(new Error('join failed'))

		const store = useTeamsStore()

		await expect(store.joinTeam('open-team')).rejects.toThrow('join failed')
		expect(fetchTeams).not.toHaveBeenCalled()
	})
})
