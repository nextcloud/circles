/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type * as ReferenceApi from '@nextcloud/vue/functions/registerReference'
import type { SharedResource } from '../types.ts'

import { flushPromises, shallowMount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createApp, h, onUnmounted } from 'vue'
import TeamResourceView from './TeamResourceView.vue'
import { useTeamResourcesStore } from '../resourcesStore.ts'

const get = vi.hoisted(() => vi.fn())
const widgets = vi.hoisted(() => ({
	renderWidget: vi.fn(),
	destroyWidget: vi.fn(),
	isWidgetRegistered: vi.fn(),
}))
const logger = vi.hoisted(() => ({ error: vi.fn() }))
vi.mock('@nextcloud/axios', () => ({ default: { get } }))
vi.mock('@nextcloud/vue/functions/registerReference', () => widgets)
vi.mock('../../logger.ts', () => ({ logger }))
vi.mock('@nextcloud/l10n', () => ({ t: (_: string, text: string) => text }))
vi.mock('../api.ts', () => ({
	getTeamFolder: vi.fn(),
	upgradeTeamFolder: vi.fn(),
	fetchTeamPages: vi.fn(),
	fetchTeamResources: vi.fn(),
	fetchTeamDeckBoards: vi.fn(),
	fetchTabOrder: vi.fn(),
	saveTabOrder: vi.fn(),
}))

const board: SharedResource = { id: 7, label: 'Board', url: '/apps/deck/board/7', provider: { id: 'deck', name: 'Deck' } }
const task: SharedResource = { id: 'list-a', label: 'Tasks', url: '/apps/tasks/lists/a', provider: { id: 'tasks', name: 'Tasks' } }
const wrappers: ReturnType<typeof shallowMount>[] = []

function response(url = 'http://localhost/apps/deck/board/7', type = 'deck-board', accessible = true) {
	return { data: { ocs: { data: { references: {
		[url]: { richObjectType: type, richObject: { id: 7 }, accessible, openGraphObject: { link: url } },
	} } } } }
}

async function mountView(options: { loaded?: boolean, resources?: SharedResource[], providerId?: string, resourceId?: string } = {}) {
	const pinia = createPinia()
	const state = useTeamResourcesStore(pinia).slot('team-1')
	state.resourcesChecked = options.loaded ?? true
	state.boardsChecked = options.loaded ?? true
	state.resources = options.resources ?? [board, task]
	const wrapper = shallowMount(TeamResourceView, {
		props: { teamId: 'team-1', providerId: options.providerId ?? 'deck', resourceId: options.resourceId ?? '7' },
		global: {
			plugins: [pinia],
			stubs: {
				NcEmptyContent: { props: ['name', 'description'], template: '<div>{{ name }} {{ description }}<slot name="action" /></div>' },
				NcButton: { template: '<button><slot /></button>' },
			},
		},
	})
	wrappers.push(wrapper)
	await flushPromises()
	return { wrapper, state }
}

beforeEach(() => {
	vi.resetAllMocks()
	widgets.isWidgetRegistered.mockReturnValue(true)
	get.mockResolvedValue(response())
})

afterEach(() => {
	for (const wrapper of wrappers.splice(0)) {
		wrapper.unmount()
	}
})

