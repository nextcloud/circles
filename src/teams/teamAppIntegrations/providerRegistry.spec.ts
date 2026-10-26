/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'

const { state, loadState } = vi.hoisted(() => {
	const state = { providers: [{ id: 'example', name: 'Example' }], errors: [] as string[] }
	return { state, loadState: vi.fn(() => state) }
})
vi.mock('@nextcloud/initial-state', () => ({ loadState }))

describe('Teams app registration', () => {
	beforeEach(() => {
		vi.resetModules()
		state.providers = [{ id: 'example', name: 'Example' }]
		state.errors = []
		loadState.mockClear()
	})

	it('registers PHP navigation providers without importing browser modules', async () => {
		const registry = await import('./providerRegistry.ts')
		registry.initializeTeamApps()
		registry.initializeTeamApps()
		expect(loadState).toHaveBeenCalledOnce()
		expect(loadState).toHaveBeenCalledWith('circles', 'teamAppProviders')
		expect(registry.getTeamOwnedResourceProviders()).toEqual([{ id: 'example', name: 'Example' }])
		expect(registry.getTeamOwnedResourceProvider('example')).toEqual({ id: 'example', name: 'Example' })
		expect(registry.getTeamOwnedResourceProvider('missing')).toBeUndefined()
		expect(registry.getTeamAppRegistrationErrors()).toEqual([])
	})

	it('preserves PHP errors and sorts providers alphabetically', async () => {
		state.errors = ['disabled']
		state.providers.push({ id: 'other', name: 'Other' })
		const registry = await import('./providerRegistry.ts')
		registry.initializeTeamApps()
		expect(registry.getTeamOwnedResourceProviders().map(({ id }) => id)).toEqual(['example', 'other'])
		expect(registry.getTeamAppRegistrationErrors()).toEqual(['disabled'])
	})

	it('rejects duplicate registrations', async () => {
		state.providers.push(state.providers[0]!)
		const registry = await import('./providerRegistry.ts')
		registry.initializeTeamApps()
		expect(registry.getTeamOwnedResourceProviders()).toEqual([])
		expect(registry.getTeamAppRegistrationErrors()).toEqual(['example'])
	})

	it('prioritizes Files, Collectives and Deck, then sorts by app name', async () => {
		state.providers = [
			{ id: 'alpha', name: 'Zebra' },
			{ id: 'deck', name: 'Deck' },
			{ id: 'collectives', name: 'Collectives' },
			{ id: 'zebra', name: 'Alpha' },
			{ id: 'files', name: 'Files' },
		]
		const registry = await import('./providerRegistry.ts')
		registry.initializeTeamApps()
		expect(registry.getTeamOwnedResourceProviders().map(({ id }) => id)).toEqual(['files', 'collectives', 'deck', 'zebra', 'alpha'])
	})

	it.each([
		{ id: '../invalid', name: 'Invalid' },
		{ id: 'example', name: '   ' },
	])('isolates invalid descriptors: %j', async (descriptor) => {
		state.providers = [descriptor, { id: 'other', name: 'Other' }]
		const registry = await import('./providerRegistry.ts')
		registry.initializeTeamApps()
		expect(registry.getTeamAppRegistrationErrors()).toEqual([descriptor.id])
		expect(registry.getTeamOwnedResourceProviders().map(({ id }) => id)).toEqual(['other'])
	})
})
