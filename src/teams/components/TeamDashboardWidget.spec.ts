/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type * as L10n from '@nextcloud/l10n'
import type { TeamDashboardResult, TeamDashboardWidget } from '../types.ts'

import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import TeamDashboardWidgetComponent from './TeamDashboardWidget.vue'

vi.mock('@nextcloud/l10n', async (importOriginal) => ({
	...await importOriginal<typeof L10n>(),
	t: (_app: string, text: string) => text,
}))

const widget: TeamDashboardWidget = { id: 'pages', title: 'Recent pages', order: 6, icon_url: '', icon_class: '', item_icons_round: false }

function render(result: TeamDashboardResult) {
	return mount(TeamDashboardWidgetComponent, {
		props: { widget, result, loading: false },
		global: {
			stubs: {
				NcDashboardWidget: {
					props: ['items'],
					template: '<div><slot v-for="item in items" :item="item" /><slot v-if="!items.length" name="empty-content" /></div>',
				},
				NcDashboardWidgetItem: {
					props: ['mainText', 'subText', 'targetUrl'],
					template: '<a :href="targetUrl"><slot name="avatar" />{{ mainText }} {{ subText }}</a>',
				},
				NcEmptyContent: { props: ['name'], template: '<div>{{ name }}<slot name="action" /></div>' },
				NcButton: { template: '<button><slot /></button>' },
				NcIconSvgWrapper: true,
			},
		},
	})
}

describe('TeamDashboardWidget', () => {
	it('renders team-provided page titles and links', () => {
		const wrapper = render({ status: 'ok', data: {
			items: [{ title: 'Team handbook', subtitle: 'Notes', link: '/apps/collectives/team-a/handbook', iconUrl: '/page.png', overlayIconUrl: '', sinceId: '1' }],
			emptyContentMessage: '',
			halfEmptyContentMessage: '',
		} })
		expect(wrapper.get('h2').text()).toBe('Recent pages')
		expect(wrapper.get('a').attributes('href')).toBe('/apps/collectives/team-a/handbook')
		expect(wrapper.get('a').text()).toContain('Team handbook')
		wrapper.unmount()
	})

	it('shows the provider empty-state message', () => {
		const wrapper = render({ status: 'ok', data: { items: [], emptyContentMessage: 'No recent team pages', halfEmptyContentMessage: '' } })
		expect(wrapper.text()).toContain('No recent team pages')
		wrapper.unmount()
	})

	it('offers an independent retry after a provider failure', async () => {
		const wrapper = render({ status: 'error' })
		await wrapper.get('button').trigger('click')
		expect(wrapper.emitted('retry')).toHaveLength(1)
		expect(wrapper.find('a').exists()).toBe(false)
		wrapper.unmount()
	})

	it('replaces a broken item icon with the standard fallback', async () => {
		const wrapper = render({ status: 'ok', data: {
			items: [{ title: 'Page', subtitle: '', link: '/page', iconUrl: '/broken.png', overlayIconUrl: '', sinceId: '' }],
			emptyContentMessage: '',
			halfEmptyContentMessage: '',
		} })
		await wrapper.get('a img').trigger('error')
		expect(wrapper.find('a img').exists()).toBe(false)
		wrapper.unmount()
	})
})
