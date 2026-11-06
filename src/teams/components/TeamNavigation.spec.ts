/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { VueWrapper } from '@vue/test-utils'
import type { Team } from '../types.ts'

import { shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { nextTick, reactive } from 'vue'
import { createStore } from 'vuex'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import TeamNavigation from './TeamNavigation.vue'
import { useTeamsStore } from '../store.ts'

vi.mock('@nextcloud/initial-state', () => ({
	loadState: (_app: string, _key: string, fallback: unknown) => fallback,
}))
vi.mock('@nextcloud/dialogs', () => ({
	showConfirmation: vi.fn(),
	showError: vi.fn(),
	showSuccess: vi.fn(),
}))
vi.mock('vue-router', () => ({
	useRoute: () => ({ name: 'team-members', params: { teamId: 'team' } }),
	useRouter: () => ({ push: vi.fn() }),
}))
vi.mock('./TeamHeader.vue', () => ({ default: { template: '<div />' } }))
vi.mock('./TeamSettingsDialog.vue', () => ({ default: { template: '<div />' } }))

describe('Team navigation member visibility', () => {
	let wrapper: VueWrapper
	let circle: { isMember: boolean, canViewMembers?: boolean }

	beforeEach(() => {
		const pinia = createPinia()
		setActivePinia(pinia)
		useTeamsStore().teams = [{ id: 'team', myRole: 'moderator' } as Team]
		circle = reactive({ isMember: true, canViewMembers: false })
		const store = createStore({
			getters: { getCircle: () => () => circle },
		})

		wrapper = shallowMount(TeamNavigation, {
			global: {
				plugins: [pinia, store],
				stubs: {
					NcAppNavigation: {
						template: '<nav><slot name="footer" /></nav>',
					},
				},
			},
		})
	})

	afterEach(() => wrapper.unmount())

	it.each([
		{ isMember: true, canViewMembers: true, visible: true },
		{ isMember: true, canViewMembers: false, visible: false },
		{ isMember: true, canViewMembers: undefined, visible: false },
		{ isMember: false, canViewMembers: true, visible: false },
	])('shows Members only with access: $isMember/$canViewMembers', async ({ isMember, canViewMembers, visible }) => {
		Object.assign(circle, { isMember, canViewMembers })
		await nextTick()

		const items = wrapper.findAllComponents(NcAppNavigationItem)
		expect(items.some((item) => item.props('name') === 'Members')).toBe(visible)
		expect(items.some((item) => item.props('name') === 'Team settings')).toBe(isMember)
	})

	it('updates the link when access changes without hiding team settings', async () => {
		circle.canViewMembers = true
		await nextTick()
		expect(wrapper.findAllComponents(NcAppNavigationItem).some((item) => item.props('name') === 'Members')).toBe(true)

		circle.canViewMembers = false
		await nextTick()

		const items = wrapper.findAllComponents(NcAppNavigationItem)
		expect(items.some((item) => item.props('name') === 'Members')).toBe(false)
		expect(items.some((item) => item.props('name') === 'Team settings')).toBe(true)
	})
})