describe('TeamResourceView', () => {
	it('resolves a confirmed team resource and renders its registered interactive widget', async () => {
		const { wrapper } = await mountView()
		expect(get).toHaveBeenCalledWith(expect.stringContaining('references/resolve'), expect.objectContaining({
			params: { reference: 'http://localhost/apps/deck/board/7' },
			signal: expect.any(AbortSignal),
		}))
		expect(widgets.renderWidget).toHaveBeenCalledWith(expect.any(HTMLElement), expect.objectContaining({
			richObjectType: 'deck-board',
			interactive: true,
		}))
		expect(wrapper.find('.team-resource__loading').exists()).toBe(false)
	})

	it('renders another app without any app-specific adapter', async () => {
		get.mockResolvedValue(response('http://localhost/apps/tasks/lists/a', 'tasks-list'))
		await mountView({ providerId: 'tasks', resourceId: 'list-a' })
		expect(widgets.renderWidget).toHaveBeenCalledWith(expect.any(HTMLElement), expect.objectContaining({
			richObjectType: 'tasks-list',
			interactive: true,
		}))
	})

	it('waits for team resources before resolving a URL', async () => {
		const { state } = await mountView({ loaded: false, resources: [] })
		expect(get).not.toHaveBeenCalled()
		state.resources = [board]
		state.resourcesChecked = true
		await flushPromises()
		expect(widgets.renderWidget).toHaveBeenCalledTimes(1)
	})

	it('rejects resources outside the team and provider-id collisions', async () => {
		const { wrapper } = await mountView({ providerId: 'tasks', resourceId: '7' })
		expect(wrapper.text()).toContain('Resource not available in this team')
		expect(get).not.toHaveBeenCalled()
	})

	it('shows failed team-resource loading explicitly', async () => {
		const { wrapper, state } = await mountView({ loaded: false, resources: [] })
		state.boardsChecked = true
		await flushPromises()
		expect(wrapper.text()).toContain('Team resources unavailable')
		expect(get).not.toHaveBeenCalled()
	})

	it.each([false, true])('does not render inaccessible or unregistered widgets (accessible=%s)', async (accessible) => {
		get.mockResolvedValue(response(undefined, 'deck-board', accessible))
		widgets.isWidgetRegistered.mockReturnValue(!accessible)
		const { wrapper } = await mountView()
		expect(wrapper.text()).toContain('Embedded view unavailable')
		expect(wrapper.text()).toContain('Open in app')
		expect(widgets.renderWidget).not.toHaveBeenCalled()
	})

	it('handles unresolved references without using an app-switch fallback', async () => {
		get.mockResolvedValue({ data: { ocs: { data: { references: {} } } } })
		const { wrapper } = await mountView()
		expect(wrapper.text()).toContain('Embedded view unavailable')
		expect(widgets.renderWidget).not.toHaveBeenCalled()
	})

	it('reports request failures and retries explicitly', async () => {
		get.mockRejectedValueOnce(new Error('Network failure'))
		const { wrapper } = await mountView()
		expect(wrapper.text()).toContain('Could not load the resource')
		expect(logger.error).toHaveBeenCalled()
		await wrapper.get('button').trigger('click')
		await flushPromises()
		expect(get).toHaveBeenCalledTimes(2)
		expect(widgets.renderWidget).toHaveBeenCalledTimes(1)
	})

	it('rejects non-HTTP resource URLs', async () => {
		const { wrapper } = await mountView({ resources: [{ ...board, url: 'javascript:alert(1)' }] })
		expect(wrapper.text()).toContain('Could not load the resource')
		expect(logger.error).toHaveBeenCalled()
		expect(get).not.toHaveBeenCalled()
	})

	it('destroys the widget using exactly the element passed to its renderer', async () => {
		const { wrapper } = await mountView()
		const element = widgets.renderWidget.mock.calls[0]![0]
		wrapper.unmount()
		expect(widgets.destroyWidget).toHaveBeenCalledWith('deck-board', element)
	})

	it('destroys the old widget before switching to another resource', async () => {
		const { wrapper } = await mountView()
		const element = widgets.renderWidget.mock.calls[0]![0]
		get.mockResolvedValue(response('http://localhost/apps/tasks/lists/a', 'tasks-list'))
		await wrapper.setProps({ providerId: 'tasks', resourceId: 'list-a' })
		await flushPromises()
		expect(widgets.destroyWidget).toHaveBeenCalledWith('deck-board', element)
		expect(widgets.renderWidget).toHaveBeenCalledTimes(2)
		expect(widgets.renderWidget.mock.calls[1]![0]).not.toBe(element)
	})

	it('cancels pending resolution and ignores late responses after unmount', async () => {
		let resolve!: (value: ReturnType<typeof response>) => void
		get.mockReturnValue(new Promise((done) => {
			resolve = done
		}))
		const { wrapper } = await mountView()
		const signal = get.mock.calls[0]![1].signal as AbortSignal
		wrapper.unmount()
		expect(signal.aborted).toBe(true)
		resolve(response())
		await flushPromises()
		expect(widgets.renderWidget).not.toHaveBeenCalled()
	})

	it('ignores a previous resource response after switching providers', async () => {
		let resolve!: (value: ReturnType<typeof response>) => void
		get.mockReturnValueOnce(new Promise((done) => {
			resolve = done
		}))
		const { wrapper } = await mountView()
		get.mockResolvedValue(response('http://localhost/apps/tasks/lists/a', 'tasks-list'))
		await wrapper.setProps({ providerId: 'tasks', resourceId: 'list-a' })
		await flushPromises()
		resolve(response())
		await flushPromises()
		expect(widgets.renderWidget).toHaveBeenCalledTimes(1)
		expect(widgets.renderWidget.mock.calls[0]![1].richObjectType).toBe('tasks-list')
	})

	it('unmounts when the resource is no longer shared with the team', async () => {
		const { wrapper, state } = await mountView()
		state.resources = []
		await flushPromises()
		expect(widgets.destroyWidget).toHaveBeenCalledTimes(1)
		expect(wrapper.text()).toContain('Resource not available in this team')
	})

	it('reports synchronous widget rendering errors', async () => {
		widgets.renderWidget.mockImplementationOnce(() => {
			throw new Error('Render failed')
		})
		const { wrapper } = await mountView()
		expect(wrapper.text()).toContain('Could not load the resource')
		expect(logger.error).toHaveBeenCalled()
	})

	it('mounts and disposes a Vue app through the real shared widget registry', async () => {
		const api = await vi.importActual<typeof ReferenceApi>('@nextcloud/vue/functions/registerReference')
		const disposed = vi.fn()
		const apps = new Map<HTMLElement, ReturnType<typeof createApp>>()
		api.registerWidget('teams-test-widget', (element) => {
			const app = createApp({
				setup() {
					onUnmounted(disposed)
					return () => h('p', 'Interactive resource')
				},
			})
			apps.set(element, app)
			app.mount(element)
		}, (element) => {
			apps.get(element)?.unmount()
			apps.delete(element)
		})
		widgets.isWidgetRegistered.mockImplementation(api.isWidgetRegistered)
		widgets.renderWidget.mockImplementation(api.renderWidget)
		widgets.destroyWidget.mockImplementation(api.destroyWidget)
		get.mockResolvedValue(response(undefined, 'teams-test-widget'))
		const { wrapper } = await mountView()
		expect(wrapper.text()).toContain('Interactive resource')
		wrapper.unmount()
		expect(disposed).toHaveBeenCalledTimes(1)
		expect(apps.size).toBe(0)
	})
})
