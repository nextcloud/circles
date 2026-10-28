/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TeamAppScope } from './store.ts'
import type { TeamAppProvider } from './types.ts'

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { finishResourcePicker, resourcePickerState } from './resourcePicker.ts'
import { useTeamAppsStore } from './store.ts'

const { providers, post, get, showError } = vi.hoisted(() => ({
	providers: [] as TeamAppProvider[],
	post: vi.fn(),
	get: vi.fn(),
	showError: vi.fn(),
}))
vi.mock('./registry.ts', () => ({
	getTeamAppProviders: () => providers,
	getTeamAppProvider: (id: string) => providers.find((provider) => provider.id === id),
	getTeamAppRegistrationErrors: () => [],
}))
vi.mock('@nextcloud/axios', () => ({ default: { put: post, post, get } }))
vi.mock('@nextcloud/dialogs', () => ({ showConfirmation: vi.fn(), showError }))

function scope(teamId = 'team'): TeamAppScope {
	return { teamId, teamName: teamId, isAdmin: true, folder: null, resources: [], refresh: vi.fn(), open: vi.fn() }
}

describe('generic Teams app host', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
		providers.splice(0)
		vi.clearAllMocks()
		post.mockReset().mockResolvedValue({ data: { ocs: { data: {} } } })
	})

	it('executes a fresh declarative prompt action, refreshes and opens the returned entry', async () => {
		const load = vi.fn(async () => ({ entries: [], actions: [{ id: 'create', label: 'New', prompt: { title: 'Name' } }] }))
		providers.push({ id: 'example', order: 1, load })
		const store = useTeamAppsStore()
		const input = scope()
		await store.loadTeam(input)
		vi.spyOn(store.context('team', 'example')!, 'prompt').mockResolvedValue('Sketch')
		post.mockResolvedValueOnce({ data: { ocs: { data: { open: '43' } } } })
		await store.run(store.actions('team')[0]!)
		expect(load).toHaveBeenCalledTimes(2)
		expect(post).toHaveBeenCalledWith(expect.stringContaining('/teams/team/apps/example/actions/create'), { parameters: { value: 'Sketch' } }, { signal: expect.any(AbortSignal) })
		expect(input.refresh).toHaveBeenCalledOnce()
		expect(input.open).toHaveBeenCalledWith('example', '43')
	})

	it('does not execute or refresh a cancelled prompt', async () => {
		providers.push({ id: 'example', order: 1, load: async () => ({ entries: [], actions: [{ id: 'create', label: 'New', prompt: { title: 'Name' } }] }) })
		const store = useTeamAppsStore()
		const input = scope()
		await store.loadTeam(input)
		vi.spyOn(store.context('team', 'example')!, 'prompt').mockResolvedValue(null)
		await store.run(store.actions('team')[0]!)
		expect(post).not.toHaveBeenCalled()
		expect(input.refresh).not.toHaveBeenCalled()
	})

	it('does not execute an unconfirmed declarative deletion', async () => {
		providers.push({ id: 'example', order: 1, load: async () => ({ entries: [{ id: '7', label: 'Board', href: '/board', actions: [{ id: 'delete-7', label: 'Delete', confirm: { title: 'Delete', message: 'Delete board?' } }] }] }) })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		vi.spyOn(store.context('team', 'example')!, 'confirm').mockResolvedValue(false)
		await store.run(store.entries('team')[0]!.actions![0]!)
		expect(post).not.toHaveBeenCalled()
	})

	it('persists an explicitly empty declarative resource selection', async () => {
		providers.push({ id: 'example', order: 1, load: async () => ({ entries: [], actions: [{ id: 'navigation', label: 'Navigation entries', resourcePicker: { title: 'Boards', resources: [{ id: '7', label: 'Board' }], selectedIds: ['7'] } }] }) })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		const action = store.run(store.actions('team')[0]!)
		await vi.waitFor(() => expect(resourcePickerState.open).toBe(true))
		finishResourcePicker([])
		await action
		expect(post).toHaveBeenCalledWith(expect.stringContaining('/actions/navigation'), { parameters: { ids: [] } }, { signal: expect.any(AbortSignal) })
	})

	it('rejects actions that disappeared during refresh', async () => {
		const load = vi.fn().mockResolvedValueOnce({ entries: [], actions: [{ id: 'create', label: 'New' }] }).mockResolvedValueOnce({ entries: [], actions: [] })
		providers.push({ id: 'example', order: 1, load })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		await store.run(store.actions('team')[0]!)
		expect(post).not.toHaveBeenCalled()
		expect(showError).toHaveBeenCalledOnce()
	})

	it('exposes a resource picker scoped to the current team without persisting on cancel', async () => {
		providers.push({ id: 'example', order: 1, load: async () => ({ entries: [] }) })
		const store = useTeamAppsStore()
		await store.loadTeam(scope('picker'))
		const context = store.context('picker', 'example')!
		const selection = context.chooseResources({ title: 'Boards', resources: [{ id: '7', label: 'Board' }], selectedIds: ['7'] })
		expect(resourcePickerState.open).toBe(true)
		expect(resourcePickerState.selectedIds).toEqual(['7'])
		store.unloadTeam('picker')
		await expect(selection).resolves.toBeNull()
		expect(resourcePickerState.open).toBe(false)
		expect(post).not.toHaveBeenCalled()
		expect(() => context.chooseResources({ title: 'Boards', resources: [] })).toThrow()
	})

	it('returns selected IDs without implicitly saving navigation entries', async () => {
		providers.push({ id: 'example', order: 1, load: async () => ({ entries: [] }) })
		const store = useTeamAppsStore()
		await store.loadTeam(scope('selection'))
		const selection = store.context('selection', 'example')!.chooseResources({ title: 'Boards', resources: [{ id: '7', label: 'Board' }] })
		finishResourcePicker(['7'])
		await expect(selection).resolves.toEqual(['7'])
		expect(post).not.toHaveBeenCalled()
		store.unloadTeam('selection')
	})

	it('loads app-owned entries and filters only represented resources', async () => {
		providers.push({
			id: 'example',
			order: 10,
			load: vi.fn(async () => ({
				entries: [{ id: '7', label: 'Board', href: '/apps/example/7' }],
				resourceKeys: [{ providerId: 'example', resourceId: '7' }],
			})),
		})
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(store.entries('team')[0]).toMatchObject({ id: 'example:7', providerId: 'example', entryId: '7' })
		expect(store.resourceKeys('team').has('["example","7"]')).toBe(true)
		expect(store.resourceKeys('team').has('["example","8"]')).toBe(false)
	})

	it('isolates a failed provider without hiding shared resources', async () => {
		providers.push({
			id: 'broken',
			order: 1,
			load: vi.fn(async () => {
				throw new Error('offline')
			}),
		})
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(store.settled('team')).toBe(true)
		expect(store.errors('team')).toEqual(['broken'])
		expect(store.entries('team')).toEqual([])
		expect(store.resourceKeys('team').size).toBe(0)
	})

	it('rejects duplicate entries and unsafe URLs', async () => {
		providers.push({ id: 'bad', order: 1, load: async () => ({ entries: [{ id: '1', label: 'Bad', href: 'javascript:alert(1)' }] }) })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(store.errors('team')).toEqual(['bad'])
	})

	it('does not probe providers when shared dependencies failed', async () => {
		const load = vi.fn(async () => ({ entries: [] }))
		providers.push({ id: 'example', order: 1, load })
		const store = useTeamAppsStore()
		await store.loadTeam({ ...scope(), sourceError: true })
		expect(load).not.toHaveBeenCalled()
		expect(store.errors('team')).toEqual(['example'])
	})

	it('aborts a team scope and ignores its late results on unload', async () => {
		let complete!: (result: { entries: [] }) => void
		providers.push({
			id: 'example',
			order: 1,
			load: () => new Promise((resolve) => {
				complete = resolve
			}),
		})
		const store = useTeamAppsStore()
		const loading = store.loadTeam(scope('old'))
		const signal = store.context('old', 'example')!.signal
		store.unloadTeam('old')
		complete({ entries: [] })
		await loading
		expect(signal.aborted).toBe(true)
		expect(store.slots.old).toBeUndefined()
	})

	it('keeps actions usable after their refresh, including open', async () => {
		providers.push({ id: 'example', order: 1, load: async () => ({ entries: [] }) })
		const store = useTeamAppsStore()
		const input = scope()
		input.refresh = () => store.loadTeam(input)
		await store.loadTeam(input)
		const context = store.context('team', 'example')!
		await context.refresh()
		context.open('new')
		expect(input.open).toHaveBeenCalledWith('example', 'new')
	})

	it('keeps the previous navigation and context active during refresh', async () => {
		let complete!: (result: { entries: Array<{ id: string, label: string, href: string }> }) => void
		const load = vi.fn()
			.mockResolvedValueOnce({ entries: [{ id: 'old', label: 'Old', href: '/old' }] })
			.mockImplementationOnce(() => new Promise((resolve) => {
				complete = resolve
			}))
		providers.push({ id: 'example', order: 1, load })
		const store = useTeamAppsStore()
		const input = scope()
		await store.loadTeam(input)
		const oldContext = store.context('team', 'example')!
		const oldSignal = oldContext.signal
		const refreshing = store.loadTeam(input)

		expect(store.entries('team').map((entry) => entry.entryId)).toEqual(['old'])
		expect(store.settled('team')).toBe(false)
		expect(store.providerReady('team', 'example')).toBe(true)
		expect(store.context('team', 'example')).toBe(oldContext)
		expect(store.context('team', 'example')!.signal).toBe(oldSignal)

		complete({ entries: [{ id: 'new', label: 'New', href: '/new' }] })
		await refreshing
		expect(store.entries('team').map((entry) => entry.entryId)).toEqual(['new'])
	})

	it('reads and writes navigation entry IDs through the renamed endpoint', async () => {
		providers.push({ id: 'example', order: 1, load: async () => ({ entries: [] }) })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		get.mockResolvedValueOnce({ data: { ocs: { data: { navigationEntryIds: ['7'] } } } })
		const context = store.context('team', 'example')!
		await expect(context.getNavigationEntryIds()).resolves.toEqual(['7'])
		await context.setNavigationEntryIds(['7'])
		expect(get).toHaveBeenCalledWith(expect.stringContaining('/teams/team/apps/example/navigation/selection'), { signal: context.signal })
		expect(post).toHaveBeenCalledWith(expect.stringContaining('/teams/team/apps/example/navigation/selection'), { navigationEntryIds: ['7'] }, { signal: context.signal })
	})

	it('binds navigation entries to the current provider and propagates failed writes', async () => {
		providers.push({ id: 'example', order: 1, load: async () => ({ entries: [] }) })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		post.mockRejectedValueOnce(new Error('forbidden'))
		await expect(store.context('team', 'example')!.setNavigationEntryIds(['7'])).rejects.toThrow('forbidden')
		expect(post.mock.calls[0]![0]).toContain('/teams/team/apps/example/navigation/selection')
	})

	it('reports action failures explicitly', async () => {
		const store = useTeamAppsStore()
		await store.run({
			id: 'bad',
			label: 'Bad',
			run: async () => {
				throw new Error('failed')
			},
		})
		expect(showError).toHaveBeenCalled()
		expect(store.busy).toBe(false)
	})
})
