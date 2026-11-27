/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { getTeamAppNavigations, resolveTeamReferences } from '../api.ts'
import type { TeamAppScope } from './teamAppsStore.ts'
import type { TeamAppNavigationDescriptor, TeamAppNavigationResponse, TeamAppNavigationsResponse, TeamNavigationEntry, TeamOwnedResourceProvider } from './types.ts'

import { mdiPlus } from '@mdi/js'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { navigationId, useTeamAppsStore } from './teamAppsStore.ts'

const { providers, post, getNavigations, resolveReferences, showError, saveOrder, resourceState } = vi.hoisted(() => ({
	providers: [] as TeamOwnedResourceProvider[],
	post: vi.fn(),
	getNavigations: vi.fn<typeof getTeamAppNavigations>(),
	resolveReferences: vi.fn<typeof resolveTeamReferences>(),
	showError: vi.fn(),
	saveOrder: vi.fn(),
	resourceState: { navigation: [] as TeamNavigationEntry[], orderChecked: false, orderError: false },
}))
vi.mock('./providerRegistry.ts', () => ({
	getTeamOwnedResourceProviders: () => providers,
	getTeamAppRegistrationErrors: () => [],
}))
vi.mock('../api.ts', () => ({ getTeamAppNavigations: getNavigations, resolveTeamReferences: resolveReferences }))
vi.mock('../resourcesStore.ts', () => ({
	useTeamResourcesStore: () => ({
		forTeam: () => resourceState,
		slot: () => resourceState,
		applyNavigation: (_teamId: string, entries: TeamNavigationEntry[]) => {
			resourceState.navigation = entries
		},
		saveOrder: async (teamId: string, entries: TeamNavigationEntry[]) => {
			await saveOrder(teamId, entries)
			resourceState.navigation = entries
		},
	}),
}))
vi.mock('@nextcloud/axios', () => ({ default: { post } }))
vi.mock('@nextcloud/dialogs', () => ({ showConfirmation: vi.fn(), showError }))

function scope(teamId = 'team'): TeamAppScope {
	return { teamId, teamName: teamId, isAdmin: true, folder: null, resources: [], refresh: vi.fn(), open: vi.fn() }
}

type NavigationFixture = Partial<Omit<TeamAppNavigationDescriptor, 'entries'>> & {
	entries: Array<Omit<TeamAppNavigationDescriptor['entries'][number], 'provider'>>
}

function navigationResponse(value: NavigationFixture, providerId = 'example'): TeamAppNavigationResponse {
	return { canAddResource: false, ...value, entries: value.entries.map((entry) => ({ ...entry, provider: { id: providerId, name: providerId } })) }
}
function batchResponse(value: Omit<TeamAppNavigationsResponse, 'entries'> & { entries?: TeamNavigationEntry[] }): TeamAppNavigationsResponse {
	return {
		entries: [
			{ id: 'team-folder' },
			...Object.entries(value.providers).flatMap(([providerId, navigation]) => navigation.entries
				.map(({ id }) => ({ id: navigationId(providerId, id), providerId, entryId: id }))),
			{ id: 'home' },
		],
		...value,
	}
}

function navigation(value: NavigationFixture): void {
	providers.push({ id: 'example', name: 'Example' })
	getNavigations.mockResolvedValue(batchResponse({ providers: { example: navigationResponse(value) }, errors: [] }))
}

