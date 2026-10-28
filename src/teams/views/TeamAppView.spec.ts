/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { flushPromises, shallowMount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import TeamAppView from './TeamAppView.vue'

const { mount, cleanup, getProvider } = vi.hoisted(() => ({ mount: vi.fn(), cleanup: vi.fn(), getProvider: vi.fn() }))
vi.mock('../integrations/registry.ts', () => ({ getTeamAppProvider: getProvider }))
vi.mock('../integrations/store.ts', () => ({
	useTeamAppsStore: () => ({
		providerReady: () => true,
		entries: () => [
			{ id: 'example:42', entryId: '42', providerId: 'example', label: 'Example', viewData: { path: '/Team/Example.md' } },
			{ id: 'example:43', entryId: '43', providerId: 'example', label: 'Other', viewData: { path: '/Team/Other.md' } },
		],
		context: () => ({ teamId: 'team', signal: new AbortController().signal }),
		errors: () => [],
	}),
}))

describe('generic embedded Teams view', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		getProvider.mockReturnValue({ id: 'example', mount })
		mount.mockResolvedValue(cleanup)
	})

	const props = { teamId: 'team', providerId: 'example', entryId: '42' }

	it('mounts through the provider and cleans up on navigation', async () => {
		const wrapper = shallowMount(TeamAppView, { props })
		await flushPromises()
		expect(mount).toHaveBeenCalledWith(expect.any(HTMLElement), expect.objectContaining({ teamId: 'team' }), expect.objectContaining({ id: '42', viewData: { path: '/Team/Example.md' } }))
		wrapper.unmount()
		expect(cleanup).toHaveBeenCalledTimes(1)
	})

	it('cleans up and remounts when navigating between embedded entries', async () => {
		const wrapper = shallowMount(TeamAppView, { props })
		await flushPromises()
		await wrapper.setProps({ entryId: '43' })
		await flushPromises()
		expect(cleanup).toHaveBeenCalledOnce()
		expect(mount).toHaveBeenCalledTimes(2)
		expect(mount).toHaveBeenLastCalledWith(expect.any(HTMLElement), expect.objectContaining({ teamId: 'team' }), { id: '43', label: 'Other', viewData: { path: '/Team/Other.md' } })
		wrapper.unmount()
		expect(cleanup).toHaveBeenCalledTimes(2)
	})

	it('cleans up a late mount after the view was already removed', async () => {
		let resolve!: (cleanup: () => void) => void
		mount.mockReturnValue(new Promise((done) => {
			resolve = done
		}))
		const wrapper = shallowMount(TeamAppView, { props })
		await flushPromises()
		wrapper.unmount()
		resolve(cleanup)
		await flushPromises()
		expect(cleanup).toHaveBeenCalledTimes(1)
	})

	it('shows unavailable integrations explicitly', async () => {
		getProvider.mockReturnValue(undefined)
		const wrapper = shallowMount(TeamAppView, { props })
		await flushPromises()
		expect(wrapper.findComponent({ name: 'NcEmptyContent' }).attributes('name')).toBe('Could not open the view')
		wrapper.unmount()
	})
})
