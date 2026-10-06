/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Team } from '../types.ts'

import { shallowMount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import TeamCard from './TeamCard.vue'

// @nextcloud/vue pulls RTL helpers from the same module, so keep the rest.
vi.mock('@nextcloud/l10n', async (importOriginal) => ({
	...(await importOriginal<object>()),
	t: (app: string, text: string) => text,
}))

function makeTeam(overrides: Partial<Team> = {}): Team {
	return {
		id: 'team1',
		displayName: 'Team One',
		description: 'A team',
		isFavorite: false,
		favoritePosition: null,
		memberCount: 2,
		myRole: 'member',
		members: [
			{ id: 'm1', userId: 'alice', displayName: 'Alice', isUser: true, role: 'member' },
			{ id: 'm2', userId: 'bob', displayName: 'Bob', isUser: true, role: 'member' },
		],
		resources: [],
		...overrides,
	}
}

function mountCard(props: Record<string, unknown> = {}, team: Team = makeTeam()) {
	return shallowMount(TeamCard, {
		props: { team, ...props },
		global: {
			// Render slots of stubbed components so actions and links stay reachable.
			renderStubDefaultSlot: true,
			stubs: { RouterLink: { template: '<a><slot /></a>' } },
		},
	})
}

function actionButtons(wrapper: ReturnType<typeof mountCard>) {
	return wrapper.findAllComponents({ name: 'NcActionButton' })
}

describe('TeamCard actions', () => {
	it('only offers the favorite action when the card is not sortable', () => {
		const buttons = actionButtons(mountCard())

		expect(buttons).toHaveLength(1)
	})

	it('offers move up, move down and favorite when sortable', () => {
		const buttons = actionButtons(mountCard({ sortable: true }))

		expect(buttons).toHaveLength(3)
	})

	it('emits toggleFavorite with the team', () => {
		const team = makeTeam()
		const wrapper = mountCard({}, team)

		actionButtons(wrapper)[0].vm.$emit('click')

		expect(wrapper.emitted('toggleFavorite')).toEqual([[team]])
	})

	it('emits moveUp and moveDown with the team', () => {
		const team = makeTeam({ isFavorite: true })
		const wrapper = mountCard({ sortable: true, canMoveUp: true, canMoveDown: true }, team)
		const [up, down] = actionButtons(wrapper)

		up.vm.$emit('click')
		down.vm.$emit('click')

		expect(wrapper.emitted('moveUp')).toEqual([[team]])
		expect(wrapper.emitted('moveDown')).toEqual([[team]])
	})

	it('disables moving beyond the ends of the list', () => {
		const [up, down] = actionButtons(mountCard({ sortable: true, canMoveUp: false, canMoveDown: true }))

		expect(up.props('disabled')).toBe(true)
		expect(down.props('disabled')).toBe(false)
	})

	it('disables every action while a favorite update is running', () => {
		const buttons = actionButtons(mountCard({ sortable: true, canMoveUp: true, canMoveDown: true, favoriteBusy: true }))

		expect(buttons.map((button) => button.props('disabled'))).toEqual([true, true, true])
	})
})

describe('TeamCard dragging', () => {
	it('is draggable only when requested', () => {
		expect(mountCard({ draggable: true }).get('article').attributes('draggable')).toBe('true')
		expect(mountCard({ draggable: false }).get('article').attributes('draggable')).toBe('false')
	})

	it('forwards drag events with the team', async () => {
		const team = makeTeam({ isFavorite: true })
		const wrapper = mountCard({ draggable: true }, team)
		const article = wrapper.get('article')

		await article.trigger('dragstart')
		await article.trigger('dragover')
		await article.trigger('dragend')

		expect(wrapper.emitted('dragStart')?.[0][0]).toEqual(team)
		expect(wrapper.emitted('dragOver')?.[0][0]).toEqual(team)
		expect(wrapper.emitted('dragEnd')).toHaveLength(1)
	})
})

describe('TeamCard content', () => {
	it('shows the description only when there is one', () => {
		expect(mountCard().text()).toContain('A team')
		expect(mountCard({}, makeTeam({ description: '' })).find('p').exists()).toBe(false)
	})

	it('shows how many members are not part of the preview', () => {
		const wrapper = mountCard({}, makeTeam({ memberCount: 9 }))

		expect(wrapper.text()).toContain('+7')
	})

	it('shows no overflow counter when all members are previewed', () => {
		expect(mountCard().text()).not.toContain('+')
	})

	it('previews at most five members', () => {
		const members = Array.from({ length: 8 }, (_, index): Team['members'][number] => ({
			id: `m${index}`,
			userId: `user${index}`,
			displayName: `User ${index}`,
			isUser: true,
			role: 'member',
		}))
		const wrapper = mountCard({}, makeTeam({ members, memberCount: 8 }))

		expect(wrapper.findAllComponents({ name: 'NcAvatar' })).toHaveLength(5)
	})
})
