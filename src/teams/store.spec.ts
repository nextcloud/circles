/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TeamsOverview } from './api.ts'
import type { DiscoverableTeam, Team } from './types.ts'

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useTeamsStore } from './store.ts'

const fetchTeams = vi.hoisted(() => vi.fn<() => Promise<TeamsOverview>>(async () => ({
	teams: [],
	discoverableTeams: [],
})))
const joinTeam = vi.hoisted(() => vi.fn<(id: string) => Promise<'joined' | 'requested'>>(async () => 'joined'))

vi.mock('./api.ts', () => ({
	fetchTeams,
	joinTeam,
}))

vi.mock('../logger.ts', () => ({
	logger: { error: vi.fn(), warn: vi.fn() },
}))

const memberTeam: Team = {
	id: 'member-team',
	displayName: 'Product',
	description: '',
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
