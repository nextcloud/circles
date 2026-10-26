/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TeamReference } from '../teamAppIntegrations/types.ts'

import { flushPromises, mount, shallowMount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { reactive } from 'vue'
import { registerWidget } from '@nextcloud/vue/components/NcRichText'
import TeamAppView from './TeamAppView.vue'

const { initialState } = vi.hoisted(() => ({
	initialState: {
		errors: [] as string[],
		ready: true,
		entries: [] as Array<{ id: string, entryId: string, providerId: string, label: string, href: string, referenceUrl?: string, reference?: TeamReference }>,
	},
}))
const state = reactive(initialState)
vi.mock('../teamAppIntegrations/teamAppsStore.ts', () => ({
	useTeamAppsStore: () => ({
		providerReady: () => state.ready,
		entries: () => state.entries.map((entry) => ({ ...entry })),
		errors: () => state.errors,
	}),
}))
vi.mock('../teamAppIntegrations/providerRegistry.ts', () => ({ getTeamOwnedResourceProvider: (id: string) => id === 'example' ? { id, name: 'Example' } : undefined }))

const props = { teamId: 'team', providerId: 'example', entryId: '42' }
const reference: TeamReference = {
	richObjectType: 'file',
	richObject: { file: null },
	accessible: true,
	openGraphObject: { id: '42', name: 'Example', description: null, thumb: null, link: 'http://localhost/f/42' },
}
const entries: typeof initialState.entries = [
	{ id: 'example-42', entryId: '42', providerId: 'example', label: 'Example', href: '/f/42', referenceUrl: '/reference/42', reference },
	{ id: 'example-43', entryId: '43', providerId: 'example', label: 'Other', href: '/f/43', reference: { ...reference, openGraphObject: { ...reference.openGraphObject, id: '43' } } },
	{ id: 'example-44', entryId: '44', providerId: 'example', label: 'Link only', href: '/f/44' },
	{ id: 'example-45', entryId: '45', providerId: 'example', label: 'Direct link', href: '/f/45', reference: { ...reference, richObjectType: 'open-graph' } },
]

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

describe('Teams reference previews', () => {
	beforeEach(() => {
		vi.spyOn(document, 'readyState', 'get').mockReturnValue('complete')
		state.errors = []
		state.ready = true
		state.entries = entries.map((entry) => ({ ...entry }))
	})

	afterEach(() => {
		vi.restoreAllMocks()
		vi.unstubAllGlobals()
	})

	it('renders the resolved reference interactively alongside the Open link', () => {
		const wrapper = mountView()
		const toolbar = wrapper.find('.team-app-view__toolbar')
		const openButton = toolbar.findComponent({ name: 'NcButton' })
		const widget = wrapper.findComponent({ name: 'NcReferenceWidget' })
		expect(toolbar.exists()).toBe(true)
		expect(openButton.attributes('href')).toBe('/f/42')
		expect(openButton.attributes('target')).toBe('_blank')
		expect(openButton.text()).toBe('Open Example')
		expect(openButton.findComponent({ name: 'NcIconSvgWrapper' }).exists()).toBe(true)
		expect(toolbar.element.compareDocumentPosition(widget.element) & Node.DOCUMENT_POSITION_FOLLOWING).toBeTruthy()
		expect(widget.props()).toMatchObject({
			reference,
			interactive: true,
			interactiveOptIn: false,
		})
		wrapper.unmount()
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
		state.entries[0]!.reference = { ...reference, richObjectType: 'teams-test-widget' }
		const wrapper = mount(TeamAppView, { props, global: { stubs: { NcButton: true, NcEmptyContent: true, NcLoadingIcon: true } } })
		await flushPromises()
		expect(render).toHaveBeenCalledOnce()
		expect(render.mock.calls[0]![1]).toMatchObject({ interactive: true, accessible: true })
		wrapper.unmount()
		expect(destroy).toHaveBeenCalledOnce()
	})

	it('keeps the existing widget mounted when navigation entries are refreshed unchanged', async () => {
		const wrapper = mountView()
		const list = wrapper.findComponent({ name: 'NcReferenceWidget' }).vm
		state.entries = entries.map((entry) => ({ ...entry }))
		await flushPromises()
		expect(wrapper.findComponent({ name: 'NcReferenceWidget' }).vm).toBe(list)
		wrapper.unmount()
	})

	it('remounts the widget when the reference URL changes', async () => {
		const wrapper = mountView()
		const list = wrapper.findComponent({ name: 'NcReferenceWidget' }).vm
		state.entries[0]!.referenceUrl = '/reference/new'
		await flushPromises()
		expect(wrapper.findComponent({ name: 'NcReferenceWidget' }).vm).not.toBe(list)
		expect(wrapper.findComponent({ name: 'NcButton' }).attributes('href')).toBe('/f/42')
		wrapper.unmount()
	})

	it('uses the resolved reference for the currently selected entry', async () => {
		const wrapper = mountView()
		await wrapper.setProps({ entryId: '43' })
		expect(wrapper.findComponent({ name: 'NcReferenceWidget' }).props('reference').openGraphObject.id).toBe('43')
		expect(wrapper.findComponent({ name: 'NcButton' }).attributes('href')).toBe('/f/43')
		wrapper.unmount()
	})

	it('waits for deferred reference scripts before mounting the widget', async () => {
		vi.spyOn(document, 'readyState', 'get').mockReturnValue('interactive')
		const wrapper = mountView()
		expect(wrapper.findComponent({ name: 'NcReferenceWidget' }).exists()).toBe(false)
		expect(wrapper.findComponent({ name: 'NcLoadingIcon' }).exists()).toBe(true)
		document.dispatchEvent(new Event('DOMContentLoaded'))
		await flushPromises()
		expect(wrapper.findComponent({ name: 'NcReferenceWidget' }).exists()).toBe(true)
		wrapper.unmount()
	})

	it('also handles a late mount after DOMContentLoaded', async () => {
		vi.spyOn(document, 'readyState', 'get').mockReturnValue('interactive')
		const wrapper = mountView()
		window.dispatchEvent(new Event('load'))
		await flushPromises()
		expect(wrapper.findComponent({ name: 'NcReferenceWidget' }).exists()).toBe(true)
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

	it('keeps link-only entries usable without mounting a widget', async () => {
		const wrapper = mountView()
		await wrapper.setProps({ entryId: '44' })
		expect(wrapper.findComponent({ name: 'NcReferenceWidget' }).exists()).toBe(false)
		expect(wrapper.findComponent({ name: 'NcButton' }).attributes('href')).toBe('/f/44')
		expect(wrapper.findComponent({ name: 'NcButton' }).text()).toBe('Open Link only')
		wrapper.unmount()
	})

	it('keeps generic Open Graph references as plain links', async () => {
		const wrapper = mountView()
		await wrapper.setProps({ entryId: '45' })
		expect(wrapper.findComponent({ name: 'NcReferenceWidget' }).exists()).toBe(false)
		expect(wrapper.findComponent({ name: 'NcButton' }).attributes('href')).toBe('/f/45')
		wrapper.unmount()
	})

	it('waits while a known provider is loading', () => {
		state.ready = false
		const wrapper = mountView()
		expect(wrapper.findComponent({ name: 'NcLoadingIcon' }).exists()).toBe(true)
		expect(wrapper.findComponent({ name: 'NcReferenceWidget' }).exists()).toBe(false)
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
		expect(wrapper.findComponent({ name: 'NcReferenceWidget' }).exists()).toBe(false)
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
