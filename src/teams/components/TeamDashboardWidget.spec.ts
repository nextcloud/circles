/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type * as L10n from '@nextcloud/l10n'
import type { TeamDashboardWidget as Widget } from '../types.ts'

import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import TeamDashboardWidget from './TeamDashboardWidget.vue'

vi.mock('@nextcloud/l10n', async (importOriginal) => ({ ...await importOriginal<typeof L10n>(), t: (_app: string, text: string) => text }))

const widget: Widget = { id: 'pages', title: 'Pages', order: 1, icon_url: '/icon.svg', icon_class: '', item_icons_round: false, widget_url: '/team', buttons: [{ type: 'setup', text: 'Create page', link: '/new' }] }
function render(empty = false) {
	return mount(TeamDashboardWidget, {
		props: { widget, items: { items: empty ? [] : [{ title: 'Handbook', subtitle: 'Notes', link: '/handbook', iconUrl: '/page.png', overlayIconUrl: '', sinceId: '1' }], emptyContentMessage: 'No pages', halfEmptyContentMessage: '' } },
		global: { stubs: {
			NcDashboardWidget: { props: ['items'], template: '<div><slot v-for="item in items" :item="item" /><slot v-if="!items.length" name="empty-content" /></div>' },
			NcDashboardWidgetItem: { props: ['mainText', 'targetUrl'], template: '<a :href="targetUrl"><slot name="avatar" />{{ mainText }}</a>' },
			NcEmptyContent: { props: ['name'], template: '<div>{{ name }}</div>' },
			NcButton: { props: ['href'], template: '<a :href="href"><slot /></a>' },
			NcIconSvgWrapper: true,
		} },
	})
}

describe('TeamDashboardWidget', () => {
	it('renders provider titles, destinations and item links', () => {
		const wrapper = render()
		expect(wrapper.get('h2 a').attributes('href')).toBe('/team')
		expect(wrapper.get('a[href="/handbook"]').text()).toBe('Handbook')
		expect(wrapper.find('.team-dashboard-widget__actions').exists()).toBe(false)
	})

	it('shows setup actions only for empty content', () => {
		const wrapper = render(true)
		expect(wrapper.text()).toContain('No pages')
		expect(wrapper.get('a[href="/new"]').text()).toBe('Create page')
	})

	it('replaces broken item images with a fallback', async () => {
		const wrapper = render()
		await wrapper.get('a[href="/handbook"] img').trigger('error')
		expect(wrapper.find('a[href="/handbook"] img').exists()).toBe(false)
	})
})
