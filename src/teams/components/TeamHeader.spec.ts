/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type Circle from '../team-page/models/circle.ts'

import { shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import TeamHeader from './TeamHeader.vue'
import { useTeamsStore } from '../store.ts'

type LoadState = (app: string, key: string, fallback?: unknown) => unknown

const loadState = vi.hoisted(() => vi.fn<LoadState>())

vi.mock('@nextcloud/initial-state', () => ({ loadState }))
vi.mock('@nextcloud/dialogs', () => ({ showError: vi.fn() }))
vi.mock('vue-router', () => ({
	useRouter: () => ({ push: vi.fn() }),
}))
vi.mock('vuex', () => ({
	useStore: () => ({ getters: { getCircle: () => undefined } }),
}))
vi.mock('../api.ts', () => ({ fetchTeams: vi.fn(async () => []) }))

/**
 * Mount the header with the switcher panel rendered inline.
 *
 * @param canCreateTeam - value served for the `canCreateTeam` initial state
 */
function mountHeader(canCreateTeam: boolean) {
	loadState.mockImplementation((app, key, fallback) => {
		if (app === 'circles' && key === 'canCreateTeam') {
			return canCreateTeam
		}
		return fallback
	})
	const pinia = createPinia()
	setActivePinia(pinia)

	const wrapper = shallowMount(TeamHeader, {
		props: {
			circle: { id: 'team-1', displayName: 'Marketing' } as Circle,
		},
		global: {
			plugins: [pinia],
			stubs: {
				NcPopover: {
					template: '<div><slot name="trigger" /><slot /></div>',
				},
				NcButton: {
					emits: ['click'],
					template: '<button class="nc-button-stub" '
						+ '@click="$emit(\'click\')"><slot /></button>',
				},
			},
		},
	})

	return { wrapper, store: useTeamsStore() }
}

/**
 * Find the switcher's "New team" button, if rendered.
 *
 * @param wrapper - The mounted header
 */
function findNewTeamButton(wrapper: ReturnType<typeof mountHeader>['wrapper']) {
	return wrapper.findAll('.nc-button-stub')
		.find((button) => button.text() === 'New team')
}

describe('TeamHeader team creation', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it('opens the creation wizard from the switcher when allowed', async () => {
		const { wrapper, store } = mountHeader(true)
		const button = findNewTeamButton(wrapper)

		expect(button).toBeDefined()
		await button!.trigger('click')
		expect(store.createWizardOpen).toBe(true)
	})

	it('hides the switcher "New team" button when not allowed', () => {
		const { wrapper } = mountHeader(false)

		expect(findNewTeamButton(wrapper)).toBeUndefined()
	})
})
