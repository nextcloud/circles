/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { DiscoverableTeam } from '../types.ts'

import { shallowMount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import DiscoverableTeamCard from './DiscoverableTeamCard.vue'

vi.mock('@nextcloud/l10n', async (importOriginal) => ({
	...(await importOriginal<object>()),
	t: (_app: string, text: string) => text,
	n: (_app: string, _singular: string, _plural: string, count: number) => `${count} members`,
}))

const NcAvatarStub = {
	name: 'NcAvatar',
	props: ['displayName', 'isNoUser', 'size', 'hideStatus', 'disableMenu', 'disableTooltip'],
	template: '<div />',
}

const NcButtonWithIconStub = {
	name: 'NcButton',
	props: { disabled: Boolean },
	template: '<button :disabled="disabled"><slot name="icon" /><slot /></button>',
}

const team: DiscoverableTeam = {
	id: 'team-1',
	displayName: 'Product team',
	description: 'A team for product work',
	memberCount: 4,
	canJoin: true,
	pending: false,
}

describe('DiscoverableTeamCard', () => {
	it('emits join when the join button is clicked', async () => {
		const wrapper = shallowMount(DiscoverableTeamCard, { props: { team } })
		const button = wrapper.findComponent({ name: 'NcButton' })

		expect(button.exists()).toBe(true)
		await button.trigger('click')
		expect(wrapper.emitted('join')).toEqual([[]])
	})

	it('shows pending state without a join button', () => {
		const wrapper = shallowMount(DiscoverableTeamCard, {
			props: { team: { ...team, canJoin: false, pending: true } },
		})

		expect(wrapper.text()).toContain('Request pending')
		expect(wrapper.findComponent({ name: 'NcButton' }).exists()).toBe(false)
	})

	it('shows neither a pending state nor a join button when closed', () => {
		const wrapper = shallowMount(DiscoverableTeamCard, {
			props: { team: { ...team, canJoin: false } },
		})

		expect(wrapper.text()).not.toContain('Request pending')
		expect(wrapper.findComponent({ name: 'NcButton' }).exists()).toBe(false)
	})

	it('disables joining and shows a loading icon while the request is in flight', () => {
		const wrapper = shallowMount(DiscoverableTeamCard, {
			props: { team, joining: true },
			global: { stubs: { NcButton: NcButtonWithIconStub } },
		})
		const button = wrapper.findComponent({ name: 'NcButton' })

		expect(button.props('disabled')).toBe(true)
		expect(wrapper.findComponent({ name: 'NcLoadingIcon' }).exists()).toBe(true)
	})

	it('disables joining without a loading icon when another request is in flight', () => {
		const wrapper = shallowMount(DiscoverableTeamCard, {
			props: { team, disabled: true },
			global: { stubs: { NcButton: NcButtonWithIconStub } },
		})
		const button = wrapper.findComponent({ name: 'NcButton' })

		expect(button.props('disabled')).toBe(true)
		expect(wrapper.findComponent({ name: 'NcLoadingIcon' }).exists()).toBe(false)
	})

	it('has no link and gives NcAvatar no member-only props', () => {
		const wrapper = shallowMount(DiscoverableTeamCard, {
			props: { team },
			global: { stubs: { NcAvatar: NcAvatarStub } },
		})
		const avatar = wrapper.findComponent({ name: 'NcAvatar' })

		expect(wrapper.find('a').exists()).toBe(false)
		expect(wrapper.findComponent({ name: 'RouterLink' }).exists()).toBe(false)
		expect(avatar.vm.$attrs).not.toHaveProperty('url')
		expect(avatar.vm.$attrs).not.toHaveProperty('user')
	})
})
