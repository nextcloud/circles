/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const dav = vi.hoisted(() => ({
	getDirectoryContents: vi.fn(),
	createDirectory: vi.fn(),
	putFileContents: vi.fn(),
	moveFile: vi.fn(),
	deleteFile: vi.fn(),
}))
vi.mock('@nextcloud/files', () => ({ FileType: { File: 'file' } }))
vi.mock('@nextcloud/files/dav', () => ({
	defaultRootPath: '/dav/files/alice',
	getClient: () => dav,
	getDefaultPropfind: () => '<propfind />',
	resultToNode: (entry: unknown) => entry,
}))

vi.mock('@nextcloud/axios', () => ({
	default: {
		post: vi.fn(),
		get: vi.fn(),
		put: vi.fn(),
		delete: vi.fn(),
	},
}))

vi.mock('@nextcloud/router', () => ({
	generateOcsUrl: (path: string) => `/ocs/${path}`,
}))

vi.mock('../logger.ts', () => ({
	logger: { error: vi.fn(), warn: vi.fn(), debug: vi.fn() },
}))

vi.mock('./team-page/models/constants.ts', () => ({
	SHARES_TYPES_MEMBER_MAP: {},
}))

vi.mock('./team-page/services/collaborationAutocompletion.js', () => ({
	getRecommendations: vi.fn(),
	getSuggestions: vi.fn(),
}))

const { createTeam, createTeamPage, fetchTeamPages, renameTeamPage, deleteTeamPage, fetchTeams, getTeamAppNavigations, joinTeam, resolveTeamReferences, saveNavigation, setTeamFavorite, reorderFavoriteTeams } = await import('./api.ts')

describe('native team Pages', () => {
	beforeEach(() => {
		Object.values(dav).forEach((mock) => mock.mockReset())
	})

	it('lists only markdown files, sorted by title, with team-local editor paths', async () => {
		dav.getDirectoryContents.mockResolvedValue({ data: [
			{ type: 'file', extension: '.md', basename: 'Zebra.md', fileid: 7 },
			{ type: 'file', extension: '.MD', basename: 'Alpha.MD', fileid: 8 },
			{ type: 'directory', extension: '.md', basename: 'Folder.md', fileid: 9 },
			{ type: 'file', extension: '.txt', basename: 'Notes.txt', fileid: 10 },
		] })
		await expect(fetchTeamPages('Team')).resolves.toEqual([
			{ fileId: 8, title: 'Alpha', filePath: '/Team/.system/teams/pages/Alpha.MD' },
			{ fileId: 7, title: 'Zebra', filePath: '/Team/.system/teams/pages/Zebra.md' },
		])
		expect(dav.getDirectoryContents).toHaveBeenCalledWith('/dav/files/alice/Team/.system/teams/pages', expect.objectContaining({ details: true }))
	})

	it('treats only a missing Pages directory as an empty list', async () => {
		dav.getDirectoryContents.mockRejectedValueOnce({ status: 404 }).mockRejectedValueOnce({ status: 403 })
		await expect(fetchTeamPages('Team')).resolves.toEqual([])
		await expect(fetchTeamPages('Team')).rejects.toEqual({ status: 403 })
	})

	it('creates the Pages directory and refuses to overwrite existing markdown files', async () => {
		dav.createDirectory.mockRejectedValue({ status: 405 })
		dav.putFileContents.mockResolvedValue(false)
		await expect(createTeamPage('Team', 'Handbook')).rejects.toMatchObject({ status: 412 })
		expect(dav.createDirectory).toHaveBeenCalledWith('/dav/files/alice/Team/.system/teams/pages', { recursive: true })
		expect(dav.putFileContents).toHaveBeenCalledWith('/dav/files/alice/Team/.system/teams/pages/Handbook.md', '', { overwrite: false })
		dav.putFileContents.mockResolvedValue(true)
		await expect(createTeamPage('Team', 'Handbook')).resolves.toBeUndefined()
	})

	it.each(['', '  ', '../Outside', 'dir\\Outside', 'Bad\u0000name'])('rejects invalid page title %j before DAV writes', async (name) => {
		await expect(createTeamPage('Team', name)).rejects.toThrow('Invalid page name')
		expect(dav.createDirectory).not.toHaveBeenCalled()
	})

	it('renames without overwrite and deletes the team-local markdown file', async () => {
		const page = { fileId: 42, title: 'Handbook', filePath: '/Team/.system/teams/pages/Handbook.md' }
		await renameTeamPage(page, 'Guide')
		expect(dav.moveFile).toHaveBeenCalledWith('/dav/files/alice/Team/.system/teams/pages/Handbook.md', '/dav/files/alice/Team/.system/teams/pages/Guide.md', { overwrite: false })
		await deleteTeamPage(page)
		expect(dav.deleteFile).toHaveBeenCalledWith('/dav/files/alice/Team/.system/teams/pages/Handbook.md')
	})
})

