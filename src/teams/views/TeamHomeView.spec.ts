/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TeamResources } from '../resourcesStore.ts'

import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { reactive } from 'vue'
import TeamHomeView from './TeamHomeView.vue'

const navigationStore = vi.hoisted(() => vi.fn(() => {
	throw new Error('Shared resources must not depend on app navigation')
}))
const state = reactive<TeamResources>({
	folder: null,
	folderChecked: true,
	folderError: false,
	resources: [],
	resourcesChecked: true,
	resourcesError: false,
	tabOrder: [],
	orderChecked: true,
	orderError: false,
})

vi.mock('vuex', () => ({ useStore: () => ({ getters: { getCircle: () => ({ isMember: true }) } }) }))
vi.mock('../resourcesStore.ts', () => ({ useTeamResourcesStore: () => ({ forTeam: () => state }) }))
vi.mock('../integrations/store.ts', () => ({ useTeamAppsStore: navigationStore }))

function mountView() {
	return mount(TeamHomeView, {
		props: { teamId: 'team' },
		global: {
			stubs: {
				ListItem: { props: ['name', 'href'], template: '<a :href="href">{{ name }}</a>' },
				ContentHeading: { template: '<h2><slot /></h2>' },
				NcLoadingIcon: { template: '<span data-testid="loading" />' },
				NcEmptyContent: { props: ['name'], template: '<p>{{ name }}</p>' },
				NcIconSvgWrapper: true,
			},
		},
	})
}

describe('Shared with the team', () => {
	beforeEach(() => {
		navigationStore.mockClear()
		state.folder = { id: 42, mountPoint: '/Team', quota: null }
		state.folderChecked = true
		state.folderError = false
		state.resourcesChecked = true
		state.resourcesError = false
		state.resources = [
			{ id: '42', label: 'Board', url: '/board/42', provider: { id: 'deck', name: 'Deck' } },
			{ id: '7', label: 'Handbook', url: '/collectives/7', provider: { id: 'collectives', name: 'Collectives' } },
			{ id: '42', label: 'Team folder', url: '/folders/42', provider: { id: 'groupfolders', name: 'Folders' } },
			{ id: '43', label: 'Other group folder', url: '/folders/43', provider: { id: 'groupfolders', name: 'Folders' } },
		]
	})

	it('lists shared app resources without loading or waiting for navigation providers', () => {
		const wrapper = mountView()
		expect(wrapper.findAll('a').map((link) => link.text())).toEqual(['Board', 'Handbook', 'Other group folder'])
		expect(navigationStore).not.toHaveBeenCalled()
		wrapper.unmount()
	})

	it('excludes only the team folder, retaining other group folders and matching IDs from other apps', () => {
		const wrapper = mountView()
		expect(wrapper.find('a[href="/folders/42"]').exists()).toBe(false)
		expect(wrapper.find('a[href="/folders/43"]').exists()).toBe(true)
		expect(wrapper.find('a[href="/board/42"]').exists()).toBe(true)
		expect(wrapper.text()).toContain('Group folders')
		wrapper.unmount()
	})

	it('keeps all group folders when the team has no dedicated folder', () => {
		state.folder = null
		const wrapper = mountView()
		expect(wrapper.findAll('a')).toHaveLength(4)
		wrapper.unmount()
	})

	it.each(['resourcesChecked', 'folderChecked'] as const)('waits for %s before listing resources', (flag) => {
		state[flag] = false
		const wrapper = mountView()
		expect(wrapper.find('[data-testid="loading"]').exists()).toBe(true)
		expect(wrapper.findAll('a')).toHaveLength(0)
		wrapper.unmount()
	})

	it('reports a resource loading failure', () => {
		state.resourcesChecked = false
		state.resourcesError = true
		const wrapper = mountView()
		expect(wrapper.text()).toContain('Could not load the team resources')
		expect(wrapper.findAll('a')).toHaveLength(0)
		wrapper.unmount()
	})
})
