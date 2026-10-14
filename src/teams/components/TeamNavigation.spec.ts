/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import TeamNavigation from './TeamNavigation.vue'
import { useTeamResourcesStore } from '../resourcesStore.ts'

vi.mock('@nextcloud/dialogs', () => ({ showConfirmation: vi.fn(), showError: vi.fn(), showSuccess: vi.fn() }))
vi.mock(import('@nextcloud/l10n'), async (importOriginal) => ({
	...await importOriginal(),
	t: (_: string, text: string) => text,
}))
vi.mock('vuex', () => ({ useStore: () => ({ getters: { getCircle: () => null } }) }))
vi.mock('../store.ts', () => ({ useTeamsStore: () => ({ getTeam: () => null }) }))
vi.mock('../composables/useTeamActions.ts', async () => {
	const { ref } = await import('vue')
	return { canCreateTeamFolder: () => false, useTeamActions: () => ({ canManage: ref(false), isTeamAdmin: ref(false) }) }
})
vi.mock('./TeamHeader.vue', () => ({ default: { render: () => null } }))
vi.mock('./TeamSettingsDialog.vue', () => ({ default: { render: () => null } }))
vi.mock('../api.ts', () => ({
	createCollective: vi.fn(),
	createDeckBoard: vi.fn(),
	createTeamPage: vi.fn(),
	deleteTeamPage: vi.fn(),
	renameTeamPage: vi.fn(),
	getTeamFolder: vi.fn(),
	upgradeTeamFolder: vi.fn(),
	fetchTeamPages: vi.fn(),
	fetchTeamResources: vi.fn(),
	fetchTeamDeckBoards: vi.fn(),
	fetchTabOrder: vi.fn(),
	saveTabOrder: vi.fn(),
}))

async function mountNavigation(path: string) {
	const pinia = createPinia()
	const state = useTeamResourcesStore(pinia).slot('team-1')
	state.boards = [{ id: 7, title: 'Team board', url: '/apps/deck/board/7' }]
	state.pages = [{ fileId: 5, title: 'Handbook', filePath: '/team/.pages/Handbook.md' }]
	const router = createRouter({
		history: createMemoryHistory(),
		routes: [
			{ name: 'team-folder', path: '/team/:teamId/folder', component: {} },
			{ name: 'team-home', path: '/team/:teamId/shared', component: {} },
			{ name: 'team-page', path: '/team/:teamId/page/:fileId', component: {} },
			{ name: 'team-deck', path: '/team/:teamId/deck/:boardId', component: {} },
			{ name: 'team-deck-card', path: '/team/:teamId/deck/:boardId/card/:cardId', component: {} },
		],
	})
	await router.push(path)
	return shallowMount(TeamNavigation, {
		global: {
			plugins: [pinia, router],
			stubs: {
				NcAppNavigation: { template: '<nav><slot name="list" /></nav>' },
				NcAppNavigationItem: {
					name: 'NcAppNavigationItem',
					props: ['name', 'to', 'href', 'active'],
					template: '<div />',
				},
			},
		},
	})
}

describe('embedded board navigation', () => {
	it.each(['/team/team-1/deck/7', '/team/team-1/deck/7/card/9'])('highlights the board for %s', async (path) => {
		const wrapper = await mountNavigation(path)
		const entries = wrapper.findAllComponents({ name: 'NcAppNavigationItem' })
		const board = entries.find((entry) => entry.props('name') === 'Team board')!
		expect(board.props('active')).toBe(true)
		expect(board.props('href')).toBeUndefined()
		expect(board.props('to')).toEqual({ name: 'team-deck', params: { teamId: 'team-1', boardId: '7' } })
		expect(entries.find((entry) => entry.props('name') === 'Shared with the team')!.props('active')).toBe(false)
		wrapper.unmount()
	})

	it('highlights shared resources for a personally shared board', async () => {
		const wrapper = await mountNavigation('/team/team-1/deck/8/card/9')
		const entries = wrapper.findAllComponents({ name: 'NcAppNavigationItem' })
		expect(entries.find((entry) => entry.props('name') === 'Shared with the team')!.props('active')).toBe(true)
		expect(entries.find((entry) => entry.props('name') === 'Team board')!.props('active')).toBe(false)
		wrapper.unmount()
	})

	it('keeps the existing folder and page route links', async () => {
		const wrapper = await mountNavigation('/team/team-1/page/5')
		const entries = wrapper.findAllComponents({ name: 'NcAppNavigationItem' })
		expect(entries.find((entry) => entry.props('name') === 'Handbook')!.props('to')).toEqual({
			name: 'team-page',
			params: { teamId: 'team-1', fileId: '5' },
		})
		expect(entries.find((entry) => entry.props('name') === 'Team folder')!.props('to')).toEqual({
			name: 'team-folder',
			params: { teamId: 'team-1' },
		})
		wrapper.unmount()
	})
})
