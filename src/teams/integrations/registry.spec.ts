/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'

const state = vi.hoisted(() => ({ providers: [{ id: 'example', order: 5 }], errors: [] as string[] }))
vi.mock('@nextcloud/initial-state', () => ({ loadState: () => state }))

describe('Teams app registration', () => {
	beforeEach(() => {
		vi.resetModules()
		state.providers = [{ id: 'example', order: 5 }]
		state.errors = []
	})

	it('registers PHP navigation providers without importing browser modules', async () => {
		const registry = await import('./registry.ts')
		registry.initializeTeamApps()
		registry.initializeTeamApps()
		expect(registry.getTeamAppProviders()).toEqual([{ id: 'example', order: 5 }])
		expect(registry.getTeamAppRegistrationErrors()).toEqual([])
	})

	it('preserves PHP errors and sorts providers', async () => {
		state.errors = ['disabled']
		state.providers.push({ id: 'other', order: 1 })
		const registry = await import('./registry.ts')
		registry.initializeTeamApps()
		expect(registry.getTeamAppProviders().map(({ id }) => id)).toEqual(['other', 'example'])
		expect(registry.getTeamAppRegistrationErrors()).toEqual(['disabled'])
	})

	it('rejects duplicate registrations', async () => {
		state.providers.push(state.providers[0]!)
		const registry = await import('./registry.ts')
		registry.initializeTeamApps()
		expect(registry.getTeamAppProviders()).toEqual([])
		expect(registry.getTeamAppRegistrationErrors()).toEqual(['example'])
	})

	it.each([
		{ id: '../invalid', order: 1 },
		{ id: 'example', order: Infinity },
	])('isolates invalid descriptors: %j', async (descriptor) => {
		state.providers = [descriptor, { id: 'other', order: 2 }]
		const registry = await import('./registry.ts')
		registry.initializeTeamApps()
		expect(registry.getTeamAppRegistrationErrors()).toEqual([descriptor.id])
		expect(registry.getTeamAppProviders().map(({ id }) => id)).toEqual(['other'])
	})
})
