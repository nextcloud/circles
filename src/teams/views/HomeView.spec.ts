/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type * as L10n from '@nextcloud/l10n'
import type { TeamsOverview } from '../api.ts'
import type { DiscoverableTeam, Team } from '../types.ts'

import { flushPromises, mount, shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { nextTick } from 'vue'
import DiscoverableTeamCard from '../components/DiscoverableTeamCard.vue'
import TeamCard from '../components/TeamCard.vue'
import HomeView from './HomeView.vue'
import * as api from '../api.ts'
import { useTeamsStore } from '../store.ts'

const fetchTeams = vi.hoisted(() => vi.fn<() => Promise<TeamsOverview>>())
const joinTeam = vi.hoisted(() => vi.fn<(teamId: string) => Promise<'joined' | 'requested'>>())
const setTeamFavorite = vi.hoisted(() => vi.fn<(teamId: string, isFavorite: boolean) => Promise<string[]>>())
const reorderFavoriteTeams = vi.hoisted(() => vi.fn<(teamIds: string[], expectedIds: string[]) => Promise<string[]>>())
const routerPush = vi.hoisted(() => vi.fn())
const showSuccess = vi.hoisted(() => vi.fn())
const showError = vi.hoisted(() => vi.fn())
const loggerError = vi.hoisted(() => vi.fn())

vi.mock('../api.ts', () => ({ fetchTeams, joinTeam, setTeamFavorite, reorderFavoriteTeams }))
vi.mock('../../logger.ts', () => ({ logger: { error: loggerError, warn: vi.fn() } }))
vi.mock('vue-router', () => ({ useRouter: () => ({ push: routerPush }) }))
vi.mock('@nextcloud/dialogs', () => ({ showError, showSuccess }))
vi.mock('@nextcloud/router', () => ({ imagePath: () => '/teams-illustration.svg' }))
vi.mock('@nextcloud/vue/composables/useIsDarkTheme', () => ({ useIsDarkTheme: () => false }))
vi.mock('@nextcloud/l10n', async (importOriginal) => ({
	...await importOriginal<typeof L10n>(),
	t: (_app: string, text: string, variables?: Record<string, string>) => {
		if (!variables) {
			return text
		}
		return text.replace(/\{(\w+)\}/g, (_, key: string) => variables[key] ?? `{${key}}`)
	},
	n: (_app: string, singular: string, plural: string, count: number) => (count === 1 ? singular : plural).replace('%n', String(count)),
}))
vi.mock('@nextcloud/vue/components/NcAvatar', () => ({
	default: { name: 'NcAvatar', template: '<div />' },
}))
vi.mock('@nextcloud/vue/components/NcButton', () => ({
	default: {
		name: 'NcButton',
		props: { disabled: Boolean, variant: String },
		emits: ['click'],
		template: '<button :disabled="disabled" @click="$emit(\'click\', $event)"><slot name="icon" /><slot /></button>',
	},
}))
vi.mock('@nextcloud/vue/components/NcEmptyContent', () => ({
	default: {
		name: 'NcEmptyContent',
		props: { name: String, description: String },
		template: '<div><strong>{{ name }}</strong><p>{{ description }}</p><slot name="icon" /><slot name="action" /></div>',
	},
}))
vi.mock('@nextcloud/vue/components/NcIconSvgWrapper', () => ({
	default: { name: 'NcIconSvgWrapper', template: '<span />' },
}))
vi.mock('@nextcloud/vue/components/NcLoadingIcon', () => ({
	default: { name: 'NcLoadingIcon', template: '<span data-testid="loading-icon" />' },
}))
vi.mock('@nextcloud/vue/components/NcTextField', () => ({
	default: {
		name: 'NcTextField',
		props: { modelValue: String, label: String },
		emits: ['update:modelValue'],
		template: '<div><span>{{ label }}</span><slot name="icon" /></div>',
	},
}))
vi.mock('../components/TeamCard.vue', () => ({
	default: {
		name: 'TeamCard',
		props: ['team', 'draggable', 'sortable', 'favoriteBusy', 'canMoveUp', 'canMoveDown'],
		emits: ['dragStart', 'dragOver', 'dragEnd', 'toggleFavorite', 'moveUp', 'moveDown'],
		template: '<div />',
	},
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
	})
}

describe('favorite ordering in HomeView', () => {
	beforeEach(() => vi.resetAllMocks())

	it('disables manual sorting while the team list is filtered', async () => {
		const { wrapper } = mountHome()
		wrapper.findComponent({ name: 'NcTextField' }).vm.$emit('update:modelValue', 'A')
		await nextTick()

		const card = wrapper.findComponent(TeamCard)
		expect(card.props('sortable')).toBe(false)
		expect(card.props('draggable')).toBe(false)
		wrapper.unmount()
	})

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

const teamA: DiscoverableTeam = {
	id: 'team-a',
	displayName: 'Alpha team',
	description: '',
	memberCount: 2,
	canJoin: true,
	pending: false,
}

const teamB: DiscoverableTeam = {
	id: 'team-b',
	displayName: 'Beta team',
	description: '',
	memberCount: 3,
	canJoin: true,
	pending: false,
}

const memberTeam: Team = {
	id: teamA.id,
	displayName: teamA.displayName,
	description: '',
	isFavorite: false,
	favoritePosition: null,
	memberCount: teamA.memberCount,
	myRole: 'member',
	members: [],
	resources: [],
}

const discoverableOverview: TeamsOverview = {
	teams: [],
	discoverableTeams: [teamA, teamB],
}

function deferred<T>() {
	let resolve!: (value: T) => void
	let reject!: (reason?: unknown) => void
	const promise = new Promise<T>((resolvePromise, rejectPromise) => {
		resolve = resolvePromise
		reject = rejectPromise
	})
	return { promise, resolve, reject }
}

let wrapper: ReturnType<typeof mount> | undefined

function mountHomeView() {
	const pinia = createPinia()
	setActivePinia(pinia)
	const store = useTeamsStore()
	store.discoverableTeams = [teamA, teamB]
	wrapper = mount(HomeView, { global: { plugins: [pinia] } })
	return { wrapper, store }
}

function joinButton(teamName: string) {
	return wrapper!.find(`article[aria-label="${teamName}"] button`)
}

describe('HomeView team joining', () => {
	beforeEach(() => {
		fetchTeams.mockReset()
		fetchTeams.mockResolvedValue({ teams: [], discoverableTeams: [] })
		joinTeam.mockReset()
		joinTeam.mockResolvedValue('joined')
		routerPush.mockReset()
		showSuccess.mockReset()
		showError.mockReset()
		loggerError.mockReset()
	})

	afterEach(() => {
		wrapper?.unmount()
		wrapper = undefined
	})

	it('keeps one join in flight and disables every Join button', async () => {
		const pendingJoin = deferred<'joined' | 'requested'>()
		joinTeam.mockReturnValueOnce(pendingJoin.promise)
		fetchTeams.mockResolvedValue(discoverableOverview)
		const { wrapper: home } = mountHomeView()

		await joinButton(teamA.displayName).trigger('click')

		expect(joinTeam).toHaveBeenCalledTimes(1)
		expect((joinButton(teamA.displayName).element as HTMLButtonElement).disabled).toBe(true)
		expect((joinButton(teamB.displayName).element as HTMLButtonElement).disabled).toBe(true)
		expect(home.find(`article[aria-label="${teamA.displayName}"] [data-testid="loading-icon"]`).exists()).toBe(true)
		expect(home.find(`article[aria-label="${teamB.displayName}"] [data-testid="loading-icon"]`).exists()).toBe(false)

		home.findAllComponents(DiscoverableTeamCard)[1].vm.$emit('join')
		await flushPromises()
		expect(joinTeam).toHaveBeenCalledTimes(1)

		pendingJoin.resolve('joined')
		await flushPromises()

		expect((joinButton(teamA.displayName).element as HTMLButtonElement).disabled).toBe(false)
		expect((joinButton(teamB.displayName).element as HTMLButtonElement).disabled).toBe(false)
		expect(home.findAll('[data-testid="loading-icon"]')).toHaveLength(0)
	})

	it('shows success and navigates when refreshed state includes the team', async () => {
		fetchTeams.mockResolvedValue({ teams: [memberTeam], discoverableTeams: [teamB] })
		const { store } = mountHomeView()

		await joinButton(teamA.displayName).trigger('click')
		await flushPromises()

		expect(showSuccess).toHaveBeenCalledWith('You have joined the team Alpha team.')
		expect(store.getTeam(teamA.id)).toBeDefined()
		expect(routerPush).toHaveBeenCalledWith({ name: 'team', params: { teamId: teamA.id } })
	})

	it('shows the pending toast without navigating when approval is required', async () => {
		joinTeam.mockResolvedValue('requested')
		mountHomeView()

		await joinButton(teamA.displayName).trigger('click')
		await flushPromises()

		expect(showSuccess).toHaveBeenCalledWith('Your request to join this team is pending approval')
		expect(routerPush).not.toHaveBeenCalled()
	})

	it('shows the error, reloads teams, and stays home when the join is rejected', async () => {
		const error = new Error('join rejected')
		joinTeam.mockRejectedValueOnce(error)
		mountHomeView()

		await joinButton(teamA.displayName).trigger('click')
		await flushPromises()

		expect(loggerError).toHaveBeenCalledWith('Could not join the team', { error })
		expect(showError).toHaveBeenCalledWith('Unable to join the team')
		expect(fetchTeams).toHaveBeenCalledTimes(1)
		expect(routerPush).not.toHaveBeenCalled()
	})

	it('does not navigate when the join succeeds but refreshing teams fails', async () => {
		fetchTeams.mockRejectedValueOnce(new Error('refresh failed'))
		const { store, wrapper: home } = mountHomeView()

		await joinButton(teamA.displayName).trigger('click')
		await flushPromises()

		expect(showSuccess).toHaveBeenCalledWith('You have joined the team Alpha team.')
		expect(routerPush).not.toHaveBeenCalled()
		expect(joinTeam).toHaveBeenCalledTimes(1)
		expect(store.loadError).toBe(true)
		expect(home.text()).toContain('Could not load teams')
		expect(home.text()).toContain('Try again')

		await home.findAll('button').find((button) => button.text().includes('Try again'))!.trigger('click')
		await flushPromises()
		expect(fetchTeams).toHaveBeenCalledTimes(2)
		expect(joinTeam).toHaveBeenCalledTimes(1)
	})
})
