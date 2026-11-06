/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('@nextcloud/axios', () => ({
	default: {
		post: vi.fn(),
		get: vi.fn(),
		put: vi.fn(),
		delete: vi.fn(),
	},
}))

vi.mock('@nextcloud/router', () => ({
	generateOcsUrl: (path: string, params: Record<string, string> = {}) => `/ocs/${path.replace(/\{([^}]+)\}/g, (_, key: string) => encodeURIComponent(params[key]))}`,
}))

vi.mock('../logger.ts', () => ({
	logger: { error: vi.fn(), warn: vi.fn(), debug: vi.fn() },
}))

vi.mock('./team-page/models/constants.ts', () => ({
	SHARES_TYPES_MEMBER_MAP: {},
}))

vi.mock('./team-page/services/collaborationAutocompletion.js', () => ({
	getRecommendations: vi.fn(),
	getSuggestions: vi.fn(),
}))

const { createTeam, fetchTeams, joinTeam, setTeamFavorite, reorderFavoriteTeams, fetchResourceFavorites, saveResourceFavorites } = await import('./api.ts')

describe('fetchTeams', () => {
	it('splits active members from discoverable teams', async () => {
		const circles = [
			{ id: 'owner-team', name: 'owner-team', displayName: 'Zebra owner', population: 3, initiator: { level: 9, status: 'Member' } },
			{ id: 'open-team', name: 'open-team', displayName: 'Bravo open', population: 10, initiator: null, config: 24 },
			{ id: 'closed-team', name: 'closed-team', displayName: 'Charlie closed', population: 7, initiator: null, config: 8 },
			{ id: 'pending-team', name: 'pending-team', displayName: 'Alpha pending', population: 5, initiator: { level: 0, status: 'Requesting' }, config: 88 },
			{ id: 'invited-team', name: 'invited-team', displayName: 'Echo invited', population: 2, initiator: { level: 0, status: 'Invited' } },
		]
		vi.mocked(axios.get)
			.mockResolvedValueOnce({ data: { ocs: { data: circles } } })
			.mockResolvedValueOnce({ data: { ocs: { data: [] } } })
			.mockResolvedValueOnce({ data: { ocs: { data: { circleIds: [] } } } })

		const overview = await fetchTeams()

		expect(overview.teams.map((team) => team.id)).toEqual(['owner-team', 'invited-team'])
		expect(overview.teams[0].myRole).toBe('owner')
		expect(overview.discoverableTeams.map((team) => team.id)).toEqual(['pending-team', 'open-team', 'closed-team'])
		expect(overview.discoverableTeams[0]).toMatchObject({ pending: true, canJoin: false, memberCount: 5 })
		expect(overview.discoverableTeams[1]).toMatchObject({ pending: false, canJoin: true, memberCount: 10 })
		expect(overview.discoverableTeams[2]).toMatchObject({ pending: false, canJoin: false, memberCount: 7 })
	})

	it('returns an empty list when the circles response carries no data', async () => {
		vi.mocked(axios.get)
			.mockResolvedValueOnce({ data: { ocs: {} } })
			.mockResolvedValueOnce({ data: { ocs: { data: [] } } })
			.mockResolvedValueOnce({ data: { ocs: { data: { circleIds: [] } } } })

		await expect(fetchTeams()).resolves.toEqual({ teams: [], discoverableTeams: [] })
	})

	it('uses fallback metadata for discoverable and member teams', async () => {
		const circles = [
			{ id: 'discoverable-defaults', name: 'Fallback discoverable', displayName: '', initiator: null },
			{ id: 'member-defaults', name: 'Fallback member', displayName: '', initiator: { level: 1, status: 'Member' } },
			{ id: 'member-preview-count', name: 'Preview member', displayName: 'Preview member', initiator: { level: 1, status: 'Member' } },
		]
		vi.mocked(axios.get)
			.mockResolvedValueOnce({ data: { ocs: { data: circles } } })
			.mockResolvedValueOnce({
				data: {
					ocs: {
						data: [{
							singleId: 'member-preview-count',
							members: [{ singleId: 'alice', userId: 'alice', displayName: 'Alice', type: 1 }],
							resources: [],
						}],
					},
				},
			})
			.mockResolvedValueOnce({ data: { ocs: { data: { circleIds: [] } } } })

		const overview = await fetchTeams()

		expect(overview.discoverableTeams).toMatchObject([{
			id: 'discoverable-defaults',
			displayName: 'Fallback discoverable',
			description: '',
			memberCount: 0,
			canJoin: false,
			pending: false,
		}])
		expect(overview.teams).toMatchObject([
			{ id: 'member-defaults', displayName: 'Fallback member', description: '', memberCount: 0 },
			{
				id: 'member-preview-count',
				displayName: 'Preview member',
				memberCount: 1,
				members: [{ id: 'alice', userId: 'alice', displayName: 'Alice', isUser: true, role: 'member' }],
			},
		])
	})
})

