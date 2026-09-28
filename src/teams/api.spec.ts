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

	it('does not silently replace favorites with an empty list on a failed read', async () => {
		vi.mocked(axios.get).mockResolvedValueOnce({ data: { ocs: { data: [] } } })
			.mockResolvedValueOnce({ data: { ocs: { data: [] } } })
			.mockRejectedValueOnce(new Error('favorites unavailable'))
		await expect(fetchTeams()).rejects.toThrow('favorites unavailable')
	})
})
