/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { fetchTeamPages, fetchTeamResources, getTeamFolder, saveNavigation } from './api.ts'
import { useTeamResourcesStore } from './resourcesStore.ts'

vi.mock('./api.ts', () => ({
	getTeamFolder: vi.fn(async () => null),
	upgradeTeamFolder: vi.fn(),
	fetchTeamResources: vi.fn(async () => []),
	fetchTeamPages: vi.fn(async () => []),
	saveNavigation: vi.fn(),
}))

describe('generic team resources', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		setActivePinia(createPinia())
	})

	it('loads generic resources and folder without probing apps', async () => {
		const store = useTeamResourcesStore()
		await store.loadTeam('team')
		expect(fetchTeamResources).toHaveBeenCalledWith('team')
		expect(getTeamFolder).toHaveBeenCalledWith('team')
		expect(store.forTeam('team')).toMatchObject({ folderChecked: true, resourcesChecked: true, orderChecked: false })
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
		vi.mocked(saveNavigation).mockRejectedValueOnce(new Error('forbidden'))
		const store = useTeamResourcesStore()
		store.slot('team').navigation = [{ id: 'home' }]
		await expect(store.saveOrder('team', [{ id: 'team-folder' }])).rejects.toThrow()
		expect(store.forTeam('team').navigation).toEqual([{ id: 'home' }])
	})

	it('optimistically saves the displayed navigation as ordered IDs', async () => {
		let complete!: () => void
		vi.mocked(saveNavigation).mockImplementationOnce(() => new Promise((resolve) => {
			complete = resolve
		}))
		const store = useTeamResourcesStore()
		const entries = [{ id: 'home' as const }, { id: 'deck-7', providerId: 'deck', entryId: '7' }, { id: 'team-folder' as const }]
		const saving = store.saveOrder('team', entries)
		expect(store.forTeam('team').navigation).toEqual(entries)
		expect(saveNavigation).toHaveBeenCalledExactlyOnceWith('team', ['home', 'deck-7', 'team-folder'])
		complete()
		await saving
	})

	it('merges pages with provider entries, keeping saved positions and deduplicating earlier files aliases', () => {
		const store = useTeamResourcesStore()
		store.slot('team').pages = [
			{ fileId: 42, title: 'Handbook', filePath: '/Team/.system/teams/pages/Handbook.md' },
			{ fileId: 43, title: 'Guide', filePath: '/Team/.system/teams/pages/Guide.md' },
		]
		store.applyNavigation('team', [
			{ id: 'files-42', providerId: 'files', entryId: '42' },
			{ id: 'team-folder' },
			{ id: 'page-42' },
			{ id: 'deck-7', providerId: 'deck', entryId: '7' },
			{ id: 'home' },
		])
		expect(store.forTeam('team').navigation.map(({ id }) => id)).toEqual(['page-42', 'team-folder', 'deck-7', 'page-43', 'home'])
	})

	it('loads and caches pages from the team mount point, explicitly recording DAV errors', async () => {
		const store = useTeamResourcesStore()
		vi.mocked(getTeamFolder).mockResolvedValueOnce({ id: 1, mountPoint: 'Team', quota: null })
		vi.mocked(fetchTeamPages).mockResolvedValueOnce([{ fileId: 42, title: 'Guide', filePath: '/Team/.system/teams/pages/Guide.md' }])
		await store.loadTeam('team')
		await store.ensurePages('team')
		expect(fetchTeamPages).toHaveBeenCalledExactlyOnceWith('Team')
		expect(store.forTeam('team').navigation.map(({ id }) => id)).toEqual(['team-folder', 'page-42', 'home'])
		vi.mocked(fetchTeamPages).mockRejectedValueOnce(new Error('offline'))
		await store.ensurePages('team', true)
		expect(store.forTeam('team').pagesError).toBe(true)
		expect(store.forTeam('team').pages).toHaveLength(1)
		expect(store.forTeam('other').pages).toEqual([])
	})
})
