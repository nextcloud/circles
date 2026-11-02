/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import TeamAppIcon from './TeamAppIcon.vue'
import TeamNavigation from './TeamNavigation.vue'

vi.mock('@nextcloud/dialogs', () => ({ showError: vi.fn() }))
vi.mock('vue-router', () => ({
	useRoute: () => ({ params: { teamId: 'team' }, name: 'team-home' }),
	useRouter: () => ({ push: vi.fn() }),
}))
vi.mock('vuex', () => ({ useStore: () => ({ getters: { getCircle: () => ({ isMember: true }) } }) }))
vi.mock('../store.ts', () => ({ useTeamsStore: () => ({ getTeam: () => null }) }))
vi.mock('../resourcesStore.ts', () => ({ useTeamResourcesStore: () => ({ forTeam: () => ({ tabOrder: [] }) }) }))
vi.mock('../composables/useTeamActions.ts', () => ({
	useTeamActions: () => ({ canManage: false, isTeamAdmin: false }),
	canCreateTeamFolder: () => false,
}))
vi.mock('../integrations/store.ts', () => ({
	useTeamAppsStore: () => ({
		entries: () => [{
			id: 'text-7',
			label: 'Handbook',
			icon: '\u{1F4DA}',
			actions: [{ id: 'rename', label: 'Rename', icon: 'M0 0h24v24z' }],
		}],
		actions: () => [{ id: 'create', label: 'New', icon: '\u{1F195}' }],
		errors: () => [],
		busy: false,
	}),
}))
vi.mock('./TeamHeader.vue', () => ({ default: { template: '<div />' } }))
vi.mock('./TeamSettingsDialog.vue', () => ({ default: { template: '<div />' } }))

describe('Teams navigation icons', () => {
	it('uses the shared renderer for entries and every app action menu', () => {
		const wrapper = mount(TeamNavigation, {
			global: {
				stubs: {
					NcAppNavigation: { template: '<nav><slot name="list" /><slot name="footer" /></nav>' },
					NcAppNavigationCaption: { template: '<div><slot name="actions" /></div>' },
					NcAppNavigationItem: {
						props: ['name'],
						template: '<section :aria-label="name"><slot name="icon" /><slot name="actions" /></section>',
					},
					NcActionButton: { template: '<button><slot name="icon" /><slot /></button>' },
					NcActions: { template: '<div><slot /></div>' },
					NcActionSeparator: true,
					NcIconSvgWrapper: true,
				},
			},
		})
		const entry = wrapper.get('section[aria-label="Handbook"]')
		const entryIcons = wrapper.findAllComponents(TeamAppIcon).filter((icon) => entry.element.contains(icon.element))
		expect(entryIcons.map((icon) => icon.props('icon')))
			.toEqual(['\u{1F4DA}', 'M0 0h24v24z'])
		expect(entry.get('.team-app-icon__emoji').text()).toBe('\u{1F4DA}')
		expect(wrapper.findAllComponents(TeamAppIcon).filter((icon) => icon.props('icon') === '\u{1F195}'))
			.toHaveLength(2)
		wrapper.unmount()
	})
})
