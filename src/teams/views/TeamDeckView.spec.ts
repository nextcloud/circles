/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { flushPromises, shallowMount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { afterEach, beforeAll, beforeEach, describe, expect, it, vi } from 'vitest'
import { defineComponent, defineCustomElement, h } from 'vue'
import { createMemoryHistory, createRouter, RouterView, useRoute } from 'vue-router'
import TeamDeckView from './TeamDeckView.vue'
import { teamViewKey } from '../deck.ts'
import { useTeamResourcesStore } from '../resourcesStore.ts'

const loadState = vi.hoisted(() => vi.fn())
const showError = vi.hoisted(() => vi.fn())
const logger = vi.hoisted(() => ({ error: vi.fn(), warn: vi.fn() }))

vi.mock('@nextcloud/initial-state', () => ({ loadState }))
vi.mock('@nextcloud/dialogs', () => ({ showError }))
vi.mock('@nextcloud/l10n', () => ({ t: (_: string, text: string) => text }))
vi.mock('../../logger.ts', () => ({ logger }))
vi.mock('../api.ts', () => ({
	getTeamFolder: vi.fn(),
	upgradeTeamFolder: vi.fn(),
	fetchTeamPages: vi.fn(),
	fetchTeamResources: vi.fn(),
	fetchTeamDeckBoards: vi.fn(),
	fetchTabOrder: vi.fn(),
	saveTabOrder: vi.fn(),
}))

const wrappers: ReturnType<typeof shallowMount>[] = []

beforeAll(() => {
	if (!customElements.get('nextcloud-deck-board')) {
		customElements.define('nextcloud-deck-board', defineCustomElement(defineComponent({
			props: { boardId: String, cardId: String },
			setup(props) {
				return () => h('div', { 'data-board-id': props.boardId, 'data-card-id': props.cardId })
			},
		}), { shadowRoot: false }))
	}
})

beforeEach(() => {
	vi.clearAllMocks()
	loadState.mockReturnValue(true)
})

afterEach(() => {
	for (const wrapper of wrappers.splice(0)) {
		wrapper.unmount()
	}
	vi.restoreAllMocks()
	vi.useRealTimers()
	document.body.replaceChildren()
})

async function mountView(options: { available?: boolean, loaded?: boolean, shared?: boolean, boardId?: string, cardId?: string, routed?: boolean } = {}) {
	loadState.mockReturnValue(options.available ?? true)
	const pinia = createPinia()
	const state = useTeamResourcesStore(pinia).slot('team-1')
	state.resourcesChecked = options.loaded ?? true
	state.boardsChecked = options.loaded ?? true
	state.resources = options.shared === false
		? []
		: [7, 8].map((id) => ({
				id,
				label: `Board ${id}`,
				url: `/apps/deck/board/${id}`,
				provider: { id: 'deck', name: 'Deck' },
			}))
	const router = createRouter({
		history: createMemoryHistory(),
		routes: [
			{ name: 'team-deck', path: '/team/:teamId/deck/:boardId', component: TeamDeckView, props: true },
			{ name: 'team-deck-card', path: '/team/:teamId/deck/:boardId/card/:cardId', component: TeamDeckView, props: true },
		],
	})
	await router.push('/team/team-1/deck/7')
	const host = defineComponent({
		setup() {
			const route = useRoute()
			return () => h(RouterView, { key: teamViewKey(route) })
		},
	})
	const wrapper = shallowMount(options.routed ? host : TeamDeckView, {
		attachTo: document.body,
		props: options.routed ? {} : { teamId: 'team-1', boardId: options.boardId ?? '7', cardId: options.cardId },
		global: {
			plugins: [pinia, router],
			stubs: {
				RouterView: false,
				TeamDeckView: false,
				NcEmptyContent: {
					props: ['name', 'description'],
					template: '<div class="empty">{{ name }} {{ description }}<slot name="action" /></div>',
				},
				NcButton: { template: '<button><slot /></button>' },
			},
		},
	})
	wrappers.push(wrapper)
	await flushPromises()
	return { wrapper, router, state }
}

describe('TeamDeckView', () => {
	it('embeds only a board confirmed in the current team resources', async () => {
		const { wrapper } = await mountView()
		expect(wrapper.get('nextcloud-deck-board').attributes('board-id')).toBe('7')
		expect(wrapper.get('nextcloud-deck-board').attributes('card-id')).toBeUndefined()
	})

	it('waits for the team resources before mounting Deck', async () => {
		const { wrapper, state } = await mountView({ loaded: false })
		expect(wrapper.find('nextcloud-deck-board').exists()).toBe(false)
		state.resourcesChecked = true
		await flushPromises()
		expect(wrapper.find('nextcloud-deck-board').exists()).toBe(true)
	})

	it('does not embed a board outside the current team', async () => {
		const { wrapper } = await mountView({ shared: false })
		expect(wrapper.find('nextcloud-deck-board').exists()).toBe(false)
		expect(wrapper.text()).toContain('Board not available in this team')
	})

	it('reports missing support without redirecting to the Deck app', async () => {
		const { wrapper, router } = await mountView({ available: false })
		expect(wrapper.text()).toContain('Deck integration unavailable')
		expect(wrapper.find('nextcloud-deck-board').exists()).toBe(false)
		expect(router.currentRoute.value.path).toBe('/team/team-1/deck/7')
	})

	it('rejects invalid identifiers visibly', async () => {
		const { wrapper } = await mountView({ cardId: '0' })
		expect(wrapper.text()).toContain('Invalid Deck resource')
		expect(wrapper.find('nextcloud-deck-board').exists()).toBe(false)
		expect(logger.error).toHaveBeenCalled()
	})

	it('updates the card attribute without remounting the element', async () => {
		const { wrapper } = await mountView()
		const element = wrapper.get('nextcloud-deck-board').element
		await wrapper.setProps({ cardId: '9' })
		await flushPromises()
		expect(wrapper.get('nextcloud-deck-board').element).toBe(element)
		expect(wrapper.get('nextcloud-deck-board').attributes('card-id')).toBe('9')
		expect(element.querySelector('div')!.getAttribute('data-card-id')).toBe('9')
		await wrapper.setProps({ cardId: undefined })
		await flushPromises()
		expect(wrapper.get('nextcloud-deck-board').element).toBe(element)
		expect(wrapper.get('nextcloud-deck-board').attributes('card-id')).toBeUndefined()
		expect(element.querySelector('div')!.getAttribute('data-card-id')).toBeNull()
	})

	it('preserves the board on actual route transitions and browser history', async () => {
		const { wrapper, router } = await mountView({ routed: true })
		const element = wrapper.get('nextcloud-deck-board').element
		await router.push('/team/team-1/deck/7/card/9')
		await flushPromises()
		expect(wrapper.get('nextcloud-deck-board').element).toBe(element)
		expect(element.getAttribute('card-id')).toBe('9')
		router.back()
		await flushPromises()
		expect(wrapper.get('nextcloud-deck-board').element).toBe(element)
		expect(element.getAttribute('card-id')).toBeNull()
		router.forward()
		await flushPromises()
		expect(element.getAttribute('card-id')).toBe('9')
		await router.push('/team/team-1/deck/8')
		await flushPromises()
		expect(wrapper.get('nextcloud-deck-board').element).not.toBe(element)
		expect(element.isConnected).toBe(false)
	})

	it('reports failed resource loading instead of waiting forever', async () => {
		const { wrapper, state } = await mountView({ loaded: false })
		state.boardsChecked = true
		await flushPromises()
		expect(wrapper.text()).toContain('Team resources unavailable')
		expect(wrapper.find('nextcloud-deck-board').exists()).toBe(false)
	})

	it('removes the loading indicator when Deck signals readiness', async () => {
		const { wrapper } = await mountView()
		expect(wrapper.find('.team-deck__loading').exists()).toBe(true)
		wrapper.get('nextcloud-deck-board').element.dispatchEvent(new CustomEvent('deck-ready', { detail: { boardId: 7 } }))
		await flushPromises()
		expect(wrapper.find('.team-deck__loading').exists()).toBe(false)
	})

	it('uses host history for card navigation and ignores route echoes', async () => {
		const { wrapper, router } = await mountView()
		const element = wrapper.get('nextcloud-deck-board').element
		element.dispatchEvent(new CustomEvent('deck-navigation', { detail: { boardId: 7, cardId: 9 } }))
		await flushPromises()
		expect(router.currentRoute.value.path).toBe('/team/team-1/deck/7/card/9')
		const push = vi.spyOn(router, 'push')
		element.dispatchEvent(new CustomEvent('deck-navigation', { detail: { boardId: 7, cardId: 9 } }))
		await flushPromises()
		expect(push).not.toHaveBeenCalled()
	})

	it('allows cross-board navigation only within the same team resources', async () => {
		const { wrapper, router } = await mountView()
		const element = wrapper.get('nextcloud-deck-board').element
		element.dispatchEvent(new CustomEvent('deck-navigation', { detail: { boardId: 8, cardId: null } }))
		await flushPromises()
		expect(router.currentRoute.value.path).toBe('/team/team-1/deck/8')
		element.dispatchEvent(new CustomEvent('deck-navigation', { detail: { boardId: 99, cardId: 9 } }))
		await flushPromises()
		expect(router.currentRoute.value.path).toBe('/team/team-1/deck/8')
		expect(showError).toHaveBeenCalledWith('This board is not shared with the current team')
	})

	it('reports malformed navigation events', async () => {
		const { wrapper } = await mountView()
		wrapper.get('nextcloud-deck-board').element.dispatchEvent(new CustomEvent('deck-navigation', { detail: { boardId: -1, cardId: null } }))
		expect(logger.error).toHaveBeenCalled()
		expect(showError).toHaveBeenCalledWith('Could not open the Deck resource')
	})

	it('reports loading errors and can retry with a fresh element', async () => {
		const { wrapper } = await mountView()
		const element = wrapper.get('nextcloud-deck-board').element
		element.dispatchEvent(new CustomEvent('deck-error', { detail: { status: 403 } }))
		await flushPromises()
		expect(wrapper.text()).toContain('Deck board unavailable')
		expect(wrapper.find('nextcloud-deck-board').exists()).toBe(false)
		await wrapper.get('button').trigger('click')
		await flushPromises()
		expect(wrapper.get('nextcloud-deck-board').element).not.toBe(element)
	})

	it('reports a registration timeout rather than loading forever', async () => {
		vi.useFakeTimers()
		vi.spyOn(customElements, 'whenDefined').mockReturnValue(new Promise(() => {}))
		const { wrapper } = await mountView()
		await vi.advanceTimersByTimeAsync(9999)
		expect(wrapper.text()).not.toContain('Deck board unavailable')
		await vi.advanceTimersByTimeAsync(1)
		expect(wrapper.text()).toContain('Deck board unavailable')
		expect(logger.error).toHaveBeenCalled()
	})
})
