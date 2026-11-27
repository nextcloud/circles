/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import PageView from './PageView.vue'

const { ensurePages, createEditor, destroy, state } = vi.hoisted(() => ({
	ensurePages: vi.fn(),
	createEditor: vi.fn(),
	destroy: vi.fn(),
	state: {
		folder: { id: 1 },
		folderError: false,
		pagesError: false,
		pagesChecked: true,
		pages: [{ fileId: 42, title: 'Guide', filePath: '/Team/.system/teams/pages/Guide.md' }],
	},
}))
vi.mock('../resourcesStore.ts', () => ({
	useTeamResourcesStore: () => ({ ensurePages, forTeam: () => state }),
}))
vi.mock('../../logger.ts', () => ({ logger: { error: vi.fn() } }))

function mountPage(fileId = '42') {
	return mount(PageView, {
		props: { teamId: 'team', fileId },
		global: {
			stubs: {
				NcEmptyContent: { props: ['name'], template: '<div>{{ name }}</div>' },
				NcLoadingIcon: true,
				NcIconSvgWrapper: true,
			},
		},
	})
}

describe('embedded team Page editor', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		ensurePages.mockResolvedValue(undefined)
		createEditor.mockResolvedValue({ destroy })
		state.pagesError = false
		window.OCA = { ...window.OCA, Text: { createCollaborativeEditor: createEditor } }
	})

	it('opens the team-local file inside Circles and destroys the editor on unmount', async () => {
		const wrapper = mountPage()
		await flushPromises()
		expect(createEditor).toHaveBeenCalledExactlyOnceWith({
			el: wrapper.get('.page-view__editor').element,
			context: { type: 'file', id: 42 },
			filePath: '/Team/.system/teams/pages/Guide.md',
		})
		wrapper.unmount()
		expect(destroy).toHaveBeenCalledOnce()
	})

	it('refreshes missing deep links but never opens a file outside this team', async () => {
		const wrapper = mountPage('99')
		await flushPromises()
		expect(ensurePages).toHaveBeenCalledWith('team', true)
		expect(createEditor).not.toHaveBeenCalled()
		expect(wrapper.text()).toContain('This page no longer exists')
		wrapper.unmount()
	})

	it('destroys an editor whose initialization completes after unmount', async () => {
		let resolve!: (editor: { destroy(): void }) => void
		createEditor.mockImplementationOnce(() => new Promise((complete) => {
			resolve = complete
		}))
		const wrapper = mountPage()
		await flushPromises()
		wrapper.unmount()
		resolve({ destroy })
		await flushPromises()
		expect(destroy).toHaveBeenCalledOnce()
	})

	it('shows an explicit error when Text is unavailable', async () => {
		delete window.OCA.Text
		const wrapper = mountPage()
		await flushPromises()
		expect(wrapper.text()).toContain('The Text app is required')
		expect(createEditor).not.toHaveBeenCalled()
		wrapper.unmount()
	})
})
