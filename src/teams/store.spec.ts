/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useTeamsStore } from './store.ts'

type LoadState = (app: string, key: string, fallback?: unknown) => unknown

const loadState = vi.hoisted(() => vi.fn<LoadState>())
const createTeam = vi.hoisted(() => vi.fn(async () => 'team-1'))
const fetchTeams = vi.hoisted(() => vi.fn(async () => []))

vi.mock('@nextcloud/initial-state', () => ({ loadState }))
vi.mock('./api.ts', () => ({ createTeam, fetchTeams }))

/**
 * Create a fresh store whose `canCreateTeam` initial state is `allowed`.
 *
 * @param allowed - value served for the `canCreateTeam` initial state
 */
function setupStore(allowed: boolean) {
	loadState.mockImplementation((app, key, fallback) => {
		if (app === 'circles' && key === 'canCreateTeam') {
			return allowed
		}
		return fallback
	})
	setActivePinia(createPinia())
	return useTeamsStore()
}

describe('teams store team creation permission', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it('reads canCreateTeam from the initial state', () => {
		expect(setupStore(true).canCreateTeam).toBe(true)
		expect(setupStore(false).canCreateTeam).toBe(false)
	})

	it('defaults to not allowed when the initial state is missing', () => {
		loadState.mockImplementation((app, key, fallback) => fallback)
		setActivePinia(createPinia())

		expect(useTeamsStore().canCreateTeam).toBe(false)
	})

	it('opens the creation wizard when allowed', () => {
		const store = setupStore(true)

		store.openCreateTeamWizard()

		expect(store.createWizardOpen).toBe(true)
	})

	it('keeps the creation wizard closed when not allowed', () => {
		const store = setupStore(false)

		store.openCreateTeamWizard()

		expect(store.createWizardOpen).toBe(false)
	})

	it('creates a team when allowed', async () => {
		const store = setupStore(true)

		await store.createTeam('Marketing')

		expect(createTeam).toHaveBeenCalledWith('Marketing', true)
	})

	it('does not call the API to create a team when not allowed', async () => {
		const store = setupStore(false)

		await expect(store.createTeam('Marketing')).rejects.toThrow()
		expect(createTeam).not.toHaveBeenCalled()
	})
})
