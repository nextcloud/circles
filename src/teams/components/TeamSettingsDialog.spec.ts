/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { VueWrapper } from '@vue/test-utils'

import { shallowMount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { createStore } from 'vuex'
import CircleConfigCheckboxesList from '../team-page/components/CircleDetails/CircleConfigs/CircleConfigCheckboxesList.vue'
import TeamMembersVisibilitySettings from './TeamMembersVisibilitySettings.vue'
import TeamSettingsDialog from './TeamSettingsDialog.vue'
import Circle from '../team-page/models/circle.ts'

vi.mock('@nextcloud/dialogs', () => ({
	FilePickerClosed: class extends Error {},
	FilePickerType: { Choose: 'choose' },
	getFilePickerBuilder: () => {
		const builder = {
			setMultiSelect: () => builder,
			setMimeTypeFilter: () => builder,
			setType: () => builder,
			allowDirectories: () => builder,
			build: () => ({}),
		}
		return builder
	},
	showError: vi.fn(),
	showSuccess: vi.fn(),
	showConfirmation: vi.fn(),
}))
vi.mock('@nextcloud/initial-state', () => ({
	loadState: (_app: string, _key: string, fallback: unknown) => fallback,
}))
vi.mock('vue-router', () => ({
	useRouter: () => ({ push: vi.fn() }),
}))

describe('Team settings privacy group', () => {
	let wrapper: VueWrapper

	afterEach(() => wrapper.unmount())

	it.each([
		{ level: 9, canEdit: true },
		{ level: 8, canEdit: true },
		{ level: 4, canEdit: false },
		{ level: 1, canEdit: false },
	])('places member visibility in Privacy for role $level', ({ level, canEdit }) => {
		wrapper = shallowMount(TeamSettingsDialog, {
			props: {
				open: false,
				circle: new Circle({
					id: 'team',
					owner: { id: 'owner', level: 9 },
					initiator: { id: 'actor', level },
					displayName: 'Team',
					description: '',
					settings: {},
				}),
			},
			global: {
				plugins: [createPinia(), createStore({})],
				stubs: {
					NcAppSettingsDialog: { template: '<div><slot /></div>' },
					NcAppSettingsSection: { template: '<section><slot /></section>' },
					ContentHeading: { template: '<h3><slot /></h3>' },
				},
			},
		})

		const privacy = wrapper.findAll('.team-settings-groups__group')
			.find((group) => group.get('h3').text() === 'Privacy')

		expect(privacy).toBeDefined()
		expect(Object.values(privacy!.findComponent(CircleConfigCheckboxesList).props('configs'))).toContain('Visible to everyone')
		expect(privacy!.findComponent(TeamMembersVisibilitySettings).exists()).toBe(canEdit)
		expect(wrapper.findAllComponents(TeamMembersVisibilitySettings)).toHaveLength(canEdit ? 1 : 0)
		expect(wrapper.findAll('h3').map((heading) => heading.text())).not.toContain('Member visibility')
	})
})
