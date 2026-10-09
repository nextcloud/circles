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
	generateOcsUrl: (path: string) => `/ocs/${path}`,
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

const { createTeam, fetchTeams, setTeamFavorite, reorderFavoriteTeams } = await import('./api.ts')

describe('fetchTeams', () => {
	it('drops circles the current user is not a member of', async () => {
		const circles = [
			{ id: 'team1', name: 'team1', displayName: 'Team One', population: 3, initiator: { level: 9 } },
			{ id: 'visible1', name: 'visible1', displayName: 'Visible circle', population: 10, initiator: null },
		]
		vi.mocked(axios.get)
			.mockResolvedValueOnce({ data: { ocs: { data: circles } } })
			.mockResolvedValueOnce({ data: { ocs: { data: [] } } })
			.mockResolvedValueOnce({ data: { ocs: { data: { circleIds: [] } } } })

		const teams = await fetchTeams()

		expect(teams).toHaveLength(1)
		expect(teams[0].id).toBe('team1')
		expect(teams[0].myRole).toBe('owner')
	})

	it('returns an empty list when the circles response carries no data', async () => {
		vi.mocked(axios.get)
			.mockResolvedValueOnce({ data: { ocs: {} } })
			.mockResolvedValueOnce({ data: { ocs: { data: [] } } })
			.mockResolvedValueOnce({ data: { ocs: { data: { circleIds: [] } } } })

		await expect(fetchTeams()).resolves.toEqual([])
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
		const teams = await fetchTeams()
		expect(teams.map((team) => [team.id, team.isFavorite])).toEqual([['A', false]])
	})
})
