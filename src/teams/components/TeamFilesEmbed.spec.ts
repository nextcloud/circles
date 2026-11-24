/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type * as TeamFilesView from '../services/teamFilesView.ts'

import { mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { ref } from 'vue'
import TeamFilesEmbed from './TeamFilesEmbed.vue'
import { SIDEBAR_HOST_KEY, TEAM_FILES_VIEW_ID } from '../services/teamFilesView.ts'

const registerTeamFilesView = vi.hoisted(() => vi.fn())
const removeTeamFilesView = vi.hoisted(() => vi.fn())

vi.mock('../services/teamFilesView.ts', async (importOriginal) => ({
	...await importOriginal<typeof TeamFilesView>(),
	registerTeamFilesView,
	removeTeamFilesView,
}))

describe('TeamFilesEmbed', () => {
	const destroy = vi.fn()
	const renderFilesApp = vi.fn(() => ({ destroy }))
	const sidebarHost = document.createElement('div')

	/**
	 * Mount the component with the app layout's sidebar host.
	 *
	 * @param mountPoint - The team folder mount point
	 */
	function mountEmbed(mountPoint = 'QA Team') {
		return mount(TeamFilesEmbed, {
			props: { mountPoint },
			global: { provide: { [SIDEBAR_HOST_KEY as symbol]: ref(sidebarHost) } },
		})
	}

	beforeEach(() => {
		vi.clearAllMocks()
		;(window as unknown as Record<string, unknown>).OCP = { Files: { renderFilesApp } }
	})

	afterEach(() => {
		Reflect.deleteProperty(window, 'OCP')
	})

	it('renders the file list of the team folder with the sidebar', () => {
		const wrapper = mountEmbed()

		expect(registerTeamFilesView).toHaveBeenCalledWith('QA Team')
		expect(renderFilesApp).toHaveBeenCalledWith(wrapper.element, TEAM_FILES_VIEW_ID, {
			rootDir: '/QA Team',
			sidebarEl: sidebarHost,
		})
	})

	it('removes the file list and its view when unmounted', () => {
		const wrapper = mountEmbed()
		removeTeamFilesView.mockClear()

		wrapper.unmount()

		expect(destroy).toHaveBeenCalledOnce()
		expect(removeTeamFilesView).toHaveBeenCalled()
	})

	it('renders the file list again for another team folder', async () => {
		const wrapper = mountEmbed()

		await wrapper.setProps({ mountPoint: 'Other Team' })

		expect(destroy).toHaveBeenCalledOnce()
		expect(registerTeamFilesView).toHaveBeenLastCalledWith('Other Team')
		expect(renderFilesApp).toHaveBeenCalledTimes(2)
	})

	it('renders nothing without the Files app', () => {
		Reflect.deleteProperty(window, 'OCP')

		mountEmbed()

		expect(registerTeamFilesView).not.toHaveBeenCalled()
	})

	it('removes the view again if the file list cannot be rendered', () => {
		renderFilesApp.mockImplementationOnce(() => {
			throw new Error('Only one embedded Files view can be active at a time')
		})
		removeTeamFilesView.mockClear()

		mountEmbed()

		expect(removeTeamFilesView).toHaveBeenCalled()
	})
})
