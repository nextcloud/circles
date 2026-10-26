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
	providers.push({ id: 'example', order: 1 })
	getNavigations.mockResolvedValue({ providers: { example: navigationResponse(value, selectedEntryIds) }, errors: [] })
}

describe('generic Teams app host', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
		providers.splice(0)
		vi.clearAllMocks()
		post.mockReset().mockResolvedValue({ data: { ocs: { data: {} } } })
		getNavigations.mockReset().mockResolvedValue({ providers: { example: navigationResponse({ entries: [] }) }, errors: [] })
	})

	it('uses provider-id navigation IDs without parsing app-local IDs', () => {
		expect(navigationId('text', '42')).toBe('text-42')
		expect(navigationId('whiteboard', 'draft-7')).toBe('whiteboard-draft-7')
	})

	it('shows selected entries and exposes host-owned add and remove actions', async () => {
		navigation({
			entries: [{ id: '42', label: 'Pinned', href: '/pinned' }, { id: '43', label: 'Unpinned', href: '/unpinned' }],
		}, ['42'])
		const store = useTeamAppsStore()
		const input = scope()
		await store.loadTeam(input)

		expect(store.entries('team').map((entry) => entry.entryId)).toEqual(['42'])
		const remove = store.entries('team')[0]!.actions![0]!
		expect(remove.label).toBe('Remove from navigation')
		const add = store.actions('team').find((action) => action.label === 'Add Unpinned to navigation')!
		await store.run(add)

		expect(post).toHaveBeenCalledWith(
			expect.stringContaining('/apps/example/entries/navigation'),
			{ entryId: '43', inNavigation: true },
			{ signal: expect.any(AbortSignal) },
		)
		post.mockClear()
		await store.run(remove)
		expect(post).toHaveBeenCalledWith(
			expect.stringContaining('/apps/example/entries/navigation'),
			{ entryId: '42', inNavigation: false },
			{ signal: expect.any(AbortSignal) },
		)
		expect(input.refresh).toHaveBeenCalledTimes(2)
	})

	it('executes a fresh declarative prompt action, refreshes and opens the returned entry', async () => {
		navigation({ entries: [], actions: [{ id: 'create', label: 'New', dialog: { type: 'prompt', title: 'Name' } }] })
		const store = useTeamAppsStore()
		const input = scope()
		await store.loadTeam(input)
		const createAction = store.actions('team')[0]!
		expect(createAction.icon).toBe(mdiPlus)
		vi.spyOn(store.context('team', 'example')!, 'prompt').mockResolvedValue('Sketch')
		post.mockResolvedValueOnce({ data: { ocs: { data: { open: '43' } } } })
		await store.run(createAction)
		expect(getNavigations).toHaveBeenCalledTimes(2)
		expect(post).toHaveBeenCalledWith(expect.stringContaining('/teams/team/apps/example/actions/create'), { parameters: { value: 'Sketch' } }, { signal: expect.any(AbortSignal) })
		expect(input.refresh).toHaveBeenCalledOnce()
		expect(input.open).toHaveBeenCalledWith('example', '43')
	})

	it('does not execute or refresh a cancelled prompt', async () => {
		navigation({ entries: [], actions: [{ id: 'create', label: 'New', dialog: { type: 'prompt', title: 'Name' } }] })
		const store = useTeamAppsStore()
		const input = scope()
		await store.loadTeam(input)
		vi.spyOn(store.context('team', 'example')!, 'prompt').mockResolvedValue(null)
		await store.run(store.actions('team')[0]!)
		expect(post).not.toHaveBeenCalled()
		expect(input.refresh).not.toHaveBeenCalled()
	})

	it('uses the current action dialog from the batch response', async () => {
		navigation({ entries: [], actions: [{ id: 'create', label: 'New', dialog: { type: 'prompt', title: 'Old name' } }] })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		getNavigations.mockResolvedValueOnce({
			providers: { example: navigationResponse({ entries: [], actions: [{ id: 'create', label: 'New', dialog: { type: 'prompt', title: 'Current name', value: 'Draft' } }] }) },
			errors: [],
		})
		const prompt = vi.spyOn(store.context('team', 'example')!, 'prompt').mockResolvedValue(null)
		await store.run(store.actions('team')[0]!)
		expect(prompt).toHaveBeenCalledExactlyOnceWith('Current name', 'Draft')
		expect(getNavigations).toHaveBeenCalledTimes(2)
		expect(post).not.toHaveBeenCalled()
	})

	it.each(['failed', 'missing', 'invalid'])('does not open a dialog when batch preflight is %s', async (failure) => {
		navigation({ entries: [], actions: [{ id: 'create', label: 'New', dialog: { type: 'prompt', title: 'Name' } }] })
		const store = useTeamAppsStore()
		const input = scope()
		await store.loadTeam(input)
		getNavigations.mockResolvedValueOnce({
			providers: failure === 'missing' ? {} : { example: navigationResponse({ entries: [{ id: '7', label: 'Unsafe', href: 'javascript:alert(1)' }] }) },
			errors: failure === 'failed' ? ['example'] : [],
		})
		const prompt = vi.spyOn(store.context('team', 'example')!, 'prompt')
		await store.run(store.actions('team')[0]!)
		expect(prompt).not.toHaveBeenCalled()
		expect(post).not.toHaveBeenCalled()
		expect(input.refresh).not.toHaveBeenCalled()
		expect(showError).toHaveBeenCalledOnce()
	})

	it('does not open a late action dialog after leaving the team', async () => {
		navigation({ entries: [], actions: [{ id: 'create', label: 'New', dialog: { type: 'prompt', title: 'Name' } }] })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		let complete!: (result: Awaited<ReturnType<typeof getNavigations>>) => void
		getNavigations.mockImplementationOnce(() => new Promise((resolve) => {
			complete = resolve
		}))
		const prompt = vi.spyOn(store.context('team', 'example')!, 'prompt')
		const action = store.run(store.actions('team')[0]!)
		store.unloadTeam('team')
		complete({ providers: { example: navigationResponse({ entries: [], actions: [{ id: 'create', label: 'New', dialog: { type: 'prompt', title: 'Name' } }] }) }, errors: [] })
		await action
		expect(prompt).not.toHaveBeenCalled()
		expect(post).not.toHaveBeenCalled()
	})

	it.each([true, false])('executes a declarative deletion only when confirmed (%s)', async (confirmed) => {
		navigation({ entries: [{ id: '7', label: 'Board', href: '/board', actions: [{ id: 'delete-7', label: 'Delete', dialog: { type: 'confirm', title: 'Delete', message: 'Delete board?' } }] }] })
		const store = useTeamAppsStore()
		const input = scope()
		await store.loadTeam(input)
		const context = store.context('team', 'example')!
		const confirm = vi.spyOn(context, 'confirm').mockResolvedValue(confirmed)
		const prompt = vi.spyOn(context, 'prompt')
		await store.run(store.entries('team')[0]!.actions![0]!)
		expect(confirm).toHaveBeenCalledExactlyOnceWith('Delete', 'Delete board?')
		expect(prompt).not.toHaveBeenCalled()
		if (confirmed) {
			expect(post).toHaveBeenCalledWith(expect.stringContaining('/actions/delete-7'), { parameters: {} }, { signal: expect.any(AbortSignal) })
			expect(input.refresh).toHaveBeenCalledOnce()
		} else {
			expect(post).not.toHaveBeenCalled()
			expect(input.refresh).not.toHaveBeenCalled()
		}
	})

	it('executes actions without a dialog', async () => {
		navigation({ entries: [], actions: [{ id: 'create', label: 'New' }] })
		const store = useTeamAppsStore()
		const input = scope()
		await store.loadTeam(input)
		const context = store.context('team', 'example')!
		const prompt = vi.spyOn(context, 'prompt')
		const confirm = vi.spyOn(context, 'confirm')
		await store.run(store.actions('team')[0]!)
		expect(prompt).not.toHaveBeenCalled()
		expect(confirm).not.toHaveBeenCalled()
		expect(post).toHaveBeenCalledWith(expect.stringContaining('/actions/create'), { parameters: {} }, { signal: expect.any(AbortSignal) })
		expect(input.refresh).toHaveBeenCalledOnce()
	})

	it('does not execute an action with an unknown dialog type', async () => {
		providers.push({ id: 'example', order: 1 })
		getNavigations.mockResolvedValue({
			// @ts-expect-error Simulate an unsupported server-provided dialog.
			providers: { example: navigationResponse({ entries: [], actions: [{ id: 'create', label: 'New', dialog: { type: 'unknown' } }] }) },
			errors: [],
		})
		const store = useTeamAppsStore()
		const input = scope()
		await store.loadTeam(input)
		await store.run(store.actions('team')[0]!)
		expect(post).not.toHaveBeenCalled()
		expect(input.refresh).not.toHaveBeenCalled()
		expect(showError).toHaveBeenCalledOnce()
	})

	it('rejects actions that disappeared during refresh', async () => {
		navigation({ entries: [], actions: [{ id: 'create', label: 'New' }] })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		getNavigations.mockResolvedValueOnce({ providers: { example: navigationResponse({ entries: [], actions: [] }) }, errors: [] })
		await store.run(store.actions('team')[0]!)
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
		providers.push({ id: 'example', order: 1 }, { id: 'other', order: 2 })
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
		providers.push({ id: 'broken', order: 1 }, { id: 'example', order: 2 })
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
		providers.push({ id: 'example', order: 1 }, { id: 'other', order: 2 })
		getNavigations.mockRejectedValueOnce(new Error('offline'))
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(getNavigations).toHaveBeenCalledOnce()
		expect(store.errors('team')).toEqual(['example', 'other'])
		expect(store.allProvidersSettled('team')).toBe(true)
	})

	it('isolates a failed provider without leaving navigation pending', async () => {
		providers.push({ id: 'broken', order: 1 })
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
		providers.push({ id: 'example', order: 1 })
		const store = useTeamAppsStore()
		await store.loadTeam({ ...scope(), sourceError: true })
		expect(getNavigations).not.toHaveBeenCalled()
		expect(store.errors('team')).toEqual(['example'])
	})

	it('aborts a team scope and ignores its late results on unload', async () => {
		let complete!: (result: Awaited<ReturnType<typeof getNavigations>>) => void
		providers.push({ id: 'example', order: 1 }, { id: 'other', order: 2 })
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
		providers.push({ id: 'example', order: 1 })
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
		providers.push({ id: 'example', order: 1 })
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
		providers.push({ id: 'example', order: 1 })
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
})
