import type * as L10n from '@nextcloud/l10n'
import type { Team } from '../types.ts'

import { flushPromises, shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { nextTick, ref } from 'vue'
import TeamCard from '../components/TeamCard.vue'
import HomeView from './HomeView.vue'
import * as api from '../api.ts'
import { useTeamsStore } from '../store.ts'

vi.mock('../api.ts', () => ({ reorderFavoriteTeams: vi.fn(), setTeamFavorite: vi.fn(), fetchTeams: vi.fn() }))
vi.mock('../../logger.ts', () => ({ logger: { error: vi.fn() } }))
vi.mock('@nextcloud/dialogs', () => ({ showError: vi.fn() }))
vi.mock('@nextcloud/vue/composables/useIsDarkTheme', () => ({ useIsDarkTheme: () => ref(false) }))
vi.mock('@nextcloud/l10n', async (importOriginal) => ({
	...await importOriginal<typeof L10n>(),
	t: (_app: string, text: string) => text,
}))

function mountHome() {
	const pinia = createPinia()
	setActivePinia(pinia)
	const store = useTeamsStore()
	store.teams = ['A', 'B'].map((id, position): Team => ({
		id,
		displayName: id,
		description: '',
		isFavorite: true,
		favoritePosition: position,
		memberCount: 0,
		myRole: 'member',
		members: [],
		resources: [],
	}))
	const wrapper = shallowMount(HomeView, { global: { plugins: [pinia] } })
	return { wrapper, store }
}

function startDrag(wrapper: ReturnType<typeof mountHome>['wrapper']) {
	const cards = wrapper.findAllComponents(TeamCard)
	cards[0]!.vm.$emit('dragStart', cards[0]!.props('team'), { dataTransfer: null, preventDefault: vi.fn() })
	cards[1]!.vm.$emit('dragOver', cards[1]!.props('team'), {
		preventDefault: vi.fn(),
		dataTransfer: null,
		clientY: 90,
		currentTarget: { getBoundingClientRect: () => ({ top: 0, height: 100 }) },
	})
}

describe('favorite ordering in HomeView', () => {
	beforeEach(() => vi.resetAllMocks())

	it('discards a cancelled preview without saving or mutating the store', async () => {
		const { wrapper, store } = mountHome()
		startDrag(wrapper)
		await nextTick()
		expect(wrapper.findAllComponents(TeamCard).map((card) => card.props('team').id)).toEqual(['B', 'A'])
		expect(store.favoriteTeams.map((team) => team.id)).toEqual(['A', 'B'])
		wrapper.findAllComponents(TeamCard)[0]!.vm.$emit('dragEnd')
		await nextTick()
		expect(wrapper.findAllComponents(TeamCard).map((card) => card.props('team').id)).toEqual(['A', 'B'])
		expect(api.reorderFavoriteTeams).not.toHaveBeenCalled()
		wrapper.unmount()
	})

	it('persists an accepted drop exactly once', async () => {
		vi.mocked(api.reorderFavoriteTeams).mockResolvedValue(['B', 'A'])
		const { wrapper, store } = mountHome()
		startDrag(wrapper)
		await nextTick()
		wrapper.findAllComponents(TeamCard)[0]!.element.parentElement!.dispatchEvent(new Event('drop', { bubbles: true, cancelable: true }))
		wrapper.findAllComponents(TeamCard)[0]!.vm.$emit('dragEnd')
		await flushPromises()
		expect(api.reorderFavoriteTeams).toHaveBeenCalledExactlyOnceWith(['B', 'A'], ['A', 'B'])
		expect(store.favoriteTeams.map((team) => team.id)).toEqual(['B', 'A'])
		wrapper.unmount()
	})

	it('ignores external drops without an active team drag', async () => {
		const { wrapper } = mountHome()
		wrapper.findAllComponents(TeamCard)[0]!.element.parentElement!.dispatchEvent(new Event('drop', { bubbles: true, cancelable: true }))
		await flushPromises()
		expect(api.reorderFavoriteTeams).not.toHaveBeenCalled()
		wrapper.unmount()
	})
})
