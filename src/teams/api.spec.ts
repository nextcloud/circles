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

const { createTeam, fetchTeams, joinTeam } = await import('./api.ts')

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
