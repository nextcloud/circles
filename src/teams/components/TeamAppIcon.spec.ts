/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import TeamAppIcon from './TeamAppIcon.vue'

describe('Teams app icons', () => {
	it.each(['M3 3H21V21H3Z', 'm3 3h18v18h-18z', ' M .5 -.5 L 24 24 '])('renders SVG path data: %s', (icon) => {
		const wrapper = shallowMount(TeamAppIcon, { props: { icon } })
		expect(wrapper.getComponent(NcIconSvgWrapper).props()).toMatchObject({ path: icon.trim(), size: 20 })
		expect(wrapper.find('.team-app-icon__emoji').exists()).toBe(false)
	})

	it.each(['\u{1F4DA}', '\u{1F469}\u{200D}\u{1F4BB}', '\u{1F1E9}\u{1F1EA}', '\u{1F44D}\u{1F3FD}', '1\u{FE0F}\u{20E3}'])('renders emoji as text: %s', (icon) => {
		const wrapper = shallowMount(TeamAppIcon, { props: { icon } })
		expect(wrapper.findComponent(NcIconSvgWrapper).exists()).toBe(false)
		expect(wrapper.get('.team-app-icon__emoji').text()).toBe(icon)
		expect(wrapper.attributes('aria-hidden')).toBe('true')
	})

	it.each([undefined, '', ' \n '])('renders no icon for an empty value: %s', (icon) => {
		const wrapper = shallowMount(TeamAppIcon, { props: { icon } })
		expect(wrapper.findComponent(NcIconSvgWrapper).exists()).toBe(false)
		expect(wrapper.find('span').exists()).toBe(false)
		expect(wrapper.text()).toBe('')
	})

	it('does not interpret unsupported markup as HTML or SVG', () => {
		const icon = '<svg onload="alert(1)"><path d="M0 0" /></svg>'
		const wrapper = shallowMount(TeamAppIcon, { props: { icon } })
		expect(wrapper.findComponent(NcIconSvgWrapper).exists()).toBe(false)
		expect(wrapper.find('svg').exists()).toBe(false)
		expect(wrapper.text()).toBe(icon)
	})

	it('updates when an icon changes between path and emoji', async () => {
		const wrapper = shallowMount(TeamAppIcon, { props: { icon: 'M3 3H21V21H3Z' } })
		await wrapper.setProps({ icon: '\u{1F4DA}' })
		expect(wrapper.findComponent(NcIconSvgWrapper).exists()).toBe(false)
		expect(wrapper.text()).toBe('\u{1F4DA}')
		await wrapper.setProps({ icon: 'm0 0h24v24z' })
		expect(wrapper.getComponent(NcIconSvgWrapper).props('path')).toBe('m0 0h24v24z')
	})
})
