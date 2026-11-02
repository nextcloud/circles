/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { flushPromises, shallowMount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import DashboardTeamsWidget from './DashboardTeamsWidget.vue'

type LoadState = (app: string, key: string, fallback?: unknown) => unknown

const loadState = vi.hoisted(() => vi.fn<LoadState>())

vi.mock('@nextcloud/initial-state', () => ({ loadState }))
vi.mock('@nextcloud/dialogs', () => ({ showError: vi.fn() }))
vi.mock('@nextcloud/axios', () => ({
	default: {
		get: vi.fn(async () => ({ data: { ocs: { data: [] } } })),
	},
}))

const EMPTY_DESCRIPTION = 'Join or create teams to see them here.'

/**
 * Mount the widget with an empty teams list, so the empty state is shown.
 *
 * @param canCreateTeam - value served for the `canCreateTeam` initial state
 */
async function mountWidget(canCreateTeam: boolean) {
	loadState.mockImplementation((app, key, fallback) => {
		if (app === 'circles' && key === 'canCreateTeam') {
			return canCreateTeam
		}
		return fallback
	})

	const wrapper = shallowMount(DashboardTeamsWidget, {
		global: {
			stubs: {
				NcButton: false,
				NcEmptyContent: false,
			},
		},
	})
	await flushPromises()

	return wrapper
}

describe('DashboardTeamsWidget team creation', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it('offers creating teams when allowed', async () => {
		const wrapper = await mountWidget(true)

		expect(wrapper.text()).toContain(EMPTY_DESCRIPTION)
		expect(wrapper.text()).toContain('Create your first team')
	})

	it('only shows the empty state title when not allowed', async () => {
		const wrapper = await mountWidget(false)

		expect(wrapper.text()).toContain('No teams found')
		expect(wrapper.text()).not.toContain(EMPTY_DESCRIPTION)
		expect(wrapper.text()).not.toContain('Create your first team')
		expect(wrapper.find('.empty-content__action').exists()).toBe(false)
	})
})