describe('generic Teams app host', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
		providers.splice(0)
		vi.clearAllMocks()
		resolveReferences.mockReset().mockResolvedValue({})
		resourceState.navigation = [{ id: 'team-folder' }, { id: 'home' }]
		resourceState.orderChecked = false
		resourceState.orderError = false
		saveOrder.mockReset().mockResolvedValue(undefined)
		post.mockReset().mockResolvedValue({ data: { ocs: { data: {} } } })
		getNavigations.mockReset().mockResolvedValue(batchResponse({ providers: { example: navigationResponse({ entries: [] }) }, errors: [] }))
	})

	it('uses provider-id navigation IDs without parsing app-local IDs', () => {
		expect(navigationId('text', '42')).toBe('text-42')
		expect(navigationId('whiteboard', 'draft-7')).toBe('whiteboard-draft-7')
	})

	it('shows all provider entries in global order', async () => {
		navigation({ entries: [
			{ id: '42', label: 'First', url: '/first' },
			{ id: '43', label: 'Second', url: '/second' },
		] })
		getNavigations.mockResolvedValueOnce({
			entries: [
				{ id: 'home' },
				{ id: 'example-43', providerId: 'example', entryId: '43' },
				{ id: 'example-42', providerId: 'example', entryId: '42' },
				{ id: 'team-folder' },
			],
			providers: { example: navigationResponse({
				entries: [
					{ id: '42', label: 'First', url: '/first' },
					{ id: '43', label: 'Second', url: '/second' },
				],
			}) },
			errors: [],
		})
		const store = useTeamAppsStore()

		await store.loadTeam(scope())

		expect(resourceState).toMatchObject({
			navigation: [
				{ id: 'home' },
				{ id: 'example-43', providerId: 'example', entryId: '43' },
				{ id: 'example-42', providerId: 'example', entryId: '42' },
				{ id: 'team-folder' },
			],
			orderChecked: true,
			orderError: false,
		})
		expect(store.entries('team').map(({ entryId }) => entryId)).toEqual(['43', '42'])
	})

	it('creates with the provider default, refreshes and opens the returned entry', async () => {
		navigation({ entries: [], canAddResource: true })
		const store = useTeamAppsStore()
		const input = scope()
		await store.loadTeam(input)
		const createAction = store.actions('team')[0]!
		expect(createAction.icon).toBe(mdiPlus)
		post.mockResolvedValueOnce({ data: { ocs: { data: '43' } } })
		await store.run(createAction)
		expect(getNavigations).toHaveBeenCalledTimes(2)
		expect(post).toHaveBeenCalledWith(expect.stringContaining('/teams/team/apps/example/resources'), { order: ['team-folder', 'home'] }, { signal: expect.any(AbortSignal) })
		expect(input.refresh).toHaveBeenCalledOnce()
		expect(input.open).toHaveBeenCalledWith('example', '43')
	})

	it('rejects a stale create command when creation becomes unavailable', async () => {
		navigation({ entries: [], canAddResource: true })
		const store = useTeamAppsStore()
		const input = scope()
		await store.loadTeam(input)
		getNavigations.mockResolvedValueOnce(batchResponse({
			providers: { example: navigationResponse({ entries: [], canAddResource: false }) },
			errors: [],
		}))
		await store.run(store.actions('team')[0]!)
		expect(getNavigations).toHaveBeenCalledTimes(2)
		expect(post).not.toHaveBeenCalled()
		expect(input.refresh).not.toHaveBeenCalled()
		expect(showError).toHaveBeenCalledOnce()
	})

	it.each(['failed', 'missing', 'invalid'])('does not create when batch preflight is %s', async (failure) => {
		navigation({ entries: [], canAddResource: true })
		const store = useTeamAppsStore()
		const input = scope()
		await store.loadTeam(input)
		getNavigations.mockResolvedValueOnce({
			entries: [{ id: 'team-folder' }, { id: 'home' }],
			providers: failure === 'missing' ? {} : { example: navigationResponse({ entries: [{ id: '7', label: 'Unsafe', url: 'javascript:alert(1)' }] }) },
			errors: failure === 'failed' ? ['example'] : [],
		})
		await store.run(store.actions('team')[0]!)
		expect(post).not.toHaveBeenCalled()
		expect(input.refresh).not.toHaveBeenCalled()
		expect(showError).toHaveBeenCalledOnce()
	})

	it('does not create a late action after leaving the team', async () => {
		navigation({ entries: [], canAddResource: true })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		let complete!: (result: Awaited<ReturnType<typeof getNavigations>>) => void
		getNavigations.mockImplementationOnce(() => new Promise((resolve) => {
			complete = resolve
		}))
		const action = store.run(store.actions('team')[0]!)
		store.unloadTeam('team')
		complete(batchResponse({ providers: { example: navigationResponse({ entries: [], canAddResource: true }) }, errors: [] }))
		await action
		expect(post).not.toHaveBeenCalled()
	})

	it('rejects actions that disappeared during refresh', async () => {
		navigation({ entries: [], canAddResource: true })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		getNavigations.mockResolvedValueOnce(batchResponse({ providers: { example: navigationResponse({ entries: [] }) }, errors: [] }))
		await store.run(store.actions('team')[0]!)
		expect(post).not.toHaveBeenCalled()
		expect(showError).toHaveBeenCalledOnce()
	})

	it('loads app-owned entries with provider-prefixed IDs', async () => {
		navigation({ entries: [{ id: '7', label: 'Board', url: '/apps/example/7' }] })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(store.entries('team')[0]).toMatchObject({ id: 'example-7', providerId: 'example', entryId: '7' })
	})

	it('preserves provider resource emojis in navigation entries', async () => {
		navigation({ entries: [{ id: '7', label: 'Collective', url: '/apps/collectives/7', iconEmoji: '\u{1F4DA}' }] })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(store.entries('team')[0]?.icon).toBe('\u{1F4DA}')
	})

	it('loads multiple providers with one batch request per load and no per-provider requests', async () => {
		providers.push({ id: 'example', name: 'Example' }, { id: 'other', name: 'Other' })
		getNavigations.mockResolvedValue(batchResponse({
			entries: [{ id: 'other-8', providerId: 'other', entryId: '8' }, { id: 'home' }, { id: 'example-7', providerId: 'example', entryId: '7' }, { id: 'team-folder' }],
			providers: {
				example: navigationResponse({ entries: [{ id: '7', label: 'Board', url: '/board' }] }),
				other: navigationResponse({ entries: [{ id: '8', label: 'Page', url: '/page' }] }, 'other'),
			},
			errors: [],
		}))
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		const signal = store.context('team', 'example')!.signal
		expect(getNavigations).toHaveBeenCalledExactlyOnceWith('team', signal)
		expect(store.context('team', 'other')!.signal).toBe(signal)
		expect(store.entries('team').map((entry) => entry.id)).toEqual(['other-8', 'example-7'])
		await store.loadTeam(scope())
		expect(getNavigations).toHaveBeenCalledTimes(2)
	})

	it('loads the tab order even without app providers', async () => {
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(getNavigations).toHaveBeenCalledOnce()
		expect(store.allProvidersSettled('team')).toBe(true)
		expect(resourceState.orderChecked).toBe(true)
	})

	it('settles a failed navigation load without app providers', async () => {
		getNavigations.mockRejectedValueOnce(new Error('offline'))
		await useTeamAppsStore().loadTeam(scope())
		expect(resourceState.orderError).toBe(true)
		expect(resourceState.orderChecked).toBe(false)
	})

	it.each(['reported', 'missing'])('isolates a %s provider failure in a batch', async (failure) => {
		providers.push({ id: 'broken', name: 'Broken' }, { id: 'example', name: 'Example' })
		getNavigations.mockResolvedValueOnce(batchResponse({
			providers: { example: navigationResponse({ entries: [{ id: '7', label: 'Board', url: '/board' }] }) },
			errors: failure === 'reported' ? ['broken'] : [],
		}))
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(getNavigations).toHaveBeenCalledOnce()
		expect(store.errors('team')).toEqual(['broken'])
		expect(store.entries('team').map((entry) => entry.id)).toEqual(['example-7'])
		expect(store.allProvidersSettled('team')).toBe(true)
	})

	it('reports a failed batch request for every provider', async () => {
		providers.push({ id: 'example', name: 'Example' }, { id: 'other', name: 'Other' })
		getNavigations.mockRejectedValueOnce(new Error('offline'))
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(getNavigations).toHaveBeenCalledOnce()
		expect(store.errors('team')).toEqual(['example', 'other'])
		expect(store.allProvidersSettled('team')).toBe(true)
	})

	it('isolates a failed provider without leaving navigation pending', async () => {
		providers.push({ id: 'broken', name: 'Broken' })
		getNavigations.mockResolvedValueOnce(batchResponse({ providers: {}, errors: ['broken'] }))
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(store.allProvidersSettled('team')).toBe(true)
		expect(store.errors('team')).toEqual(['broken'])
		expect(store.entries('team')).toEqual([])
	})

	it('rejects duplicate entries and unsafe URLs', async () => {
		navigation({ entries: [{ id: '1', label: 'Bad', url: 'javascript:alert(1)' }] })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(store.errors('team')).toEqual(['example'])
	})

	it('resolves resource URLs before marking navigation ready', async () => {
		navigation({ entries: [{ id: '1', label: 'Preview', url: '/f/1' }, { id: '2', label: 'Link', url: '/apps/example' }] })
		const reference = { accessible: true, richObjectType: 'file', richObject: {}, openGraphObject: { id: '1', name: 'Preview', description: null, thumb: null, link: '/f/1' } }
		let resolve!: (value: Awaited<ReturnType<typeof resolveTeamReferences>>) => void
		resolveReferences.mockImplementationOnce(() => new Promise((complete) => {
			resolve = complete
		}))
		const store = useTeamAppsStore()
		const loading = store.loadTeam(scope())
		await vi.waitFor(() => expect(resolveReferences).toHaveBeenCalledWith(['/f/1', '/apps/example'], expect.any(AbortSignal)))
		expect(store.allProvidersSettled('team')).toBe(false)
		resolve({ [new URL('/f/1', window.location.href).href]: reference })
		await loading
		expect(store.errors('team')).toEqual([])
		expect(store.entries('team')[0]?.reference).toEqual(reference)
		expect(store.entries('team')[1]?.reference).toBeUndefined()
	})

	it('resolves a separate reference URL while retaining the navigation URL', async () => {
		navigation({ entries: [
			{ id: '1', label: 'Preview', url: '/open/1', referenceUrl: '/reference/1' },
			{ id: '2', label: 'Legacy', url: '/open/2' },
		] })
		const reference = { accessible: true, richObjectType: 'file', richObject: {}, openGraphObject: { id: '1', name: 'Preview', description: null, thumb: null, link: '/reference/1' } }
		resolveReferences.mockResolvedValueOnce({ [new URL('/reference/1', window.location.href).href]: reference })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(resolveReferences).toHaveBeenCalledWith(['/reference/1', '/open/2'], expect.any(AbortSignal))
		expect(store.entries('team')[0]).toMatchObject({ href: '/open/1', referenceUrl: '/reference/1', reference })
		expect(store.entries('team')[1]).toMatchObject({ href: '/open/2', referenceUrl: '/open/2' })
	})

	it('rejects unsafe reference URLs', async () => {
		navigation({ entries: [{ id: '1', label: 'Bad', url: '/open', referenceUrl: 'javascript:alert(1)' }] })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(store.errors('team')).toEqual(['example'])
		expect(store.entries('team')).toEqual([])
	})

	it('keeps links usable when reference resolution fails', async () => {
		navigation({ entries: [{ id: '1', label: 'Preview', url: '/f/1' }] })
		resolveReferences.mockRejectedValueOnce(new Error('offline'))
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(store.errors('team')).toEqual([])
		expect(store.entries('team')[0]).toMatchObject({ href: '/f/1', reference: undefined })
	})

	it('ignores a reference response after leaving the team', async () => {
		navigation({ entries: [{ id: '1', label: 'Preview', url: '/f/1' }] })
		let resolve!: (value: Awaited<ReturnType<typeof resolveTeamReferences>>) => void
		resolveReferences.mockImplementationOnce(() => new Promise((complete) => {
			resolve = complete
		}))
		const store = useTeamAppsStore()
		const loading = store.loadTeam(scope())
		await vi.waitFor(() => expect(resolveReferences).toHaveBeenCalledOnce())
		const signal = resolveReferences.mock.calls[0]![1]
		store.unloadTeam('team')
		expect(signal.aborted).toBe(true)
		resolve({})
		await loading
		expect(store.entries('team')).toEqual([])
	})

	it('does not probe providers when shared dependencies failed', async () => {
		providers.push({ id: 'example', name: 'Example' })
		const store = useTeamAppsStore()
		await store.loadTeam({ ...scope(), sourceError: true })
		expect(getNavigations).not.toHaveBeenCalled()
		expect(store.errors('team')).toEqual(['example'])
	})

	it('aborts a team scope and ignores its late results on unload', async () => {
		let complete!: (result: Awaited<ReturnType<typeof getNavigations>>) => void
		providers.push({ id: 'example', name: 'Example' }, { id: 'other', name: 'Other' })
		getNavigations.mockImplementationOnce(() => new Promise((resolve) => {
			complete = resolve
		}))
		const store = useTeamAppsStore()
		const loading = store.loadTeam(scope('old'))
		const signal = store.context('old', 'example')!.signal
		expect(store.context('old', 'other')!.signal).toBe(signal)
		store.unloadTeam('old')
		complete(batchResponse({ providers: { example: navigationResponse({ entries: [] }), other: navigationResponse({ entries: [] }) }, errors: [] }))
		await loading
		expect(signal.aborted).toBe(true)
		expect(store.providerStates.old).toBeUndefined()
	})

	it('keeps actions usable after their refresh, including open', async () => {
		providers.push({ id: 'example', name: 'Example' })
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
		providers.push({ id: 'example', name: 'Example' })
		getNavigations.mockResolvedValueOnce(batchResponse({ providers: { example: navigationResponse({ entries: [{ id: 'old', label: 'Old', url: '/old' }] }) }, errors: [] }))
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
		complete(batchResponse({ providers: { example: navigationResponse({ entries: [{ id: 'new', label: 'New', url: '/new' }] }) }, errors: [] }))
		await refreshing
		expect(store.entries('team').map((entry) => entry.entryId)).toEqual(['new'])
	})

	it('keeps the last valid entries when a selected provider fails during refresh', async () => {
		navigation({ entries: [{ id: 'old', label: 'Old', url: '/old' }] })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		getNavigations.mockResolvedValueOnce({
			entries: [{ id: 'home' }, { id: 'example-old', providerId: 'example', entryId: 'old' }, { id: 'team-folder' }],
			providers: {},
			errors: ['example'],
		})

		await store.loadTeam(scope())

		expect(store.entries('team').map(({ id }) => id)).toEqual(['example-old'])
		expect(store.errors('team')).toEqual(['example'])
		expect(resourceState.navigation.map(({ id }) => id)).toEqual(['home', 'example-old', 'team-folder'])
	})

	it('ignores a batch response superseded by a newer refresh', async () => {
		let complete!: (result: Awaited<ReturnType<typeof getNavigations>>) => void
		providers.push({ id: 'example', name: 'Example' })
		getNavigations.mockImplementationOnce(() => new Promise((resolve) => {
			complete = resolve
		})).mockResolvedValueOnce({
			entries: [{ id: 'home' }, { id: 'example-new', providerId: 'example', entryId: 'new' }],
			providers: { example: navigationResponse({ entries: [{ id: 'new', label: 'New', url: '/new' }] }) },
			errors: [],
		})
		const store = useTeamAppsStore()
		const oldLoad = store.loadTeam(scope())
		await store.loadTeam(scope())
		complete({ entries: [{ id: 'example-old', providerId: 'example', entryId: 'old' }, { id: 'home' }], providers: { example: navigationResponse({ entries: [{ id: 'old', label: 'Old', url: '/old' }] }) }, errors: [] })
		await oldLoad
		expect(store.entries('team').map((entry) => entry.entryId)).toEqual(['new'])
		expect(resourceState.navigation).toEqual([{ id: 'home' }, { id: 'example-new', providerId: 'example', entryId: 'new' }])
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
		providers.unshift({ id: 'unloaded', name: 'Unloaded' })
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

	it.each(['loading', 'error', 'not ready'])('does not offer create actions for a %s provider', async (condition) => {
		navigation({ entries: [{ id: '42', label: 'Candidate', url: '/candidate' }], canAddResource: true })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		const provider = store.providerStates.team!.example!
		provider.loading = condition === 'loading'
		provider.error = condition === 'error'
		provider.ready = condition !== 'not ready'
		expect(store.actions('team')).toEqual([])
	})

	it('shows provider entries even when the provider has no create action', async () => {
		navigation({ entries: [{ id: '42', label: 'Pinned', url: '/pinned' }] })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(store.entries('team').map(({ entryId }) => entryId)).toEqual(['42'])
		expect(store.actions('team')).toEqual([])
	})

	it('reports registered providers as pending until their team has been loaded', async () => {
		navigation({ entries: [] })
		const store = useTeamAppsStore()
		expect(store.allProvidersSettled('fresh-team')).toBe(false)
		expect(store.providerReady('fresh-team', 'example')).toBe(false)
		expect(store.entries('fresh-team')).toEqual([])
		expect(store.actions('fresh-team')).toEqual([])
		expect(store.errors('fresh-team')).toEqual([])
		await store.loadTeam(scope('fresh-team'))
		expect(store.allProvidersSettled('fresh-team')).toBe(true)
	})

	it('labels the create action with the app name and uses its app icon', async () => {
		navigation({ entries: [], canAddResource: true })
		providers[0]!.iconUrl = '/apps/example/img/app.svg'
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(store.actions('team')[0]).toMatchObject({ label: 'Add Example', iconUrl: '/apps/example/img/app.svg' })
	})

	it.each([
		null,
		{ entries: null },
		{ entries: [{ id: '', label: 'Missing ID', url: '/bad' }] },
		{ entries: [{ id: '42', label: '', url: '/bad' }] },
		{ entries: [{ id: '42', label: 'Missing URL', url: '' }] },
		{ entries: [{ id: 'x'.repeat(65), label: 'Long ID', url: '/bad' }] },
		{ entries: [{ id: '42', label: 'First', url: '/first' }, { id: '42', label: 'Duplicate', url: '/duplicate' }] },
	])('rejects malformed or duplicate provider navigation: %j', async (response) => {
		providers.push({ id: 'example', name: 'Example' })
		getNavigations.mockResolvedValueOnce({ entries: [{ id: 'team-folder' }, { id: 'home' }], providers: { example: response as unknown as TeamAppNavigationResponse }, errors: [] })
		const store = useTeamAppsStore()
		await store.loadTeam(scope())
		expect(store.errors('team')).toEqual(['example'])
		expect(store.entries('team')).toEqual([])
	})
})
