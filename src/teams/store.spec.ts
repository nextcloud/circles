import type { Team } from './types.ts'

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import * as api from './api.ts'
import { useTeamsStore } from './store.ts'

vi.mock('./api.ts', () => ({ setTeamFavorite: vi.fn(), reorderFavoriteTeams: vi.fn(), fetchTeams: vi.fn() }))
vi.mock('../logger.ts', () => ({ logger: { error: vi.fn() } }))

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
		vi.mocked(api.fetchTeams).mockResolvedValue([team('A', 0), team('B', 1), team('C', 2)])
		await expect(store.reorderFavoriteTeams(['B', 'A'])).rejects.toThrow('conflict')
		expect(store.favoriteTeams.map((item) => item.id)).toEqual(['A', 'B', 'C'])
		expect(store.favoritesUpdating).toBe(false)
	})
})
