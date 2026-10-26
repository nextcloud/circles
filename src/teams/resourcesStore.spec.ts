/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useTeamResourcesStore } from './resourcesStore.ts'
import { fetchTeamResources, getTeamFolder, saveTabOrder } from './api.ts'

vi.mock('./api.ts', () => ({
	getTeamFolder: vi.fn(async () => null),
	upgradeTeamFolder: vi.fn(),
	fetchTeamResources: vi.fn(async () => []),
	fetchTabOrder: vi.fn(async () => []),
	saveTabOrder: vi.fn(),
}))

describe('generic team resources', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		setActivePinia(createPinia())
	})

	it('loads generic resources, folder and ordering without probing apps', async () => {
		const store = useTeamResourcesStore()
		await store.loadTeam('team')
		expect(fetchTeamResources).toHaveBeenCalledWith('team')
		expect(getTeamFolder).toHaveBeenCalledWith('team')
		expect(store.forTeam('team')).toMatchObject({ folderChecked: true, resourcesChecked: true, orderChecked: true })
	})

	it('caches until refreshed', async () => {
		const store = useTeamResourcesStore()
		await store.loadTeam('team')
		await store.loadTeam('team')
		expect(fetchTeamResources).toHaveBeenCalledTimes(1)
		await store.loadTeam('team', true)
		expect(fetchTeamResources).toHaveBeenCalledTimes(2)
	})

	it('records a resource failure rather than treating it as absence', async () => {
		vi.mocked(fetchTeamResources).mockRejectedValueOnce(new Error('offline'))
		const store = useTeamResourcesStore()
		await store.loadTeam('team')
		expect(store.forTeam('team').resourcesError).toBe(true)
		expect(store.forTeam('team').resourcesChecked).toBe(false)
	})

	it('rolls back a failed order write', async () => {
		vi.mocked(saveTabOrder).mockRejectedValueOnce(new Error('forbidden'))
		const store = useTeamResourcesStore()
		store.slot('team').tabOrder = ['home']
		await expect(store.saveOrder('team', ['team-folder'])).rejects.toThrow()
		expect(store.forTeam('team').tabOrder).toEqual(['home'])
	})
})
