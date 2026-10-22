/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { describe, expect, it, vi } from 'vitest'
import TeamHomeView from './TeamHomeView.vue'
import { useTeamResourcesStore } from '../resourcesStore.ts'

vi.mock('vuex', () => ({ useStore: () => ({ getters: { getCircle: () => ({ isMember: true }) } }) }))
vi.mock('../api.ts', () => ({
	getTeamFolder: vi.fn(),
	upgradeTeamFolder: vi.fn(),
	fetchTeamPages: vi.fn(),
	fetchTeamResources: vi.fn(),
	fetchTeamDeckBoards: vi.fn(),
	fetchTabOrder: vi.fn(),
	saveTabOrder: vi.fn(),
}))

describe('shared team resource links', () => {
	it('uses the same embedded resource route for different apps', () => {
		const pinia = createPinia()
		const state = useTeamResourcesStore(pinia).slot('team-1')
		state.resourcesChecked = true
		state.boardsChecked = true
		state.folderChecked = true
		state.boards = [{ id: 7, title: 'Team board', url: '/apps/deck/board/7' }]
		state.resources = [
			{ id: 7, label: 'Team board', url: '/apps/deck/board/7', provider: { id: 'deck', name: 'Deck' } },
			{ id: 8, label: 'Shared board', url: '/apps/deck/board/8', provider: { id: 'deck', name: 'Deck' } },
			{ id: 9, label: 'Team chat', url: '/apps/spreed/room/team', provider: { id: 'talk', name: 'Talk' } },
		]
		const wrapper = shallowMount(TeamHomeView, {
			props: { teamId: 'team-1' },
			global: {
				plugins: [pinia],
				stubs: {
					NcListItem: {
						name: 'NcListItem',
						props: ['name', 'to', 'href'],
						template: '<li />',
					},
				},
			},
		})
		const entries = wrapper.findAllComponents({ name: 'NcListItem' })
		expect(entries).toHaveLength(2)
		const board = entries.find((entry) => entry.props('name') === 'Shared board')!
		expect(board.props('href')).toBeUndefined()
		expect(board.props('to')).toEqual({
			name: 'team-resource',
			params: { teamId: 'team-1', providerId: 'deck', resourceId: '8' },
		})
		const chat = entries.find((entry) => entry.props('name') === 'Team chat')!
		expect(chat.props('href')).toBeUndefined()
		expect(chat.props('to')).toEqual({
			name: 'team-resource',
			params: { teamId: 'team-1', providerId: 'talk', resourceId: '9' },
		})
		wrapper.unmount()
	})
})
