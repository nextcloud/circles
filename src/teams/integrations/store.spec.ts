/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TeamAppNavigation } from '#server-types/teams'
import type { getTeamAppNavigations } from '../api.ts'
import type { TeamAppScope } from './store.ts'
import type { TeamAppNavigationResponse, TeamAppProvider } from './types.ts'

import { mdiPlus } from '@mdi/js'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { navigationId, useTeamAppsStore } from './store.ts'

const prompt = vi.fn<(title: string, signal: AbortSignal) => Promise<string | null>>()

const { providers, post, getNavigations, showError } = vi.hoisted(() => ({
	providers: [] as TeamAppProvider[],
	post: vi.fn(),
	getNavigations: vi.fn<typeof getTeamAppNavigations>(),
	showError: vi.fn(),
}))
vi.mock('./registry.ts', () => ({
	getTeamAppProviders: () => providers,
	getTeamAppRegistrationErrors: () => [],
}))
vi.mock('../api.ts', () => ({ getTeamAppNavigations: getNavigations }))
vi.mock('@nextcloud/axios', () => ({ default: { post } }))
vi.mock('@nextcloud/dialogs', () => ({ showConfirmation: vi.fn(), showError }))

function scope(teamId = 'team'): TeamAppScope {
	return { teamId, teamName: teamId, isAdmin: true, folder: null, resources: [], refresh: vi.fn(), open: vi.fn() }
}

function navigationResponse(value: TeamAppNavigation, selectedEntryIds = value.entries.map((entry) => entry.id)): TeamAppNavigationResponse {
	return { ...value, selectedEntryIds }
}

function navigation(value: TeamAppNavigation, selectedEntryIds?: string[]): void {
	providers.push({ id: 'example', name: 'Example', order: 1 })
	getNavigations.mockResolvedValue({ providers: { example: navigationResponse(value, selectedEntryIds) }, errors: [] })
}