describe('joinTeam', () => {
	it('returns requested when the join requires approval', async () => {
		vi.mocked(axios.put).mockResolvedValueOnce({ data: { ocs: { data: { status: 'Requesting' } } } })

		await expect(joinTeam('team1')).resolves.toBe('requested')
		expect(axios.put).toHaveBeenCalledWith(expect.stringMatching(/\/join$/), {})
	})

	it('returns joined for an immediate membership', async () => {
		vi.mocked(axios.put).mockResolvedValueOnce({ data: { ocs: { data: { status: 'Member' } } } })

		await expect(joinTeam('team1')).resolves.toBe('joined')
		expect(axios.put).toHaveBeenCalledWith(expect.stringMatching(/\/join$/), {})
	})

	it('propagates a rejected join request', async () => {
		vi.mocked(axios.put).mockRejectedValueOnce(new Error('join failed'))

		await expect(joinTeam('team1')).rejects.toThrow('join failed')
	})
})

describe('createTeam', () => {
	beforeEach(() => {
		vi.mocked(axios.post).mockResolvedValue({
			data: { ocs: { data: { id: 'team1' } } },
		})
	})

	it('requests a team folder by default', async () => {
		await createTeam('Design')

		expect(axios.post).toHaveBeenCalledWith(
			'/ocs/apps/circles/circles',
			{ name: 'Design', createTeamFolder: true },
		)
	})

	it('can skip team folder creation', async () => {
		await createTeam('Design', false)

		expect(axios.post).toHaveBeenCalledWith(
			'/ocs/apps/circles/circles',
			{ name: 'Design', createTeamFolder: false },
		)
	})
})

describe('favorite teams API', () => {
	beforeEach(() => vi.resetAllMocks())

	it('returns the canonical favorite list after a toggle', async () => {
		vi.mocked(axios.put).mockResolvedValue({ data: { ocs: { data: { circleIds: ['A', 'B'] } } } })
		expect(await setTeamFavorite('B', true)).toEqual(['A', 'B'])
		expect(axios.put).toHaveBeenCalledWith(expect.stringContaining('/favorite'), { isFavorite: true })
	})

	it('includes the expected order and reads the canonical response', async () => {
		vi.mocked(axios.put).mockResolvedValue({ data: { ocs: { data: { circleIds: ['B', 'A'] } } } })
		expect(await reorderFavoriteTeams(['B', 'A'], ['A', 'B'])).toEqual(['B', 'A'])
		expect(axios.put).toHaveBeenCalledWith('/ocs/apps/circles/teams/favorites/order', {
			circleIds: ['B', 'A'],
			expectedCircleIds: ['A', 'B'],
		})
	})

	it('still loads teams when favorites cannot be read', async () => {
		vi.mocked(axios.get).mockResolvedValueOnce({ data: { ocs: { data: [{ id: 'A', name: 'A', displayName: 'A', initiator: { level: 1 } }] } } })
			.mockResolvedValueOnce({ data: { ocs: { data: [] } } })
			.mockRejectedValueOnce(new Error('favorites unavailable'))
		const overview = await fetchTeams()
		expect(overview.teams.map((team) => [team.id, team.isFavorite])).toEqual([['A', false]])
	})
})

describe('resource favorites API', () => {
	beforeEach(() => vi.resetAllMocks())

	it('fetches the ordered favorites for the requested team', async () => {
		vi.mocked(axios.get).mockResolvedValueOnce({ data: { ocs: { data: { favorites: ['board-2', 'page-1'] } } } })

		await expect(fetchResourceFavorites('team/1')).resolves.toEqual(['board-2', 'page-1'])
		expect(axios.get).toHaveBeenCalledWith('/ocs/apps/circles/teams/team%2F1/resource-favorites')
	})

	it('returns no favorites when none have been saved', async () => {
		vi.mocked(axios.get).mockResolvedValueOnce({ data: { ocs: { data: {} } } })

		await expect(fetchResourceFavorites('team-1')).resolves.toEqual([])
	})

	it('propagates a failed fetch', async () => {
		vi.mocked(axios.get).mockRejectedValueOnce(new Error('offline'))

		await expect(fetchResourceFavorites('team-1')).rejects.toThrow('offline')
	})

	it.each([['board-2', 'page-1'], []])('saves the favorites %j for the requested team', async (...favorites) => {
		vi.mocked(axios.put).mockResolvedValueOnce({})

		await saveResourceFavorites('team/1', favorites)

		expect(axios.put).toHaveBeenCalledWith('/ocs/apps/circles/teams/team%2F1/resource-favorites', { favorites })
	})

	it('propagates a failed save', async () => {
		vi.mocked(axios.put).mockRejectedValueOnce(new Error('offline'))

		await expect(saveResourceFavorites('team-1', ['page-1'])).rejects.toThrow('offline')
	})
})
