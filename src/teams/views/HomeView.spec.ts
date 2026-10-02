/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import HomeView from './HomeView.vue'

type LoadState = (app: string, key: string, fallback?: unknown) => unknown

const loadState = vi.hoisted(() => vi.fn<LoadState>())

vi.mock('@nextcloud/initial-state', () => ({ loadState }))
vi.mock('../api.ts', () => ({ fetchTeams: vi.fn(async () => []) }))

const EMPTY_DESCRIPTION = 'Create your first team to start collaborating.'

/**
 * Mount the home view without teams, so the empty state is shown.
 *
 * @param canCreateTeam - value served for the `canCreateTeam` initial state
 */
function mountView(canCreateTeam: boolean) {
	loadState.mockImplementation((app, key, fallback) => {
		if (app === 'circles' && key === 'canCreateTeam') {
			return canCreateTeam
		}
		return fallback
	})
	const pinia = createPinia()
	setActivePinia(pinia)

	return shallowMount(HomeView, {
		global: {
			plugins: [pinia],
			stubs: {
				NcButton: false,
				NcEmptyContent: false,
			},
		},
	})
}

describe('HomeView team creation', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it('offers creating teams when allowed', () => {
		const wrapper = mountView(true)
		const buttons = wrapper.findAll('button').map((button) => button.text())

		expect(buttons).toContain('New team')
		expect(buttons).toContain('Create your first team')
		expect(wrapper.text()).toContain(EMPTY_DESCRIPTION)
	})

	it('hides every creation entry point when not allowed', () => {
		const wrapper = mountView(false)
		const buttons = wrapper.findAll('button').map((button) => button.text())

		expect(buttons).not.toContain('New team')
		expect(buttons).not.toContain('Create your first team')
		expect(wrapper.text()).not.toContain(EMPTY_DESCRIPTION)
		expect(wrapper.find('.empty-content__action').exists()).toBe(false)
		expect(wrapper.text()).toContain('No teams yet')
	})
})
