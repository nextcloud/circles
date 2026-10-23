/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type * as L10n from '@nextcloud/l10n'
import type { TeamDashboardLayout, TeamDashboardResult, TeamDashboardWidget } from '../types.ts'

import { enableAutoUnmount, flushPromises, shallowMount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { ref } from 'vue'
import TeamOverviewView from './TeamOverviewView.vue'
import { fetchTeamDashboardItems, fetchTeamDashboardLayout, fetchTeamDashboardWidgets, saveTeamDashboardLayout } from '../api.ts'

const getCircle = vi.hoisted(() => vi.fn())
vi.mock('vuex', () => ({ useStore: () => ({ getters: { getCircle } }) }))
vi.mock('@nextcloud/l10n', async (importOriginal) => ({
	...await importOriginal<typeof L10n>(),
	t: (_app: string, text: string) => text,
}))
vi.mock('../api.ts', () => ({
	fetchTeamDashboardItems: vi.fn(),
	fetchTeamDashboardWidgets: vi.fn(),
	fetchTeamDashboardLayout: vi.fn(),
	saveTeamDashboardLayout: vi.fn(),
}))

enableAutoUnmount(afterEach)

const widget: TeamDashboardWidget = { id: 'pages', title: 'Recent pages', order: 6, icon_url: '', icon_class: '', item_icons_round: false }

function response(title: string): TeamDashboardResult {
	return {
		status: 'ok',
		data: { items: [{ title, subtitle: '', link: '/page', iconUrl: '', overlayIconUrl: '', sinceId: '' }], emptyContentMessage: '', halfEmptyContentMessage: '' },
	}
}

function setup() {
	const member = ref(true)
	getCircle.mockImplementation(() => ({ isMember: member.value }))
	const wrapper = shallowMount(TeamOverviewView, {
		props: { teamId: 'team-a' },
		global: { stubs: {
			NcButton: { template: '<button><slot /></button>' },
			NcEmptyContent: { props: ['name'], template: '<div>{{ name }}<slot name="action" /></div>' },
			TeamDashboardWidget: {
				name: 'TeamDashboardWidget',
				props: ['widget', 'result', 'loading'],
				emits: ['retry'],
				template: '<section :data-widget="widget.id">{{ JSON.stringify(result) }}<button @click="$emit(\'retry\')">Retry widget</button></section>',
			},
			TeamDashboardSettingsDialog: {
				name: 'TeamDashboardSettingsDialog',
				props: ['catalog', 'widgetIds', 'saving', 'saveLayout'],
				template: '<div data-test="settings" />',
			},
		} },
	})
	async function settings() {
		await wrapper.findAll('button').find((button) => button.text() === 'Customize dashboards')!.trigger('click')
		return wrapper.getComponent({ name: 'TeamDashboardSettingsDialog' })
	}
	return { wrapper, member, settings }
}

async function refresh() {
	document.dispatchEvent(new Event('visibilitychange'))
	await flushPromises()
}

describe('TeamOverviewView', () => {
	beforeEach(() => {
		vi.useFakeTimers()
		vi.spyOn(document, 'hidden', 'get').mockReturnValue(false)
		vi.mocked(fetchTeamDashboardWidgets).mockReset().mockResolvedValue([widget])
		vi.mocked(fetchTeamDashboardItems).mockReset().mockResolvedValue(response('Page A'))
		vi.mocked(fetchTeamDashboardLayout).mockReset().mockResolvedValue({ widgetIds: null, canEdit: true })
		vi.mocked(saveTeamDashboardLayout).mockReset()
	})
	afterEach(() => {
		vi.restoreAllMocks()
		vi.useRealTimers()
	})

	it('loads team widgets and hides unavailable providers', async () => {
		vi.mocked(fetchTeamDashboardItems).mockResolvedValue({ status: 'unavailable' })
		const { wrapper } = setup()
		await flushPromises()
		expect(fetchTeamDashboardItems).toHaveBeenCalledWith('team-a', 'pages', expect.any(AbortSignal))
		expect(wrapper.find('[data-widget]').exists()).toBe(false)
		expect(wrapper.text()).toContain('No content yet')
	})

	it('loads only the explicit shared selection and hides editing for ordinary members', async () => {
		vi.mocked(fetchTeamDashboardWidgets).mockResolvedValue([widget, { ...widget, id: 'deck-team-today' }])
		vi.mocked(fetchTeamDashboardLayout).mockResolvedValue({ widgetIds: ['pages'], canEdit: false })
		const { wrapper } = setup()
		await flushPromises()
		expect(fetchTeamDashboardItems).toHaveBeenCalledTimes(1)
		expect(wrapper.findAll('[data-widget]').map((item) => item.attributes('data-widget'))).toEqual(['pages'])
		expect(wrapper.text()).not.toContain('Customize dashboards')
	})

	it('issues no item requests for an explicitly empty selection but keeps customization available', async () => {
		vi.mocked(fetchTeamDashboardLayout).mockResolvedValue({ widgetIds: [], canEdit: true })
		const { wrapper, settings } = setup()
		await flushPromises()
		expect(fetchTeamDashboardItems).not.toHaveBeenCalled()
		expect(wrapper.text()).toContain('No dashboards selected')
		expect((await settings()).props('catalog')).toEqual([widget])
	})

	it('keeps newly discovered dashboards off after a selection is saved', async () => {
		vi.mocked(fetchTeamDashboardLayout).mockResolvedValue({ widgetIds: ['pages'], canEdit: true })
		const { wrapper, settings } = setup()
		await flushPromises()
		vi.mocked(fetchTeamDashboardWidgets).mockResolvedValue([widget, { ...widget, id: 'deck-team-tomorrow' }])
		await refresh()
		expect((await settings()).props('catalog')).toHaveLength(2)
		expect(wrapper.findAll('[data-widget]')).toHaveLength(1)
		expect(vi.mocked(fetchTeamDashboardItems).mock.calls.every((call) => call[1] === 'pages')).toBe(true)
	})

	it('saves an empty selection and stops refreshing hidden widgets', async () => {
		const { wrapper, settings } = setup()
		await flushPromises()
		vi.mocked(saveTeamDashboardLayout).mockResolvedValue({ widgetIds: [], canEdit: true })
		vi.mocked(fetchTeamDashboardLayout).mockResolvedValue({ widgetIds: [], canEdit: true })
		expect(await (await settings()).props('saveLayout')([])).toBe(true)
		await flushPromises()
		expect(saveTeamDashboardLayout).toHaveBeenCalledWith('team-a', [], expect.any(AbortSignal))
		expect(wrapper.text()).toContain('No dashboards selected')
		expect(fetchTeamDashboardItems).toHaveBeenCalledTimes(1)
	})

	it('keeps the confirmed selection when a save fails', async () => {
		const { wrapper, settings } = setup()
		await flushPromises()
		const dialog = await settings()
		vi.mocked(saveTeamDashboardLayout).mockRejectedValue(new Error('Save failed'))
		await expect(dialog.props('saveLayout')([])).rejects.toThrow('Save failed')
		await flushPromises()
		expect(dialog.props('widgetIds')).toBeNull()
		expect(dialog.props('saving')).toBe(false)
		expect(wrapper.findAll('[data-widget]')).toHaveLength(1)
	})

	it('does not submit a save after editing permission is revoked', async () => {
		const { wrapper, settings } = setup()
		await flushPromises()
		const save = (await settings()).props('saveLayout')
		vi.mocked(fetchTeamDashboardLayout).mockResolvedValue({ widgetIds: null, canEdit: false })
		await refresh()
		expect(wrapper.find('[data-test="settings"]').exists()).toBe(false)
		expect(await save([])).toBe(false)
		expect(saveTeamDashboardLayout).not.toHaveBeenCalled()
	})

	it('ignores a late save response and closes settings after changing teams', async () => {
		let resolveSave!: (layout: TeamDashboardLayout) => void
		vi.mocked(saveTeamDashboardLayout).mockImplementationOnce(() => new Promise((resolve) => {
			resolveSave = resolve
		}))
		const { wrapper, settings } = setup()
		await flushPromises()
		const saved = (await settings()).props('saveLayout')([])
		await wrapper.setProps({ teamId: 'team-b' })
		await flushPromises()
		resolveSave({ widgetIds: [], canEdit: true })
		expect(await saved).toBe(false)
		expect(wrapper.find('[data-test="settings"]').exists()).toBe(false)
		expect(wrapper.findAll('[data-widget]')).toHaveLength(1)
	})

	it('observes another admins shared selection on the next refresh', async () => {
		const { wrapper } = setup()
		await flushPromises()
		vi.mocked(fetchTeamDashboardLayout).mockResolvedValue({ widgetIds: [], canEdit: true })
		await vi.advanceTimersByTimeAsync(33_000)
		expect(wrapper.text()).toContain('No dashboards selected')
		expect(fetchTeamDashboardItems).toHaveBeenCalledTimes(1)
	})

	it('retains selections across temporary provider disappearance', async () => {
		vi.mocked(fetchTeamDashboardLayout).mockResolvedValue({ widgetIds: ['pages'], canEdit: true })
		vi.mocked(fetchTeamDashboardWidgets).mockResolvedValueOnce([])
		const { wrapper, settings } = setup()
		await flushPromises()
		expect((await settings()).props('widgetIds')).toEqual(['pages'])
		expect(wrapper.find('[data-widget]').exists()).toBe(false)
		await refresh()
		expect(wrapper.findAll('[data-widget]')).toHaveLength(1)
	})

	it('does not fall back to all widgets when settings fail to load', async () => {
		vi.mocked(fetchTeamDashboardLayout).mockRejectedValue(new Error('Unavailable'))
		const { wrapper } = setup()
		await flushPromises()
		expect(wrapper.text()).toContain('Overview unavailable')
		expect(fetchTeamDashboardItems).not.toHaveBeenCalled()
	})

	it('does not let a late team A result populate team B', async () => {
		let resolveA!: (value: TeamDashboardResult) => void
		vi.mocked(fetchTeamDashboardItems)
			.mockImplementationOnce(() => new Promise((resolve) => { resolveA = resolve }))
			.mockResolvedValue(response('Page B'))
		const { wrapper } = setup()
		await flushPromises()
		const signalA = vi.mocked(fetchTeamDashboardItems).mock.calls[0]![2]!
		await wrapper.setProps({ teamId: 'team-b' })
		await flushPromises()
		expect(signalA.aborted).toBe(true)
		resolveA(response('Page A'))
		await flushPromises()
		expect(wrapper.text()).toContain('Page B')
		expect(wrapper.text()).not.toContain('Page A')
	})

	it('does not let late metadata from team A start requests in team B', async () => {
		let resolveA!: (value: TeamDashboardWidget[]) => void
		vi.mocked(fetchTeamDashboardWidgets).mockImplementationOnce(() => new Promise((resolve) => {
			resolveA = resolve
		}))
		const { wrapper } = setup()
		await wrapper.setProps({ teamId: 'team-b' })
		await flushPromises()
		resolveA([{ ...widget, id: 'old-widget' }])
		await flushPromises()
		expect(fetchTeamDashboardItems).toHaveBeenCalledTimes(1)
		expect(fetchTeamDashboardItems).toHaveBeenCalledWith('team-b', 'pages', expect.any(AbortSignal))
	})

	it('clears data immediately when membership is lost', async () => {
		const { wrapper, member } = setup()
		await flushPromises()
		member.value = false
		await flushPromises()
		expect(wrapper.find('[data-widget]').exists()).toBe(false)
		expect(wrapper.text()).not.toContain('Page A')
		expect(fetchTeamDashboardWidgets).toHaveBeenCalledTimes(1)
	})

	it('clears all content when the server revokes access on refresh', async () => {
		const { wrapper } = setup()
		await flushPromises()
		vi.mocked(fetchTeamDashboardItems).mockRejectedValue({ response: { status: 404 } })
		await refresh()
		expect(wrapper.find('[data-widget]').exists()).toBe(false)
		expect(wrapper.text()).toContain('Overview unavailable')
		expect(wrapper.text()).not.toContain('Customize dashboards')
	})

	it('isolates provider errors and retries the affected widget', async () => {
		vi.mocked(fetchTeamDashboardWidgets).mockResolvedValue([widget, { ...widget, id: 'broken' }])
		vi.mocked(fetchTeamDashboardItems).mockImplementation(async (_team, id) => id === 'broken' ? { status: 'error' } : response('Healthy'))
		const { wrapper } = setup()
		await flushPromises()
		expect(wrapper.get('[data-widget="pages"]').text()).toContain('Healthy')
		expect(wrapper.get('[data-widget="broken"]').text()).toContain('error')
		vi.mocked(fetchTeamDashboardItems).mockResolvedValue(response('Recovered'))
		await wrapper.get('[data-widget="broken"] button').trigger('click')
		await flushPromises()
		expect(wrapper.get('[data-widget="broken"]').text()).toContain('Recovered')
	})

	it('refreshes while active and cleans up on unmount', async () => {
		const { wrapper } = setup()
		await flushPromises()
		await vi.advanceTimersByTimeAsync(33_000)
		expect(fetchTeamDashboardItems).toHaveBeenCalledTimes(2)
		const signal = vi.mocked(fetchTeamDashboardItems).mock.calls[1]![2]!
		wrapper.unmount()
		await vi.advanceTimersByTimeAsync(66_000)
		document.dispatchEvent(new Event('visibilitychange'))
		await flushPromises()
		expect(signal.aborted).toBe(true)
		expect(fetchTeamDashboardItems).toHaveBeenCalledTimes(2)
	})

	it('skips refresh while hidden', async () => {
		vi.spyOn(document, 'hidden', 'get').mockReturnValue(true)
		setup()
		await flushPromises()
		await vi.advanceTimersByTimeAsync(33_000)
		expect(fetchTeamDashboardItems).toHaveBeenCalledTimes(1)
	})

	it('handles an unsupported endpoint and allows a full retry', async () => {
		vi.mocked(fetchTeamDashboardWidgets).mockRejectedValueOnce({ response: { status: 404 } })
		const { wrapper } = setup()
		await flushPromises()
		expect(wrapper.text()).toContain('Overview unavailable')
		expect(fetchTeamDashboardItems).not.toHaveBeenCalled()
		await wrapper.findAll('button').find((button) => button.text() === 'Try again')!.trigger('click')
		await flushPromises()
		expect(wrapper.text()).toContain('Page A')
	})
})