describe('resolveTeamReferences', () => {
	it('batches unique absolute local URLs without prefetching external links', async () => {
		const url = new URL('/f/42', window.location.href).href
		const signal = new AbortController().signal
		vi.mocked(axios.post).mockResolvedValueOnce({ data: { ocs: { data: { references: { [url]: null } } } } })

		await expect(resolveTeamReferences(['/f/42', url, 'https://example.net/f/42', 'javascript:void(0)'], signal))
			.resolves.toEqual({ [url]: null })
		expect(axios.post).toHaveBeenCalledWith('/ocs/references/resolve', { references: [url], limit: 1 }, expect.objectContaining({ signal }))
	})
})

describe('fetchTeams', () => {
	it('splits active members from discoverable teams', async () => {
		const circles = [
			{ id: 'owner-team', name: 'owner-team', displayName: 'Zebra owner', population: 3, initiator: { level: 9, status: 'Member' } },
			{ id: 'open-team', name: 'open-team', displayName: 'Bravo open', population: 10, initiator: null, config: 24 },
			{ id: 'closed-team', name: 'closed-team', displayName: 'Charlie closed', population: 7, initiator: null, config: 8 },
			{ id: 'pending-team', name: 'pending-team', displayName: 'Alpha pending', population: 5, initiator: { level: 0, status: 'Requesting' }, config: 88 },
			{ id: 'invited-team', name: 'invited-team', displayName: 'Echo invited', population: 2, initiator: { level: 0, status: 'Invited' } },
		]
		vi.mocked(axios.get)
			.mockResolvedValueOnce({ data: { ocs: { data: circles } } })
			.mockResolvedValueOnce({ data: { ocs: { data: [] } } })
			.mockResolvedValueOnce({ data: { ocs: { data: { circleIds: [] } } } })

		const overview = await fetchTeams()

		expect(overview.teams.map((team) => team.id)).toEqual(['owner-team', 'invited-team'])
		expect(overview.teams[0].myRole).toBe('owner')
		expect(overview.discoverableTeams.map((team) => team.id)).toEqual(['pending-team', 'open-team', 'closed-team'])
		expect(overview.discoverableTeams[0]).toMatchObject({ pending: true, canJoin: false, memberCount: 5 })
		expect(overview.discoverableTeams[1]).toMatchObject({ pending: false, canJoin: true, memberCount: 10 })
		expect(overview.discoverableTeams[2]).toMatchObject({ pending: false, canJoin: false, memberCount: 7 })
	})

	it('returns an empty list when the circles response carries no data', async () => {
		vi.mocked(axios.get)
			.mockResolvedValueOnce({ data: { ocs: {} } })
			.mockResolvedValueOnce({ data: { ocs: { data: [] } } })
			.mockResolvedValueOnce({ data: { ocs: { data: { circleIds: [] } } } })

		await expect(fetchTeams()).resolves.toEqual({ teams: [], discoverableTeams: [] })
	})

	it('uses fallback metadata for discoverable and member teams', async () => {
		const circles = [
			{ id: 'discoverable-defaults', name: 'Fallback discoverable', displayName: '', initiator: null },
			{ id: 'member-defaults', name: 'Fallback member', displayName: '', initiator: { level: 1, status: 'Member' } },
			{ id: 'member-preview-count', name: 'Preview member', displayName: 'Preview member', initiator: { level: 1, status: 'Member' } },
		]
		vi.mocked(axios.get)
			.mockResolvedValueOnce({ data: { ocs: { data: circles } } })
			.mockResolvedValueOnce({
				data: {
					ocs: {
						data: [{
							singleId: 'member-preview-count',
							members: [{ singleId: 'alice', userId: 'alice', displayName: 'Alice', type: 1 }],
							resources: [],
						}],
					},
				},
			})
			.mockResolvedValueOnce({ data: { ocs: { data: { circleIds: [] } } } })

		const overview = await fetchTeams()

		expect(overview.discoverableTeams).toMatchObject([{
			id: 'discoverable-defaults',
			displayName: 'Fallback discoverable',
			description: '',
			memberCount: 0,
			canJoin: false,
			pending: false,
		}])
		expect(overview.teams).toMatchObject([
			{ id: 'member-defaults', displayName: 'Fallback member', description: '', memberCount: 0 },
			{
				id: 'member-preview-count',
				displayName: 'Preview member',
				memberCount: 1,
				members: [{ id: 'alice', userId: 'alice', displayName: 'Alice', isUser: true, role: 'member' }],
			},
		])
	})
})

