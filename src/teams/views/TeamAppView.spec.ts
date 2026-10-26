/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type * as NextcloudAuth from '@nextcloud/auth'

import { flushPromises, mount, shallowMount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { reactive } from 'vue'
import { registerWidget } from '@nextcloud/vue/components/NcRichText'
import TeamAppView from './TeamAppView.vue'

const { get, initialState } = vi.hoisted(() => ({
	get: vi.fn(),
	initialState: {
		errors: [] as string[],
		ready: true,
		entries: [] as Array<{ id: string, entryId: string, providerId: string, label: string, href: string, referenceUrl?: string, openMode?: 'link' | 'reference' | null }>,
	},
}))
const state = reactive(initialState)
vi.mock('@nextcloud/axios', () => ({ default: { get } }))
vi.mock('@nextcloud/auth', async (original) => ({
	...await original<typeof NextcloudAuth>(),
	getCurrentUser: () => ({ uid: 'user' }),
}))
vi.mock('../integrations/store.ts', () => ({
	useTeamAppsStore: () => ({
		providerReady: () => state.ready,
		entries: () => state.entries.map((entry) => ({ ...entry })),
		errors: () => state.errors,
	}),
}))
vi.mock('../integrations/registry.ts', () => ({ getTeamAppProvider: (id: string) => id === 'example' ? { id, name: 'Example', order: 1 } : undefined }))

const props = { teamId: 'team', providerId: 'example', entryId: '42' }
const entries: typeof initialState.entries = [
	{ id: 'example-42', entryId: '42', providerId: 'example', label: 'Example', href: '/f/42', referenceUrl: 'http://localhost/f/42' },
	{ id: 'example-43', entryId: '43', providerId: 'example', label: 'Other', href: '/f/43', referenceUrl: 'http://localhost/f/43' },
	{ id: 'example-44', entryId: '44', providerId: 'example', label: 'Link only', href: '/f/44' },
	{ id: 'example-45', entryId: '45', providerId: 'example', label: 'Direct link', href: '/f/45', referenceUrl: 'http://localhost/f/45', openMode: 'link' },
]
const reference = {
	richObjectType: 'file',
	richObject: { file: null },
	accessible: true,
	openGraphObject: { id: '42', name: 'Example', description: null, thumb: null, link: 'http://localhost/f/42' },
}

function mountView() {
	return shallowMount(TeamAppView, {
		props,
		global: {
			stubs: {
				NcButton: { name: 'NcButton', props: ['href', 'target'], template: '<a :href="href" :target="target"><slot name="icon" /><slot /></a>' },
			},
		},
	})
}

function mountWithReferenceList() {
	return mount(TeamAppView, {
		props,
		global: {
			stubs: {
				NcButton: true,
				NcEmptyContent: true,
				NcLoadingIcon: true,
				NcReferenceWidget: { name: 'NcReferenceWidget', props: ['reference', 'interactive', 'interactiveOptIn'], template: '<div />' },
			},
		},
	})
}

describe('Teams reference previews', () => {
	beforeEach(() => {
		vi.spyOn(document, 'readyState', 'get').mockReturnValue('complete')
		state.errors = []
		state.ready = true
		state.entries = entries.map((entry) => ({ ...entry }))
		get.mockReset()
	})

	afterEach(() => {
		vi.restoreAllMocks()
		vi.unstubAllGlobals()
	})

	it('opens the canonical URL interactively with the Text editor fallback and an Open link', () => {
		const wrapper = mountView()
		const toolbar = wrapper.find('.team-app-view__toolbar')
		const openButton = toolbar.findComponent({ name: 'NcButton' })
		const referenceList = wrapper.findComponent({ name: 'NcReferenceList' })
		expect(toolbar.exists()).toBe(true)
		expect(openButton.attributes('href')).toBe('/f/42')
		expect(openButton.attributes('target')).toBe('_blank')
		expect(openButton.text()).toBe('Open Example')
		expect(openButton.findComponent({ name: 'NcIconSvgWrapper' }).exists()).toBe(true)
		expect(toolbar.element.compareDocumentPosition(referenceList.element) & Node.DOCUMENT_POSITION_FOLLOWING).toBeTruthy()
		expect(wrapper.findComponent({ name: 'NcReferenceList' }).props()).toMatchObject({
			text: 'http://localhost/f/42',
			limit: 1,
			interactive: true,
			interactiveOptIn: false,
			displayFallback: true,
		})
		expect(wrapper.findComponent({ name: 'NcButton' }).attributes('href')).toBe('/f/42')
		wrapper.unmount()
	})

	it('uses the real reference list to resolve the opened URL and pass interactive mode to the widget', async () => {
		get.mockResolvedValue({ data: { ocs: { data: { references: { 'http://localhost/f/42': reference } } } } })
		const wrapper = mountWithReferenceList()
		await flushPromises()
		expect(get).toHaveBeenCalledOnce()
		expect(get.mock.calls[0]![0]).toContain(`reference=${encodeURIComponent('http://localhost/f/42')}`)
		expect(wrapper.findComponent({ name: 'NcReferenceWidget' }).props()).toMatchObject({
			reference,
			interactive: true,
			interactiveOptIn: false,
		})
		wrapper.unmount()
	})

	it('keeps the Open link when no reference is resolved', async () => {
		get.mockResolvedValue({ data: { ocs: { data: { references: { 'http://localhost/f/42': null } } } } })
		const wrapper = mountWithReferenceList()
		await flushPromises()
		expect(wrapper.findComponent({ name: 'NcReferenceWidget' }).exists()).toBe(false)
		expect(wrapper.findComponent({ name: 'NcButton' }).attributes('href')).toBe('/f/42')
		wrapper.unmount()
	})

	it('uses the shared fallback after a failed resolution', async () => {
		get.mockRejectedValue(new Error('offline'))
		const log = vi.spyOn(console, 'error').mockImplementation(() => {})
		const wrapper = mountWithReferenceList()
		await flushPromises()
		expect(wrapper.findComponent({ name: 'NcReferenceWidget' }).props('reference').richObjectType).toBe('open-graph')
		expect(wrapper.findComponent({ name: 'NcButton' }).attributes('href')).toBe('/f/42')
		wrapper.unmount()
		log.mockRestore()
	})

	it('renders a registered widget interactively and destroys it on unmount', async () => {
		const render = vi.fn()
		const destroy = vi.fn()
		registerWidget('teams-test-widget', render, destroy)
		vi.stubGlobal('IntersectionObserver', class implements IntersectionObserver {
			readonly root = null
			readonly rootMargin = '0px'
			readonly scrollMargin = '0px'
			readonly thresholds = [0]

			constructor(private callback: IntersectionObserverCallback) {}

			observe(target: Element) {
				const rect = new DOMRect()
				this.callback([{ target, isIntersecting: true, intersectionRatio: 1, boundingClientRect: rect, intersectionRect: rect, rootBounds: null, time: 0 }], this)
			}

			unobserve() {}

			disconnect() {}

			takeRecords(): IntersectionObserverEntry[] { return [] }
		})
		get.mockResolvedValue({ data: { ocs: { data: { references: {
			'http://localhost/f/42': { ...reference, richObjectType: 'teams-test-widget' },
		} } } } })
		const wrapper = mount(TeamAppView, { props, global: { stubs: { NcButton: true, NcEmptyContent: true, NcLoadingIcon: true } } })
		await flushPromises()
		expect(render).toHaveBeenCalledOnce()
		expect(render.mock.calls[0]![1]).toMatchObject({ interactive: true, accessible: true })
		wrapper.unmount()
		expect(destroy).toHaveBeenCalledOnce()
	})

	it('keeps the existing list mounted when navigation entries are refreshed unchanged', async () => {
		const wrapper = mountView()
		const list = wrapper.findComponent({ name: 'NcReferenceList' }).vm
		state.entries = entries.map((entry) => ({ ...entry }))
		await flushPromises()
		expect(wrapper.findComponent({ name: 'NcReferenceList' }).vm).toBe(list)
		wrapper.unmount()
	})

	it('remounts the list when the reference URL changes', async () => {
		const wrapper = mountView()
		const list = wrapper.findComponent({ name: 'NcReferenceList' }).vm
		state.entries[0]!.referenceUrl = 'http://localhost/f/new'
		await flushPromises()
		expect(wrapper.findComponent({ name: 'NcReferenceList' }).vm).not.toBe(list)
		expect(wrapper.findComponent({ name: 'NcReferenceList' }).props('text')).toBe('http://localhost/f/new')
		wrapper.unmount()
	})

	it('does not display a late result after switching entries', async () => {
		let resolveFirst!: (value: object) => void
		get.mockReturnValueOnce(new Promise((resolve) => {
			resolveFirst = resolve
		}))
		get.mockResolvedValueOnce({ data: { ocs: { data: { references: {
			'http://localhost/f/43': { ...reference, openGraphObject: { ...reference.openGraphObject, id: '43' } },
		} } } } })
		const wrapper = mountWithReferenceList()
		await wrapper.setProps({ entryId: '43' })
		await flushPromises()
		resolveFirst({ data: { ocs: { data: { references: { 'http://localhost/f/42': reference } } } } })
		await flushPromises()
		expect(wrapper.findComponent({ name: 'NcReferenceWidget' }).props('reference').openGraphObject.id).toBe('43')
		expect(wrapper.findComponent({ name: 'NcButton' }).attributes('href')).toBe('/f/43')
		wrapper.unmount()
	})

	it('waits for deferred reference scripts before mounting the list', async () => {
		vi.spyOn(document, 'readyState', 'get').mockReturnValue('interactive')
		const wrapper = mountView()
		expect(wrapper.findComponent({ name: 'NcReferenceList' }).exists()).toBe(false)
		expect(wrapper.findComponent({ name: 'NcLoadingIcon' }).exists()).toBe(true)
		document.dispatchEvent(new Event('DOMContentLoaded'))
		await flushPromises()
		expect(wrapper.findComponent({ name: 'NcReferenceList' }).exists()).toBe(true)
		wrapper.unmount()
	})

	it('also handles a late mount after DOMContentLoaded', async () => {
		vi.spyOn(document, 'readyState', 'get').mockReturnValue('interactive')
		const wrapper = mountView()
		window.dispatchEvent(new Event('load'))
		await flushPromises()
		expect(wrapper.findComponent({ name: 'NcReferenceList' }).exists()).toBe(true)
		wrapper.unmount()
	})

	it('removes deferred-script listeners when the view is unmounted before readiness', () => {
		vi.spyOn(document, 'readyState', 'get').mockReturnValue('loading')
		const listen = vi.spyOn(document, 'addEventListener')
		const wrapper = mountView()
		const options = listen.mock.calls.find(([event]) => event === 'DOMContentLoaded')![2] as AddEventListenerOptions
		expect(options.signal!.aborted).toBe(false)
		wrapper.unmount()
		expect(options.signal!.aborted).toBe(true)
	})

	it('keeps link-only entries usable without mounting a reference list', async () => {
		const wrapper = mountView()
		await wrapper.setProps({ entryId: '44' })
		expect(wrapper.findComponent({ name: 'NcReferenceList' }).exists()).toBe(false)
		expect(wrapper.findComponent({ name: 'NcButton' }).attributes('href')).toBe('/f/44')
		expect(wrapper.findComponent({ name: 'NcButton' }).text()).toBe('Open Link only')
		wrapper.unmount()
	})

	it('opens link-mode entries directly even when they have a reference URL', async () => {
		const wrapper = mountView()
		await wrapper.setProps({ entryId: '45' })
		expect(wrapper.findComponent({ name: 'NcReferenceList' }).exists()).toBe(false)
		expect(wrapper.findComponent({ name: 'NcButton' }).attributes('href')).toBe('/f/45')
		wrapper.unmount()
	})

	it('waits while a known provider is loading', () => {
		state.ready = false
		const wrapper = mountView()
		expect(wrapper.findComponent({ name: 'NcLoadingIcon' }).exists()).toBe(true)
		expect(wrapper.findComponent({ name: 'NcReferenceList' }).exists()).toBe(false)
		wrapper.unmount()
	})

	it('reports unavailable providers instead of leaving a spinner', () => {
		state.errors = ['example']
		state.ready = false
		const wrapper = mountView()
		expect(wrapper.findComponent({ name: 'NcEmptyContent' }).exists()).toBe(true)
		expect(wrapper.findComponent({ name: 'NcLoadingIcon' }).exists()).toBe(false)
		wrapper.unmount()
	})

	it.each(['42', '44', '45'])('hides cached links and previews while entry %s has a provider error', async (entryId) => {
		const wrapper = mountView()
		await wrapper.setProps({ entryId })
		state.errors = ['example']
		await flushPromises()
		expect(wrapper.findComponent({ name: 'NcEmptyContent' }).exists()).toBe(true)
		expect(wrapper.findComponent({ name: 'NcButton' }).exists()).toBe(false)
		expect(wrapper.findComponent({ name: 'NcReferenceList' }).exists()).toBe(false)
		state.errors = []
		await flushPromises()
		expect(wrapper.findComponent({ name: 'NcEmptyContent' }).exists()).toBe(false)
		expect(wrapper.findComponent({ name: 'NcButton' }).attributes('href')).toBe(`/f/${entryId}`)
		wrapper.unmount()
	})

	it('reports unknown deep-linked providers and missing entries', async () => {
		state.ready = false
		const wrapper = mountView()
		await wrapper.setProps({ providerId: 'missing' })
		expect(wrapper.findComponent({ name: 'NcEmptyContent' }).exists()).toBe(true)
		state.ready = true
		await wrapper.setProps({ providerId: 'example', entryId: 'missing' })
		expect(wrapper.findComponent({ name: 'NcEmptyContent' }).exists()).toBe(true)
		wrapper.unmount()
	})
})
