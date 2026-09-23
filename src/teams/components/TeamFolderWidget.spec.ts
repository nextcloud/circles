/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { flushPromises, shallowMount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import TeamFolderWidget from './TeamFolderWidget.vue'

const getDirectoryContents = vi.hoisted(() => vi.fn(async (): Promise<unknown[]> => []))

vi.mock('@nextcloud/files', () => ({
	FileType: { File: 'file', Folder: 'folder' },
	formatFileSize: (size: number) => String(size),
}))
vi.mock('@nextcloud/files/dav', () => ({
	defaultRootPath: '/files/admin',
	getClient: () => ({ getDirectoryContents }),
	getDefaultPropfind: () => '',
	resultToNode: (entry: unknown) => entry,
}))
vi.mock('@nextcloud/router', () => ({
	generateUrl: (url: string, params: Record<string, unknown> = {}) => {
		return '/index.php' + url.replace(/\{(\w+)\}/g, (_, key: string) => String(params[key]))
	},
}))
// @nextcloud/vue pulls RTL helpers from the same module, so keep the rest.
vi.mock('@nextcloud/l10n', async (importOriginal) => ({
	...(await importOriginal<object>()),
	t: (app: string, text: string) => text,
}))
vi.mock('@nextcloud/dialogs', () => ({ showError: vi.fn() }))
vi.mock('../../logger.ts', () => ({ logger: { error: vi.fn() } }))

/**
 * Href of the header button that opens the current folder in the Files app.
 *
 * @param wrapper - The mounted widget
 */
function headerLink(wrapper: ReturnType<typeof shallowMount>): string {
	const button = wrapper.findAllComponents({ name: 'NcButton' })
		.find((candidate) => candidate.props('href') !== undefined)

	return String(button?.props('href'))
}

describe('TeamFolderWidget header link', () => {
	beforeEach(() => {
		getDirectoryContents.mockReset()
		getDirectoryContents.mockResolvedValue([])
	})

	it('addresses the folder by path, on the files view', async () => {
		const wrapper = shallowMount(TeamFolderWidget, { props: { mountPoint: 'QA Team' } })
		await flushPromises()

		expect(headerLink(wrapper)).toBe('/index.php/apps/files/files?dir=/QA%20Team')
	})

	it('carries no file id from the response that could disagree with the path', async () => {
		getDirectoryContents.mockResolvedValue([
			{ fileid: 29875, basename: 'QA Team', displayname: 'QA Team', type: 'folder', source: 'self' },
		])
		const wrapper = shallowMount(TeamFolderWidget, { props: { mountPoint: 'QA Team' } })
		await flushPromises()

		expect(headerLink(wrapper)).not.toMatch(/\/files\/\d+/)
	})

	// The path changes synchronously while the PROPFIND for the new folder is
	// still in flight, so anything derived from that response would lag behind.
	it('points at the subfolder before its contents have loaded', async () => {
		const wrapper = shallowMount(TeamFolderWidget, { props: { mountPoint: 'QA Team' } })
		await flushPromises()

		getDirectoryContents.mockReturnValue(new Promise(() => {}))
		await wrapper.setProps({ folderPath: 'Docs' })

		expect(headerLink(wrapper)).toBe('/index.php/apps/files/files?dir=/QA%20Team/Docs')
	})

	it('points at the subfolder even when loading it fails', async () => {
		const wrapper = shallowMount(TeamFolderWidget, { props: { mountPoint: 'QA Team' } })
		await flushPromises()

		getDirectoryContents.mockRejectedValue(new Error('offline'))
		await wrapper.setProps({ folderPath: 'Docs' })
		await flushPromises()

		expect(headerLink(wrapper)).toBe('/index.php/apps/files/files?dir=/QA%20Team/Docs')
	})
})

describe('TeamFolderWidget contents', () => {
	beforeEach(() => {
		getDirectoryContents.mockReset()
		// webdav filters the folder itself out of the response unless asked for
		// it, so every entry here is a child that has to show up.
		getDirectoryContents.mockResolvedValue([
			{ fileid: 2, basename: 'Docs', displayname: 'Docs', type: 'folder', source: 'd', attributes: {} },
			{ fileid: 1, basename: 'readme.txt', displayname: 'readme.txt', type: 'file', source: 'r', mime: 'text/plain', attributes: {} },
			{ fileid: 3, basename: '.system', displayname: '.system', type: 'folder', source: 's', attributes: {} },
		])
	})

	it('lists every child, folders first, hidden entries dropped', async () => {
		const wrapper = shallowMount(TeamFolderWidget, { props: { mountPoint: 'QA Team' } })
		await flushPromises()

		const names = wrapper.findAll('.team-folder-widget__name-base').map((node) => node.text())
		expect(names).toStrictEqual(['Docs', 'readme'])
	})
})
