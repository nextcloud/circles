/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type * as L10n from '@nextcloud/l10n'

import { destroyWidget, isWidgetRegistered, renderWidget } from '@nextcloud/vue/functions/registerReference'
import { enableAutoUnmount, flushPromises, shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import TeamOverviewView from './TeamOverviewView.vue'
import { fetchTeamDashboardLayout, saveTeamDashboardLayout } from '../api.ts'
import { useTeamResourcesStore } from '../resourcesStore.ts'

const replace = vi.hoisted(() => vi.fn())
enableAutoUnmount(afterEach)
vi.mock('vue-router', () => ({ useRouter: () => ({ replace }) }))
vi.mock('@nextcloud/l10n', async (importOriginal) => ({ ...await importOriginal<typeof L10n>(), t: (_app: string, text: string) => text }))
vi.mock('@nextcloud/vue/functions/registerReference', () => ({
	destroyWidget: vi.fn(),
	isWidgetRegistered: vi.fn(),
	renderWidget: vi.fn(),
}))
vi.mock('../api.ts', () => ({
	fetchTeamDashboardLayout: vi.fn(),
	saveTeamDashboardLayout: vi.fn(),
}))

const layout = { widgetIds: null, canEdit: true, available: true }

function render() {
	const pinia = createPinia()
	setActivePinia(pinia)
	const store = useTeamResourcesStore()
	Object.assign(store.slot('team-a'), { pagesChecked: true, orderChecked: true })
	const wrapper = shallowMount(TeamOverviewView, {
		props: { teamId: 'team-a' },
		global: {
			plugins: [pinia],
			stubs: { NcEmptyContent: { props: ['name'], template: '<div>{{ name }}</div>' } },
		},
	})
	return { wrapper, store }
}

describe('TeamOverviewView', () => {
	beforeEach(() => {
		vi.resetAllMocks()
		vi.mocked(isWidgetRegistered).mockReturnValue(true)
		vi.mocked(fetchTeamDashboardLayout).mockResolvedValue(layout)
	})

	it('renders the active team and destroys each embed before reusing or leaving the view', async () => {
		const { wrapper } = render()
		await flushPromises()
		const [first, firstPayload] = vi.mocked(renderWidget).mock.calls[0]!
		expect(firstPayload.richObject).toMatchObject({ teamId: 'team-a' })

		await wrapper.setProps({ teamId: 'team-b' })
		const [second, secondPayload] = vi.mocked(renderWidget).mock.calls[1]!
		expect(destroyWidget).toHaveBeenCalledWith('dashboard-team', first)
		expect(second).not.toBe(first)
		expect(secondPayload.richObject).toMatchObject({ teamId: 'team-b' })

		wrapper.unmount()
		expect(destroyWidget).toHaveBeenLastCalledWith('dashboard-team', second)
	})

	it('provides the shared layout and handles saves through the team store', async () => {
		const { wrapper, store } = render()
		await flushPromises()
		const [, payload] = vi.mocked(renderWidget).mock.calls[0]!
		const { loadLayout, saveLayout } = payload.richObject as {
			loadLayout: () => Promise<typeof layout>
			saveLayout: (widgetIds: string[]) => Promise<void>
		}
		expect(await loadLayout()).toEqual(layout)
		vi.mocked(saveTeamDashboardLayout).mockResolvedValue({ widgetIds: ['pages'], canEdit: true })
		await saveLayout(['pages'])
		expect(saveTeamDashboardLayout).toHaveBeenCalledWith('team-a', ['pages'])
		expect(store.forTeam('team-a').dashboard?.widgetIds).toEqual(['pages'])
		wrapper.unmount()
	})

	it('tears down the embed and redirects when Dashboard becomes unavailable', async () => {
		const { wrapper, store } = render()
		await flushPromises()
		store.markDashboardUnavailable('team-a')
		await flushPromises()
		expect(destroyWidget).toHaveBeenCalledOnce()
		expect(replace).toHaveBeenCalledWith({ name: 'team-folder', params: { teamId: 'team-a' } })
		wrapper.unmount()
	})

	it('does not render and redirects a direct link when Dashboard is disabled', async () => {
		vi.mocked(isWidgetRegistered).mockReturnValue(false)
		vi.mocked(fetchTeamDashboardLayout).mockResolvedValue({ ...layout, available: false })
		const { wrapper } = render()
		await flushPromises()
		expect(renderWidget).not.toHaveBeenCalled()
		expect(replace).toHaveBeenCalledWith({ name: 'team-folder', params: { teamId: 'team-a' } })
		wrapper.unmount()
	})

	it('shows an error instead of mounting when the bundle is missing', async () => {
		vi.mocked(isWidgetRegistered).mockReturnValue(false)
		const { wrapper } = render()
		await flushPromises()
		expect(renderWidget).not.toHaveBeenCalled()
		expect(wrapper.text()).toContain('Could not load widgets')
		wrapper.unmount()
	})
})
