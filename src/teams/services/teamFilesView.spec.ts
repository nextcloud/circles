/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getNavigation } from '@nextcloud/files'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { getRenderFilesApp, getTeamFolderContents, registerTeamFilesView, removeTeamFilesView, resolveTeamFolderPath, TEAM_FILES_VIEW_ID } from './teamFilesView.ts'

const getDirectoryContents = vi.hoisted(() => vi.fn())
const resultToNode = vi.hoisted(() => vi.fn((entry: { filename: string }) => ({ path: entry.filename })))

vi.mock('@nextcloud/files/dav', () => ({
	defaultRootPath: '/files/admin',
	getClient: () => ({ getDirectoryContents }),
	getDefaultPropfind: () => '<propfind />',
	resultToNode,
}))

describe('resolveTeamFolderPath', () => {
	it.each([
		['/', '/QA Team'],
		['', '/QA Team'],
		['/QA Team', '/QA Team'],
		['/QA Team/', '/QA Team'],
		['/QA Team/docs/specs', '/QA Team/docs/specs'],
		['/Other', '/QA Team'],
		['/QA Team2/docs', '/QA Team'],
		['/QA Team/../Other', '/QA Team'],
	])('resolves %j into the team folder', (path, expected) => {
		expect(resolveTeamFolderPath('QA Team', path)).toBe(expected)
	})
})

describe('getTeamFolderContents', () => {
	beforeEach(() => {
		getDirectoryContents.mockReset()
	})

	it('lists the requested folder within the team folder', async () => {
		getDirectoryContents.mockResolvedValue({
			data: [
				{ filename: '/files/admin/QA Team/docs' },
				{ filename: '/files/admin/QA Team/docs/a.md' },
				{ filename: '/files/admin/QA Team/docs/b.md' },
			],
		})
		const signal = new AbortController().signal

		const { folder, contents } = await getTeamFolderContents('QA Team', '/QA Team/docs', signal)

		expect(getDirectoryContents).toHaveBeenCalledWith('/files/admin/QA Team/docs', expect.objectContaining({ includeSelf: true, signal }))
		expect(folder).toEqual({ path: '/files/admin/QA Team/docs' })
		expect(contents).toHaveLength(2)
	})

	it('lists the team folder for paths outside of it', async () => {
		getDirectoryContents.mockResolvedValue({ data: [{ filename: '/files/admin/QA Team' }] })

		await getTeamFolderContents('QA Team', '/')

		expect(getDirectoryContents).toHaveBeenCalledWith('/files/admin/QA Team', expect.anything())
	})
})

describe('team files view registration', () => {
	afterEach(() => {
		removeTeamFilesView()
	})

	it('registers a hidden view for the team folder', () => {
		registerTeamFilesView('QA Team')

		const view = getNavigation().views.find((view) => view.id === TEAM_FILES_VIEW_ID)
		expect(view).toBeDefined()
		expect(view?.hidden).toBe(true)
	})

	it('replaces the view of a previously shown team', async () => {
		getDirectoryContents.mockResolvedValue({ data: [{ filename: '/files/admin/Other Team' }] })
		registerTeamFilesView('QA Team')
		registerTeamFilesView('Other Team')

		const views = getNavigation().views.filter((view) => view.id === TEAM_FILES_VIEW_ID)
		expect(views).toHaveLength(1)
		await views[0]!.getContents('/', { signal: new AbortController().signal })
		expect(getDirectoryContents).toHaveBeenCalledWith('/files/admin/Other Team', expect.anything())
	})

	it('ignores removing a view which is not registered', () => {
		expect(() => removeTeamFilesView()).not.toThrow()
	})
})

describe('getRenderFilesApp', () => {
	afterEach(() => {
		Reflect.deleteProperty(window, 'OCP')
	})

	it('is not available without the Files app', () => {
		expect(getRenderFilesApp()).toBeUndefined()
	})

	it('returns the API of the Files app', () => {
		const renderFilesApp = vi.fn()
		;(window as unknown as Record<string, unknown>).OCP = { Files: { renderFilesApp } }

		expect(getRenderFilesApp()).toBe(renderFilesApp)
	})
})
