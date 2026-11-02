/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import App from './App.vue'
import { useTeamsStore } from './store.ts'

type LoadState = (app: string, key: string, fallback?: unknown) => unknown

const loadState = vi.hoisted(() => vi.fn<LoadState>())

vi.mock('@nextcloud/initial-state', () => ({ loadState }))
vi.mock('./api.ts', () => ({ fetchTeams: vi.fn(async () => []) }))
vi.mock('./components/TeamNavigation.vue', () => ({
	default: { name: 'TeamNavigation', template: '<nav />' },
}))
vi.mock('./components/TeamCreationWizard.vue', () => ({
	default: {
		name: 'TeamCreationWizard',
		template: '<div class="wizard-stub" />',
	},
}))

/**
 * Mount the app shell with the creation wizard flagged as open.
 *
 * @param canCreateTeam - value served for the `canCreateTeam` initial state
 */
function mountApp(canCreateTeam: boolean) {
	loadState.mockImplementation((app, key, fallback) => {
		if (app === 'circles' && key === 'canCreateTeam') {
			return canCreateTeam
		}
		return fallback
	})
	const pinia = createPinia()
	setActivePinia(pinia)
	useTeamsStore().createWizardOpen = true

	return shallowMount(App, {
		global: {
			plugins: [pinia],
			stubs: {
				NcContent: { template: '<div><slot /></div>' },
				RouterView: true,
			},
		},
	})
}

describe('App team creation wizard', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it('mounts the creation wizard when allowed', () => {
		const wrapper = mountApp(true)

		const wizard = wrapper.findComponent({ name: 'TeamCreationWizard' })
		expect(wizard.exists()).toBe(true)
	})

	it('never mounts the creation wizard when not allowed', () => {
		const wrapper = mountApp(false)

		const wizard = wrapper.findComponent({ name: 'TeamCreationWizard' })
		expect(wizard.exists()).toBe(false)
	})
})
