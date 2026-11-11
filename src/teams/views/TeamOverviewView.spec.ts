/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type * as L10n from '@nextcloud/l10n'
import type { TeamDashboardItems, TeamDashboardLayout, TeamDashboardWidget as Widget } from '../types.ts'

import { enableAutoUnmount, flushPromises, shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import TeamDashboardWidget from '../components/TeamDashboardWidget.vue'
import TeamOverviewView from './TeamOverviewView.vue'
import { fetchTeamDashboardItems, fetchTeamDashboardLayout, fetchTeamDashboardWidgets, saveTeamDashboardLayout } from '../api.ts'
import { useTeamResourcesStore } from '../resourcesStore.ts'

const replace = vi.hoisted(() => vi.fn())
enableAutoUnmount(afterEach)
vi.mock('vue-router', () => ({ useRouter: () => ({ replace }) }))
vi.mock('@nextcloud/l10n', async (importOriginal) => ({ ...await importOriginal<typeof L10n>(), t: (_app: string, text: string) => text }))
vi.mock('../api.ts', () => ({
	fetchTeamDashboardLayout: vi.fn(),
	fetchTeamDashboardWidgets: vi.fn(),
	fetchTeamDashboardItems: vi.fn(),
	saveTeamDashboardLayout: vi.fn(),
}))

const data: TeamDashboardItems = { items: [], emptyContentMessage: 'Nothing yet', halfEmptyContentMessage: '' }
const widget = (id: string, order = 1, interval = 0): Widget => ({ id, title: id, order, icon_url: '', icon_class: '', item_icons_round: false, reload_interval: interval })
const layout = (widgetIds: string[] | null = null): TeamDashboardLayout => ({ widgetIds, canEdit: true, available: true })

function render() {
	const pinia = createPinia()
	setActivePinia(pinia)
	const store = useTeamResourcesStore()
	Object.assign(store.slot('team-a'), { pagesChecked: true, orderChecked: true })
	const wrapper = shallowMount(TeamOverviewView, {
		props: { teamId: 'team-a' },
		global: {
			plugins: [pinia],
			stubs: {
				NcButton: { template: '<button><slot /></button>' },
				NcEmptyContent: { props: ['name'], template: '<div>{{ name }}<slot name="action" /></div>' },
			},
		},
	})
	return { wrapper, store }
}

describe('TeamOverviewView', () => {
	beforeEach(() => {
		vi.resetAllMocks()
		vi.useFakeTimers()
		vi.spyOn(document, 'hidden', 'get').mockReturnValue(false)
		vi.mocked(fetchTeamDashboardLayout).mockResolvedValue(layout())
		vi.mocked(fetchTeamDashboardWidgets).mockResolvedValue([widget('pages', 2, 5), widget('tasks', 1, 0)])
		vi.mocked(fetchTeamDashboardItems).mockResolvedValue({ pages: data, tasks: data })
	})

	afterEach(() => {
		vi.useRealTimers()
		vi.restoreAllMocks()
	})

	it('intersects the shared selection with the catalog and sorts by order then id', async () => {
		vi.mocked(fetchTeamDashboardLayout).mockResolvedValue(layout(['pages', 'tasks', 'missing', 'alpha']))
		vi.mocked(fetchTeamDashboardWidgets).mockResolvedValue([widget('pages', 2), widget('other'), widget('tasks'), widget('alpha')])
		const { wrapper } = render()
		await flushPromises()
		expect(fetchTeamDashboardItems).toHaveBeenCalledWith('team-a', ['alpha', 'tasks', 'pages'], expect.any(AbortSignal))
		expect(wrapper.findAllComponents(TeamDashboardWidget)).toHaveLength(2)
		wrapper.unmount()
	})

	it('does not request items for an explicit empty selection', async () => {
		vi.mocked(fetchTeamDashboardLayout).mockResolvedValue(layout([]))
		const { wrapper } = render()
		await flushPromises()
		expect(fetchTeamDashboardItems).not.toHaveBeenCalled()
		expect(wrapper.text()).toContain('No dashboards selected')
		wrapper.unmount()
	})

	it('hides widgets omitted from the batched response', async () => {
		vi.mocked(fetchTeamDashboardItems).mockResolvedValue({ pages: data })
		const { wrapper } = render()
		await flushPromises()
		const widgets = wrapper.findAllComponents(TeamDashboardWidget)
		expect(widgets).toHaveLength(1)
		expect(widgets[0].props('widget').id).toBe('pages')
		wrapper.unmount()
	})

	it('shows a whole-overview error and retries', async () => {
		vi.mocked(fetchTeamDashboardItems).mockRejectedValueOnce(new Error('offline'))
		const { wrapper } = render()
		await flushPromises()
		expect(wrapper.text()).toContain('Could not load dashboards')
		await wrapper.findAll('button').find((button) => button.text() === 'Try again')!.trigger('click')
		await flushPromises()
		expect(wrapper.text()).not.toContain('Could not load dashboards')
		expect(fetchTeamDashboardItems).toHaveBeenCalledTimes(2)
		wrapper.unmount()
	})

	it('marks dashboard unavailable and redirects on 404', async () => {
		vi.mocked(fetchTeamDashboardItems).mockRejectedValue({ response: { status: 404 } })
		const { wrapper, store } = render()
		await flushPromises()
		expect(store.forTeam('team-a').dashboard?.available).toBe(false)
		expect(replace).toHaveBeenCalledWith({ name: 'team-folder', params: { teamId: 'team-a' } })
		wrapper.unmount()
	})

	it('redirects an open Overview immediately when Dashboard becomes unavailable', async () => {
		const { wrapper, store } = render()
		await flushPromises()
		store.markDashboardUnavailable('team-a')
		await flushPromises()
		expect(replace).toHaveBeenCalledWith({ name: 'team-folder', params: { teamId: 'team-a' } })
		wrapper.unmount()
	})

	it('redirects direct links when Dashboard is unavailable', async () => {
		vi.mocked(fetchTeamDashboardLayout).mockResolvedValue({ ...layout(), available: false })
		const { wrapper } = render()
		await flushPromises()
		expect(fetchTeamDashboardWidgets).not.toHaveBeenCalled()
		expect(fetchTeamDashboardItems).not.toHaveBeenCalled()
		expect(replace).toHaveBeenCalledWith({ name: 'team-folder', params: { teamId: 'team-a' } })
		wrapper.unmount()
	})

	it.each([401, 403])('clears content after a %s response', async (status) => {
		const { wrapper } = render()
		await flushPromises()
		vi.mocked(fetchTeamDashboardItems).mockRejectedValueOnce({ response: { status } })
		await vi.advanceTimersByTimeAsync(5000)
		expect(wrapper.findAllComponents(TeamDashboardWidget)).toHaveLength(0)
		expect(wrapper.text()).toContain('Could not load dashboards')
		wrapper.unmount()
	})

	it('refreshes only due widgets without reloading layout or metadata and skips hidden tabs', async () => {
		const { wrapper } = render()
		await flushPromises()
		await vi.advanceTimersByTimeAsync(4000)
		expect(fetchTeamDashboardItems).toHaveBeenCalledTimes(1)
		await vi.advanceTimersByTimeAsync(1000)
		expect(fetchTeamDashboardItems).toHaveBeenLastCalledWith('team-a', ['pages'], expect.any(AbortSignal))
		expect(fetchTeamDashboardLayout).toHaveBeenCalledTimes(1)
		expect(fetchTeamDashboardWidgets).toHaveBeenCalledTimes(1)
		vi.spyOn(document, 'hidden', 'get').mockReturnValue(true)
		await vi.advanceTimersByTimeAsync(10000)
		expect(fetchTeamDashboardItems).toHaveBeenCalledTimes(2)
		vi.spyOn(document, 'hidden', 'get').mockReturnValue(false)
		document.dispatchEvent(new Event('visibilitychange'))
		await flushPromises()
		expect(fetchTeamDashboardLayout).toHaveBeenCalledTimes(2)
		wrapper.unmount()
	})

	it('aborts team requests and ignores late item results on switch and unmount', async () => {
		let resolve!: (value: Record<string, TeamDashboardItems>) => void
		vi.mocked(fetchTeamDashboardItems).mockImplementationOnce(() => new Promise((done) => {
			resolve = done
		}))
		const { wrapper } = render()
		await flushPromises()
		const oldSignal = vi.mocked(fetchTeamDashboardItems).mock.calls[0][2]!
		await wrapper.setProps({ teamId: 'team-b' })
		await flushPromises()
		expect(oldSignal.aborted).toBe(true)
		resolve({ old: data })
		await flushPromises()
		expect(wrapper.findAllComponents(TeamDashboardWidget).map((entry) => entry.props('widget').id)).toEqual(['tasks', 'pages'])
		const signal = vi.mocked(fetchTeamDashboardItems).mock.calls[1][2]!
		wrapper.unmount()
		expect(signal.aborted).toBe(true)
	})

	it('keeps widgets mounted during focus refresh and replaces them only after new items arrive', async () => {
		const { wrapper } = render()
		await flushPromises()
		const original = wrapper.findAllComponents(TeamDashboardWidget)
		let resolveWidgets!: (widgets: Widget[]) => void
		let resolveItems!: (items: Record<string, TeamDashboardItems>) => void
		vi.mocked(fetchTeamDashboardWidgets).mockImplementationOnce(() => new Promise((resolve) => {
			resolveWidgets = resolve
		}))
		vi.mocked(fetchTeamDashboardItems).mockImplementationOnce(() => new Promise((resolve) => {
			resolveItems = resolve
		}))
		vi.mocked(fetchTeamDashboardLayout).mockResolvedValueOnce(layout(['replacement']))
		document.dispatchEvent(new Event('visibilitychange'))
		await flushPromises()
		expect(wrapper.findComponent({ name: 'NcLoadingIcon' }).exists()).toBe(false)
		expect(wrapper.findAllComponents(TeamDashboardWidget).map((entry) => entry.vm)).toEqual(original.map((entry) => entry.vm))
		resolveWidgets([widget('replacement')])
		await flushPromises()
		expect(wrapper.findAllComponents(TeamDashboardWidget).map((entry) => entry.vm)).toEqual(original.map((entry) => entry.vm))
		expect(wrapper.findComponent({ name: 'NcLoadingIcon' }).exists()).toBe(false)
		resolveItems({ replacement: data })
		await flushPromises()
		const updated = wrapper.findAllComponents(TeamDashboardWidget)
		expect(updated).toHaveLength(1)
		expect(updated[0].props('widget').id).toBe('replacement')
		expect(updated[0].props('items')).toEqual(data)
		wrapper.unmount()
	})

	it('resyncs after a confirmed save and hides customization for members', async () => {
		const { wrapper } = render()
		await flushPromises()
		await wrapper.get('button').trigger('click')
		const dialog = wrapper.findComponent({ name: 'TeamDashboardSettingsDialog' })
		vi.mocked(saveTeamDashboardLayout).mockResolvedValue({ widgetIds: [], canEdit: true })
		vi.mocked(fetchTeamDashboardLayout).mockResolvedValue(layout([]))
		expect(await dialog.props('saveLayout')([])).toBe(true)
		expect(fetchTeamDashboardWidgets).toHaveBeenCalledTimes(2)
		wrapper.unmount()
		vi.mocked(fetchTeamDashboardLayout).mockResolvedValue({ ...layout(), canEdit: false })
		const member = render().wrapper
		await flushPromises()
		expect(member.find('button').exists()).toBe(false)
		member.unmount()
	})

	it('does not start new requests when a save completes after unmount', async () => {
		let resolve!: (value: { widgetIds: string[], canEdit: boolean }) => void
		vi.mocked(saveTeamDashboardLayout).mockImplementationOnce(() => new Promise((done) => {
			resolve = done
		}))
		const { wrapper } = render()
		await flushPromises()
		await wrapper.get('button').trigger('click')
		const saving = wrapper.findComponent({ name: 'TeamDashboardSettingsDialog' }).props('saveLayout')([])
		wrapper.unmount()
		resolve({ widgetIds: [], canEdit: true })
		await saving
		expect(fetchTeamDashboardLayout).toHaveBeenCalledTimes(1)
		expect(fetchTeamDashboardWidgets).toHaveBeenCalledTimes(1)
	})
})