describe('generic Teams app host', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
		providers.splice(0)
		vi.clearAllMocks()
		prompt.mockReset().mockResolvedValue(null)
		post.mockReset().mockResolvedValue({ data: { ocs: { data: {} } } })
		getNavigations.mockReset().mockResolvedValue({ providers: { example: navigationResponse({ entries: [] }) }, errors: [] })
	})

	it('uses provider-id navigation IDs without parsing app-local IDs', () => {
		expect(navigationId('text', '42')).toBe('text-42')
		expect(navigationId('whiteboard', 'draft-7')).toBe('whiteboard-draft-7')
	})

	it('shows selected entries, adds existing resources, and removes through the store method', async () => {
		navigation({
			entries: [{ id: '42', label: 'Pinned', href: '/pinned' }, { id: '43', label: 'Unpinned', href: '/unpinned' }],
		}, ['42'])
		const store = useTeamAppsStore()
		const input = scope()
		await store.loadTeam(input)

		expect(store.entries('team').map((entry) => entry.entryId)).toEqual(['42'])
		expect(store.entries('team')[0]).not.toHaveProperty('canRemove')
		expect(store.entries('team')[0]).not.toHaveProperty('actions')
		expect(store.entries('team')[0]).not.toHaveProperty('removeAction')
		const existingResources = store.existingResources('team')
		expect(existingResources.map(({ name }) => name)).toEqual(['Example'])
		const add = existingResources[0]!.actions[0]!
		expect(add.label).toBe('Unpinned')
		await store.run(add)

		expect(post).toHaveBeenCalledWith(
			expect.stringContaining('/apps/example/entries/navigation'),
			{ entryId: '43', inNavigation: true },
			{ signal: expect.any(AbortSignal) },
		)
		post.mockClear()
		await store.removeFromNavigation('team', 'example', '42')
		expect(post).toHaveBeenCalledWith(
			expect.stringContaining('/apps/example/entries/navigation'),
			{ entryId: '42', inNavigation: false },
			{ signal: expect.any(AbortSignal) },
		)
		expect(input.refresh).toHaveBeenCalledTimes(2)
	})

	it.each(['member', 'loading', 'error'])('rejects removal for a %s provider context without per-entry flags', async (condition) => {
		navigation({ entries: [{ id: '42', label: 'Pinned', href: '/pinned' }] })
		const store = useTeamAppsStore()
		await store.loadTeam({ ...scope(), isAdmin: condition !== 'member' })
		const provider = store.providerStates.team!.example!
		provider.loading = condition === 'loading'
		provider.error = condition === 'error'
		expect(store.entries('team')[0]).not.toHaveProperty('canRemove')
		await expect(store.removeFromNavigation('team', 'example', '42')).rejects.toThrow('cannot be removed')
		expect(post).not.toHaveBeenCalled()
	})

	it.each(['missing team', 'missing provider', 'missing entry', 'unselected entry', 'unloaded team'])('rejects removal for %s', async (condition) => {
		navigation({ entries: [{ id: '42', label: 'Pinned', href: '/pinned' }, { id: '43', label: 'Unpinned', href: '/unpinned' }] }, ['42'])
		const store = useTeamAppsStore()
		const input = scope()
		await store.loadTeam(input)
		if (condition === 'unloaded team') {
			store.unloadTeam('team')
		}
		const teamId = condition === 'missing team' ? 'missing' : 'team'
		const providerId = condition === 'missing provider' ? 'missing' : 'example'
		const entryId = condition === 'unselected entry' ? '43' : condition === 'missing entry' ? 'missing' : '42'
		await expect(store.removeFromNavigation(teamId, providerId, entryId)).rejects.toThrow('cannot be removed')
		expect(post).not.toHaveBeenCalled()
		expect(input.refresh).not.toHaveBeenCalled()
	})

	it('propagates removal failures without refreshing', async () => {
		navigation({ entries: [{ id: '42', label: 'Pinned', href: '/pinned' }] })
		const store = useTeamAppsStore()
		const input = scope()
		await store.loadTeam(input)
		post.mockRejectedValueOnce(new Error('offline'))
		await expect(store.removeFromNavigation('team', 'example', '42')).rejects.toThrow('offline')
		expect(input.refresh).not.toHaveBeenCalled()
	})

	it('executes a fresh declarative prompt action, refreshes and opens the returned entry', async () => {
		navigation({ entries: [], createAction: { id: 'create', label: 'New', prompt: 'Name' } })
		const store = useTeamAppsStore()
		const input = scope()
		await store.loadTeam(input)
		const createAction = store.actions('team', prompt)[0]!
		expect(createAction.icon).toBe(mdiPlus)
		prompt.mockResolvedValue('Sketch')
		post.mockResolvedValueOnce({ data: { ocs: { data: '43' } } })
		await store.run(createAction)
		expect(getNavigations).toHaveBeenCalledTimes(2)
		expect(post).toHaveBeenCalledWith(expect.stringContaining('/teams/team/apps/example/actions/create'), { value: 'Sketch' }, { signal: expect.any(AbortSignal) })
		expect(input.refresh).toHaveBeenCalledOnce()
		expect(input.open).toHaveBeenCalledWith('example', '43')
	})

	it('does not execute or refresh a cancelled prompt', async () => {
		navigation({ entries: [], createAction: { id: 'create', label: 'New', prompt: 'Name' } })
		const store = useTeamAppsStore()
		const input = scope()
		await store.loadTeam(input)
		await store.run(store.actions('team', prompt)[0]!)
		expect(post).not.toHaveBeenCalled()
		expect(input.refresh).not.toHaveBeenCalled()
	})

	it('uses the current action dialog from the batch response', async () => {
		navigation({ entries: [], createAction: { id: 'create', label: 'New', prompt: 'Old name' } })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		getNavigations.mockResolvedValueOnce({
			providers: { example: navigationResponse({ entries: [], createAction: { id: 'create', label: 'New', prompt: 'Current name' } }) },
			errors: [],
		})
		await store.run(store.actions('team', prompt)[0]!)
		expect(prompt).toHaveBeenCalledExactlyOnceWith('Current name', store.context('team', 'example')!.signal)
		expect(getNavigations).toHaveBeenCalledTimes(2)
		expect(post).not.toHaveBeenCalled()
	})

	it.each(['failed', 'missing', 'invalid'])('does not open a dialog when batch preflight is %s', async (failure) => {
		navigation({ entries: [], createAction: { id: 'create', label: 'New', prompt: 'Name' } })
		const store = useTeamAppsStore()
		const input = scope()
		await store.loadTeam(input)
		getNavigations.mockResolvedValueOnce({
			providers: failure === 'missing' ? {} : { example: navigationResponse({ entries: [{ id: '7', label: 'Unsafe', href: 'javascript:alert(1)' }] }) },
			errors: failure === 'failed' ? ['example'] : [],
		})
		await store.run(store.actions('team', prompt)[0]!)
		expect(prompt).not.toHaveBeenCalled()
		expect(post).not.toHaveBeenCalled()
		expect(input.refresh).not.toHaveBeenCalled()
		expect(showError).toHaveBeenCalledOnce()
	})

	it('does not open a late action dialog after leaving the team', async () => {
		navigation({ entries: [], createAction: { id: 'create', label: 'New', prompt: 'Name' } })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		let complete!: (result: Awaited<ReturnType<typeof getNavigations>>) => void
		getNavigations.mockImplementationOnce(() => new Promise((resolve) => {
			complete = resolve
		}))
		const action = store.run(store.actions('team', prompt)[0]!)
		store.unloadTeam('team')
		complete({ providers: { example: navigationResponse({ entries: [], createAction: { id: 'create', label: 'New', prompt: 'Name' } }) }, errors: [] })
		await action
		expect(prompt).not.toHaveBeenCalled()
		expect(post).not.toHaveBeenCalled()
	})

	it('executes actions without a dialog', async () => {
		navigation({ entries: [], createAction: { id: 'create', label: 'New' } })
		const store = useTeamAppsStore()
		const input = scope()
		await store.loadTeam(input)
		await store.run(store.actions('team', prompt)[0]!)
		expect(prompt).not.toHaveBeenCalled()
		expect(post).toHaveBeenCalledWith(expect.stringContaining('/actions/create'), { value: null }, { signal: expect.any(AbortSignal) })
		expect(input.refresh).toHaveBeenCalledOnce()
	})

	it('rejects actions that disappeared during refresh', async () => {
		navigation({ entries: [], createAction: { id: 'create', label: 'New' } })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		getNavigations.mockResolvedValueOnce({ providers: { example: navigationResponse({ entries: [] }) }, errors: [] })
		await store.run(store.actions('team', prompt)[0]!)
		expect(post).not.toHaveBeenCalled()
		expect(showError).toHaveBeenCalledOnce()
	})

	it('loads app-owned entries with provider-prefixed IDs', async () => {
		navigation({ entries: [{ id: '7', label: 'Board', href: '/apps/example/7' }] })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(store.entries('team')[0]).toMatchObject({ id: 'example-7', providerId: 'example', entryId: '7' })
	})

	it('loads multiple providers with one batch request per load and no per-provider requests', async () => {
		providers.push({ id: 'example', name: 'Example', order: 1 }, { id: 'other', name: 'Other', order: 2 })
		getNavigations.mockResolvedValue({
			providers: {
				example: navigationResponse({ entries: [{ id: '7', label: 'Board', href: '/board' }] }),
				other: navigationResponse({ entries: [{ id: '8', label: 'Page', href: '/page' }] }),
			},
			errors: [],
		})
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		const signal = store.context('team', 'example')!.signal
		expect(getNavigations).toHaveBeenCalledExactlyOnceWith('team', signal)
		expect(store.context('team', 'other')!.signal).toBe(signal)
		expect(store.entries('team').map((entry) => entry.id)).toEqual(['example-7', 'other-8'])
		await store.loadTeam(scope())
		expect(getNavigations).toHaveBeenCalledTimes(2)
	})

	it('does not request navigation without providers', async () => {
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(getNavigations).not.toHaveBeenCalled()
		expect(store.allProvidersSettled('team')).toBe(true)
	})

	it.each(['reported', 'missing'])('isolates a %s provider failure in a batch', async (failure) => {
		providers.push({ id: 'broken', name: 'Broken', order: 1 }, { id: 'example', name: 'Example', order: 2 })
		getNavigations.mockResolvedValueOnce({
			providers: { example: navigationResponse({ entries: [{ id: '7', label: 'Board', href: '/board' }] }) },
			errors: failure === 'reported' ? ['broken'] : [],
		})
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(getNavigations).toHaveBeenCalledOnce()
		expect(store.errors('team')).toEqual(['broken'])
		expect(store.entries('team').map((entry) => entry.id)).toEqual(['example-7'])
		expect(store.allProvidersSettled('team')).toBe(true)
	})

	it('reports a failed batch request for every provider', async () => {
		providers.push({ id: 'example', name: 'Example', order: 1 }, { id: 'other', name: 'Other', order: 2 })
		getNavigations.mockRejectedValueOnce(new Error('offline'))
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(getNavigations).toHaveBeenCalledOnce()
		expect(store.errors('team')).toEqual(['example', 'other'])
		expect(store.allProvidersSettled('team')).toBe(true)
	})

	it('isolates a failed provider without leaving navigation pending', async () => {
		providers.push({ id: 'broken', name: 'Broken', order: 1 })
		getNavigations.mockResolvedValueOnce({ providers: {}, errors: ['broken'] })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(store.allProvidersSettled('team')).toBe(true)
		expect(store.errors('team')).toEqual(['broken'])
		expect(store.entries('team')).toEqual([])
	})

	it('rejects duplicate entries and unsafe URLs', async () => {
		navigation({ entries: [{ id: '1', label: 'Bad', href: 'javascript:alert(1)' }] })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(store.errors('team')).toEqual(['example'])
	})

	it('accepts previews without a custom mount module', async () => {
		navigation({ entries: [{ id: '1', label: 'Preview', href: '/f/1', referenceUrl: 'http://localhost/f/1' }] })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(store.errors('team')).toEqual([])
		expect(store.entries('team')[0]?.referenceUrl).toBe('http://localhost/f/1')
	})

	it.each(['javascript:alert(1)', 'data:text/html,test'])('rejects unsafe reference URLs: %s', async (referenceUrl) => {
		navigation({ entries: [{ id: '1', label: 'Bad', href: '/f/1', referenceUrl }] })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(store.errors('team')).toEqual(['example'])
	})

	it('does not probe providers when shared dependencies failed', async () => {
		providers.push({ id: 'example', name: 'Example', order: 1 })
		const store = useTeamAppsStore()
		await store.loadTeam({ ...scope(), sourceError: true })
		expect(getNavigations).not.toHaveBeenCalled()
		expect(store.errors('team')).toEqual(['example'])
	})

	it('aborts a team scope and ignores its late results on unload', async () => {
		let complete!: (result: Awaited<ReturnType<typeof getNavigations>>) => void
		providers.push({ id: 'example', name: 'Example', order: 1 }, { id: 'other', name: 'Other', order: 2 })
		getNavigations.mockImplementationOnce(() => new Promise((resolve) => {
			complete = resolve
		}))
		const store = useTeamAppsStore()
		const loading = store.loadTeam(scope('old'))
		const signal = store.context('old', 'example')!.signal
		expect(store.context('old', 'other')!.signal).toBe(signal)
		store.unloadTeam('old')
		complete({ providers: { example: navigationResponse({ entries: [] }), other: navigationResponse({ entries: [] }) }, errors: [] })
		await loading
		expect(signal.aborted).toBe(true)
		expect(store.providerStates.old).toBeUndefined()
	})

	it('keeps actions usable after their refresh, including open', async () => {
		providers.push({ id: 'example', name: 'Example', order: 1 })
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
		let complete!: (result: Awaited<ReturnType<typeof getNavigations>>) => void
		providers.push({ id: 'example', name: 'Example', order: 1 })
		getNavigations.mockResolvedValueOnce({ providers: { example: navigationResponse({ entries: [{ id: 'old', label: 'Old', href: '/old' }] }) }, errors: [] })
			.mockImplementationOnce(() => new Promise((resolve) => {
				complete = resolve
			}))
		const store = useTeamAppsStore()
		const input = scope()
		await store.loadTeam(input)
		const oldContext = store.context('team', 'example')!
		const oldSignal = oldContext.signal
		const refreshing = store.loadTeam(input)

		expect(store.entries('team').map((entry) => entry.entryId)).toEqual(['old'])
		expect(store.allProvidersSettled('team')).toBe(false)
		expect(store.providerReady('team', 'example')).toBe(true)
		expect(store.context('team', 'example')).toBe(oldContext)
		expect(store.context('team', 'example')!.signal).toBe(oldSignal)

		expect(getNavigations).toHaveBeenCalledTimes(2)
		complete({ providers: { example: navigationResponse({ entries: [{ id: 'new', label: 'New', href: '/new' }] }) }, errors: [] })
		await refreshing
		expect(store.entries('team').map((entry) => entry.entryId)).toEqual(['new'])
	})

	it('ignores a batch response superseded by a newer refresh', async () => {
		let complete!: (result: Awaited<ReturnType<typeof getNavigations>>) => void
		providers.push({ id: 'example', name: 'Example', order: 1 })
		getNavigations.mockImplementationOnce(() => new Promise((resolve) => {
			complete = resolve
		})).mockResolvedValueOnce({
			providers: { example: navigationResponse({ entries: [{ id: 'new', label: 'New', href: '/new' }] }) },
			errors: [],
		})
		const store = useTeamAppsStore()
		const oldLoad = store.loadTeam(scope())
		await store.loadTeam(scope())
		complete({ providers: { example: navigationResponse({ entries: [{ id: 'old', label: 'Old', href: '/old' }] }) }, errors: [] })
		await oldLoad
		expect(store.entries('team').map((entry) => entry.entryId)).toEqual(['new'])
		expect(store.errors('team')).toEqual([])
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

	it('refreshes the available team context and ignores an unloaded team', async () => {
		navigation({ entries: [] })
		providers.unshift({ id: 'unloaded', name: 'Unloaded', order: 0 })
		const store = useTeamAppsStore()
		const input = scope()
		await store.loadTeam(input)
		await store.refresh('team')
		expect(input.refresh).toHaveBeenCalledOnce()
		store.unloadTeam('team')
		await store.refresh('team')
		expect(input.refresh).toHaveBeenCalledOnce()
	})

	it('does not execute another action while the current action is pending', async () => {
		const store = useTeamAppsStore()
		let finish!: () => void
		const pending = new Promise<void>((resolve) => {
			finish = resolve
		})
		const second = vi.fn()
		const first = store.run({ id: 'first', label: 'First', run: () => pending })
		expect(store.busy).toBe(true)
		await store.run({ id: 'second', label: 'Second', run: second })
		expect(second).not.toHaveBeenCalled()
		finish()
		await first
		expect(store.busy).toBe(false)
	})

	it.each(['member', 'loading', 'error', 'not ready'])('does not offer existing-resource actions for a %s context', async (condition) => {
		navigation({ entries: [{ id: '42', label: 'Candidate', href: '/candidate' }], createAction: { id: 'create', label: 'New' } }, [])
		const store = useTeamAppsStore()
		await store.loadTeam({ ...scope(), isAdmin: condition !== 'member' })
		const provider = store.providerStates.team!.example!
		provider.loading = condition === 'loading'
		provider.error = condition === 'error'
		provider.ready = condition !== 'not ready'
		expect(store.existingResources('team')).toEqual([])
		if (condition !== 'member') {
			expect(store.actions('team', prompt)).toEqual([])
		}
	})

	it('offers no creation or add actions when all entries are already selected', async () => {
		navigation({ entries: [{ id: '42', label: 'Pinned', href: '/pinned' }] })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(store.actions('team', prompt)).toEqual([])
		expect(store.existingResources('team')).toEqual([])
	})

	it('keeps an explicit empty selection empty and offers all entries for adding', async () => {
		navigation({ entries: [{ id: '42', label: 'Board', href: '/board' }, { id: '43', label: 'Page', href: '/page' }] }, [])
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(store.entries('team')).toEqual([])
		expect(store.existingResources('team')[0]!.actions.map(({ label }) => label)).toEqual(['Board', 'Page'])
	})

	it('reports registered providers as pending until their team has been loaded', async () => {
		navigation({ entries: [] })
		const store = useTeamAppsStore()
		expect(store.allProvidersSettled('fresh-team')).toBe(false)
		expect(store.providerReady('fresh-team', 'example')).toBe(false)
		expect(store.entries('fresh-team')).toEqual([])
		expect(store.actions('fresh-team', prompt)).toEqual([])
		expect(store.existingResources('fresh-team')).toEqual([])
		expect(store.errors('fresh-team')).toEqual([])
		await store.loadTeam(scope('fresh-team'))
		expect(store.allProvidersSettled('fresh-team')).toBe(true)
	})

	it('preserves a provider-supplied creation icon', async () => {
		navigation({ entries: [], createAction: { id: 'create', label: 'New', icon: 'custom-icon' } })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(store.actions('team', prompt)[0]!.icon).toBe('custom-icon')
	})

	it.each([
		null,
		{ entries: null },
		{ entries: [{ id: '', label: 'Missing ID', href: '/bad' }] },
		{ entries: [{ id: '42', label: '', href: '/bad' }] },
		{ entries: [{ id: '42', label: 'Missing URL', href: '' }] },
		{ entries: [{ id: 'x'.repeat(65), label: 'Long ID', href: '/bad' }] },
		{ entries: [{ id: '42', label: 'First', href: '/first' }, { id: '42', label: 'Duplicate', href: '/duplicate' }] },
	])('rejects malformed or duplicate provider navigation: %j', async (response) => {
		providers.push({ id: 'example', name: 'Example', order: 1 })
		getNavigations.mockResolvedValueOnce({ providers: { example: response as unknown as TeamAppNavigationResponse }, errors: [] })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(store.errors('team')).toEqual(['example'])
		expect(store.entries('team')).toEqual([])
	})
})
