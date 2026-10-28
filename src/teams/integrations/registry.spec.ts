/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TeamAppView } from './types.ts'

import { beforeEach, describe, expect, it, vi } from 'vitest'

const state = vi.hoisted(() => ({ providers: [{ id: 'example', order: 5, routeAliases: [] as Array<{ path: string, entryParam: string }>, module: '/apps/example/js/teams.mjs' as string | null }], errors: [] as string[] }))
const loadModule = vi.hoisted(() => vi.fn())
const get = vi.hoisted(() => vi.fn())
vi.mock('@nextcloud/axios', () => ({ default: { get } }))
vi.mock('@nextcloud/initial-state', () => ({ loadState: () => state }))
vi.mock('./module.ts', () => ({ loadTeamAppModule: loadModule }))

describe('Teams app registration', () => {
	const provider: TeamAppView = { mount: vi.fn() }

	beforeEach(() => {
		vi.restoreAllMocks()
		vi.resetModules()
		window.OCA = {} as typeof window.OCA
		state.providers = [{ id: 'example', order: 5, routeAliases: [], module: '/apps/example/js/teams.mjs' }]
		state.errors = []
		loadModule.mockReset().mockResolvedValue({ apiVersion: 2, default: provider })
		get.mockReset().mockResolvedValue({ data: { ocs: { data: { entries: [] } } } })
	})

	it('loads only modules declared by PHP without browser registration or readiness events', async () => {
		const dispatch = vi.spyOn(window, 'dispatchEvent')
		const registry = await import('./registry.ts')
		await registry.initializeTeamApps()
		expect(loadModule).toHaveBeenCalledExactlyOnceWith('http://localhost/apps/example/js/teams.mjs')
		expect(registry.getTeamAppProviders()).toMatchObject([{ id: 'example', order: 5, mount: provider.mount }])
		expect(registry.getTeamAppRegistrationErrors()).toEqual([])
		expect(window.OCA).toEqual({})
		expect(dispatch).not.toHaveBeenCalled()
	})

	it('isolates failed imports and preserves PHP errors', async () => {
		state.errors = ['disabled']
		state.providers.push({ id: 'other', order: 10, routeAliases: [], module: '/apps/other/js/teams.mjs' })
		loadModule.mockRejectedValueOnce(new Error('missing module'))
			.mockResolvedValueOnce({ apiVersion: 2, default: provider })
		const registry = await import('./registry.ts')
		await registry.initializeTeamApps()
		expect(registry.getTeamAppRegistrationErrors()).toEqual(['disabled', 'example'])
		expect(registry.getTeamAppProviders().map(({ id }) => id)).toEqual(['other'])
	})

	it('rejects duplicate registrations', async () => {
		state.providers.push(state.providers[0]!)
		const registry = await import('./registry.ts')
		await registry.initializeTeamApps()
		expect(registry.getTeamAppProviders()).toEqual([])
		expect(registry.getTeamAppRegistrationErrors()).toEqual(['example'])
	})

	it('does not depend on deferred script timing and initializes once', async () => {
		vi.spyOn(document, 'readyState', 'get').mockReturnValue('interactive')
		const registry = await import('./registry.ts')
		await registry.initializeTeamApps()
		await registry.initializeTeamApps()
		expect(loadModule).toHaveBeenCalledTimes(1)
		expect(registry.getTeamAppProviders()).toMatchObject([{ id: 'example', order: 5, mount: provider.mount }])
	})

	it.each([
		{ apiVersion: 1, default: provider },
		{ apiVersion: 2, default: null },
		{ apiVersion: 2, default: { mount: undefined } },
		{ apiVersion: 2, default: { mount: 'invalid' } },
	])('isolates an invalid or incompatible module: %j', async (module) => {
		loadModule.mockResolvedValue(module)
		const registry = await import('./registry.ts')
		await registry.initializeTeamApps()
		expect(registry.getTeamAppProviders()).toEqual([])
		expect(registry.getTeamAppRegistrationErrors()).toEqual(['example'])
	})

	it.each(['https://external.example/teams.mjs', '/apps/example/teams.php'])('rejects invalid module URLs before importing: %s', async (module) => {
		state.providers[0]!.module = module
		const registry = await import('./registry.ts')
		await registry.initializeTeamApps()
		expect(loadModule).not.toHaveBeenCalled()
		expect(registry.getTeamAppRegistrationErrors()).toEqual(['example'])
	})

	it('accepts PHP-only providers without importing a browser module', async () => {
		state.providers[0]!.module = null
		const registry = await import('./registry.ts')
		await registry.initializeTeamApps()
		expect(loadModule).not.toHaveBeenCalled()
		expect(registry.getTeamAppProviders()).toMatchObject([{ id: 'example', order: 5 }])
		expect(registry.getTeamAppProviders()[0]!.mount).toBeUndefined()
		const context = { teamId: 'team', teamName: 'Team', isAdmin: false, folder: null, resources: [], signal: new AbortController().signal, refresh: vi.fn(), open: vi.fn(), getNavigationEntryIds: vi.fn(), setNavigationEntryIds: vi.fn(), chooseResources: vi.fn(), prompt: vi.fn(), confirm: vi.fn() }
		await expect(registry.getTeamAppProviders()[0]!.load(context)).resolves.toEqual({ entries: [] })
		expect(get).toHaveBeenCalledWith(expect.stringContaining('/teams/team/apps/example/navigation'), { signal: context.signal })
	})

	it('validates PHP-owned compatibility routes', async () => {
		state.providers[0]!.routeAliases = [{ path: '/home', entryParam: 'id' }]
		const registry = await import('./registry.ts')
		await registry.initializeTeamApps()
		expect(registry.getTeamAppRegistrationErrors()).toEqual(['example'])
	})
})