describe('joinTeam', () => {
	it('returns requested when the join requires approval', async () => {
		vi.mocked(axios.put).mockResolvedValueOnce({ data: { ocs: { data: { status: 'Requesting' } } } })

		await expect(joinTeam('team1')).resolves.toBe('requested')
		expect(axios.put).toHaveBeenCalledWith(expect.stringMatching(/\/join$/), {})
	})

	it('returns joined for an immediate membership', async () => {
		vi.mocked(axios.put).mockResolvedValueOnce({ data: { ocs: { data: { status: 'Member' } } } })

		await expect(joinTeam('team1')).resolves.toBe('joined')
		expect(axios.put).toHaveBeenCalledWith(expect.stringMatching(/\/join$/), {})
	})

	it('propagates a rejected join request', async () => {
		vi.mocked(axios.put).mockRejectedValueOnce(new Error('join failed'))

		await expect(joinTeam('team1')).rejects.toThrow('join failed')
	})
})

describe('createTeam', () => {
	beforeEach(() => {
		vi.mocked(axios.post).mockResolvedValue({
			data: { ocs: { data: { id: 'team1' } } },
		})
	})

	it('requests a team folder by default', async () => {
		await createTeam('Design')

		expect(axios.post).toHaveBeenCalledWith(
			'/ocs/apps/circles/circles',
			{ name: 'Design', createTeamFolder: true },
		)
	})

	it('can skip team folder creation', async () => {
		await createTeam('Design', false)

		expect(axios.post).toHaveBeenCalledWith(
			'/ocs/apps/circles/circles',
			{ name: 'Design', createTeamFolder: false },
		)
	})
})

describe('favorite teams API', () => {
	beforeEach(() => vi.resetAllMocks())

	it('returns the canonical favorite list after a toggle', async () => {
		vi.mocked(axios.put).mockResolvedValue({ data: { ocs: { data: { circleIds: ['A', 'B'] } } } })
		expect(await setTeamFavorite('B', true)).toEqual(['A', 'B'])
		expect(axios.put).toHaveBeenCalledWith(expect.stringContaining('/favorite'), { isFavorite: true })
	})

	it('includes the expected order and reads the canonical response', async () => {
		vi.mocked(axios.put).mockResolvedValue({ data: { ocs: { data: { circleIds: ['B', 'A'] } } } })
		expect(await reorderFavoriteTeams(['B', 'A'], ['A', 'B'])).toEqual(['B', 'A'])
		expect(axios.put).toHaveBeenCalledWith('/ocs/apps/circles/teams/favorites/order', {
			circleIds: ['B', 'A'],
			expectedCircleIds: ['A', 'B'],
		})
	})

	it('still loads teams when favorites cannot be read', async () => {
		vi.mocked(axios.get).mockResolvedValueOnce({ data: { ocs: { data: [{ id: 'A', name: 'A', displayName: 'A', initiator: { level: 1 } }] } } })
			.mockResolvedValueOnce({ data: { ocs: { data: [] } } })
			.mockRejectedValueOnce(new Error('favorites unavailable'))
		const overview = await fetchTeams()
		expect(overview.teams.map((team) => [team.id, team.isFavorite])).toEqual([['A', false]])
	})
})

describe('Team app navigation API', () => {
	beforeEach(() => vi.resetAllMocks())

	it('saves selected entry IDs in display order through the navigation endpoint', async () => {
		const order = ['home', 'deck-7', 'team-folder']
		vi.mocked(axios.put).mockResolvedValueOnce({ data: { ocs: { data: [] } } })

		await saveNavigation('team', order)

		expect(axios.put).toHaveBeenCalledExactlyOnceWith('/ocs/apps/circles/teams/{circleId}/apps/navigation', { order })
	})

	it('fetches all provider navigation in one team-scoped request', async () => {
		const result = { entries: [{ id: 'team-folder' }, { id: 'home' }], providers: { example: { entries: [], canAddResource: false } }, errors: ['broken'] }
		const signal = new AbortController().signal
		vi.mocked(axios.get).mockResolvedValueOnce({ data: { ocs: { data: result } } })

		await expect(getTeamAppNavigations('team', signal)).resolves.toEqual(result)
		expect(axios.get).toHaveBeenCalledExactlyOnceWith('/ocs/apps/circles/teams/{teamId}/apps/navigation', { signal })
	})
})
