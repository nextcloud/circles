/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Member, MemberCandidate, Resource, SharedResource, Team, TeamRole } from './types.ts'

import axios from '@nextcloud/axios'
import { FileType } from '@nextcloud/files'
import { defaultRootPath, getClient, getDefaultPropfind, resultToNode } from '@nextcloud/files/dav'
import { generateOcsUrl } from '@nextcloud/router'
import { logger } from '../logger.ts'
import { SHARES_TYPES_MEMBER_MAP } from './team-page/models/constants.ts'
import { getRecommendations, getSuggestions } from './team-page/services/collaborationAutocompletion.js'

/** `SHARES_TYPES_MEMBER_MAP` is built dynamically, so type its shape explicitly. */
const shareTypeToMemberType = SHARES_TYPES_MEMBER_MAP as Record<number, number>

/** Minimal shape of an OCS response envelope. */
interface OcsResponse<T> {
	ocs: { data: T }
}

/** Raw member as returned by the circles API. */
interface RawMember {
	singleId: string
	userId: string | null
	displayName: string
	userType?: number
	type?: number
	level?: number
}

/** Raw resource as returned by the dashboard endpoint. */
interface RawResource {
	id: string | number
	name: string
	type: string
	iconUrl: string
	fallbackIcon: string
	url: string
}

/** Raw circle as returned by the `/circles` endpoint. */
interface RawCircle {
	id: string
	name: string
	displayName: string
	description?: string
	population?: number
	initiator?: { level?: number }
}

/** Raw team as returned by the dashboard widget endpoint. */
interface RawDashboardTeam {
	singleId: string
	members: RawMember[]
	resources: RawResource[]
}

/** Raw sharee suggestion, as returned by the files_sharing autocompletion helper. */
interface RawSuggestion {
	id: string
	label: string
	shareWith: string
	shareType: number
	user?: string | null
}

/**
 * Map a circles member level to a role.
 *
 * @param level - The circles member level (9 owner, 8 admin, 4 moderator, …)
 */
function levelToRole(level: number | undefined): TeamRole {
	if (level === undefined) {
		return 'member'
	}
	if (level >= 9) {
		return 'owner'
	}
	if (level >= 8) {
		return 'admin'
	}
	if (level >= 4) {
		return 'moderator'
	}
	return 'member'
}

/**
 * Map a resource from the dashboard endpoint to our type.
 *
 * @param raw - The raw resource
 */
function mapResource(raw: RawResource): Resource {
	return {
		id: String(raw.id),
		name: raw.name,
		type: raw.type === 'folder' ? 'folder' : 'file',
		iconUrl: raw.iconUrl,
		fallbackIcon: raw.fallbackIcon,
		url: raw.url,
	}
}

/**
 * Map a preview member (from the dashboard endpoint, no level) to our type.
 *
 * @param raw - The raw member
 */
function mapPreviewMember(raw: RawMember): Member {
	return {
		id: raw.singleId,
		userId: raw.userId ?? null,
		displayName: raw.displayName,
		isUser: raw.type === 1,
		role: 'member',
	}
}

/**
 * Map a full member (from the members endpoint, includes level) to our type.
 *
 * @param raw - The raw member
 */
function mapFullMember(raw: RawMember): Member {
	return {
		id: raw.singleId,
		userId: raw.userId ?? null,
		displayName: raw.displayName,
		isUser: raw.userType === 1,
		role: levelToRole(raw.level),
	}
}

/**
 * Fetch all of the current user's teams, merging team metadata (name,
 * description, member count, our role) with the members preview and resources.
 */
export async function fetchTeams(): Promise<Team[]> {
	const [circlesRes, dashRes] = await Promise.allSettled([
		axios.get<OcsResponse<RawCircle[]>>(generateOcsUrl('apps/circles/circles') + '?limit=-1'),
		axios.get<OcsResponse<RawDashboardTeam[]>>(generateOcsUrl('apps/circles/teams/dashboard/widget') + '?limit=200&offset=0'),
	])

	// The team list is required; without it we have nothing to show.
	if (circlesRes.status === 'rejected') {
		throw circlesRes.reason
	}
	const circles = circlesRes.value.data.ocs.data ?? []

	// The dashboard only enriches each team with member/resource previews, so
	// treat a failure there as "no previews" rather than failing the whole page.
	let dashboard: RawDashboardTeam[] = []
	if (dashRes.status === 'fulfilled') {
		dashboard = dashRes.value.data.ocs.data ?? []
	} else {
		logger.warn('Failed to load team dashboard previews', { error: dashRes.reason })
	}
	const dashboardById = new Map(dashboard.map((team) => [team.singleId, team]))

	return circles.map((circle) => {
		const extra = dashboardById.get(circle.id)
		return {
			id: circle.id,
			displayName: circle.displayName || circle.name,
			description: circle.description ?? '',
			memberCount: circle.population ?? extra?.members.length ?? 0,
			myRole: levelToRole(circle.initiator?.level),
			members: (extra?.members ?? []).map(mapPreviewMember),
			resources: (extra?.resources ?? []).map(mapResource),
		}
	})
}

/**
 * Fetch the full member list for a team (includes roles).
 *
 * @param teamId - The team single id
 */
export async function fetchTeamMembers(teamId: string): Promise<Member[]> {
	const res = await axios.get<OcsResponse<RawMember[]>>(generateOcsUrl('apps/circles/circles/{circleId}/members', { circleId: teamId }))
	return (res.data.ocs.data ?? []).map(mapFullMember)
}

/**
 * Create a team and return its single id.
 *
 * @param name - The team name
 * @param createTeamFolder - Whether to auto-create a team space. Defaults to
 * true so existing API callers keep the previous behaviour.
 */
export async function createTeam(name: string, createTeamFolder = true): Promise<string> {
	const res = await axios.post<OcsResponse<RawCircle>>(
		generateOcsUrl('apps/circles/circles'),
		{ name, createTeamFolder },
	)
	return res.data.ocs.data.id
}

/**
 * Set a team's description.
 *
 * @param teamId - The team single id
 * @param description - The new description
 */
export async function setTeamDescription(teamId: string, description: string): Promise<void> {
	await axios.put(
		generateOcsUrl('apps/circles/circles/{circleId}/description', { circleId: teamId }),
		{ value: description },
	)
}

/**
 * Leave a team.
 *
 * @param teamId - The team single id
 */
export async function leaveTeam(teamId: string): Promise<void> {
	await axios.put(
		generateOcsUrl('apps/circles/circles/{circleId}/leave', { circleId: teamId }),
		{},
	)
}

/**
 * Delete a team.
 *
 * @param teamId - The team single id
 */
export async function deleteTeam(teamId: string): Promise<void> {
	await axios.delete(generateOcsUrl('apps/circles/circles/{circleId}', { circleId: teamId }))
}

/**
 * The team folder linked to a team.
 */
export interface TeamFolder {
	id: number
	mountPoint: string
}

/**
 * Fetch the team folder linked to a team.
 *
 * The Circles app exposes the team-folder lifecycle through the core Teams
 * contract. The active provider is discovered server-side.
 *
 * @param teamId - The team single id
 * @return The linked team folder, or null if none exists.
 */
export async function getTeamFolder(teamId: string): Promise<TeamFolder | null> {
	try {
		const { data } = await axios.get<OcsResponse<TeamFolder>>(generateOcsUrl('apps/circles/teams/{circleId}/folder', { circleId: teamId }))
		return data.ocs.data
	} catch (error) {
		if (error && typeof error === 'object'
			&& 'response' in error
			&& (error.response as { status?: number })?.status === 404) {
			return null
		}
		throw error
	}
}

/**
 * Create a team folder for a team that predates the auto-create feature.
 *
 * Idempotent: if the team already owns a folder, the existing folder is
 * returned. Requires team owner privileges.
 *
 * @param teamId - The team single id
 * @return The created (or existing) team folder.
 */
export async function upgradeTeamFolder(teamId: string): Promise<TeamFolder> {
	const { data } = await axios.post<OcsResponse<{ folderId: number, folder: TeamFolder }>>(
		generateOcsUrl('apps/circles/teams/{circleId}/folder', { circleId: teamId }),
		{},
	)
	return data.ocs.data.folder
}

/**
 * Subfolder of the team folder holding the page files, inside the app's
 * own namespace of the hidden `.system` folder: the pages neither clutter
 * the files the team actually shares nor the `.system` root itself.
 */
const PAGES_FOLDER = '.system/teams/pages'

/**
 * A team page: a markdown file stored in the team folder's hidden pages
 * subfolder, surfaced as a tab on the team.
 */
export interface TeamPage {
	fileId: number
	/** Page title: the file name without the .md extension. */
	title: string
	/** Path relative to the user's files root, as the Text editor expects. */
	filePath: string
}

/**
 * List the team pages: the markdown files in the team folder's hidden
 * pages subfolder.
 *
 * @param mountPoint - The team folder mount point of the current user
 */
export async function fetchTeamPages(mountPoint: string): Promise<TeamPage[]> {
	let response
	try {
		response = await getClient().getDirectoryContents(`${defaultRootPath}/${mountPoint}/${PAGES_FOLDER}`, {
			details: true,
			data: getDefaultPropfind(),
		})
	} catch (error) {
		// 404 means the pages subfolder was not created yet (it appears with
		// the first page), or the team folder itself has not been physically
		// created — either way "no pages".
		if ((error as { status?: number })?.status === 404) {
			return []
		}
		throw error
	}
	const data = Array.isArray(response) ? response : response.data

	return data
		.map((entry) => resultToNode(entry, defaultRootPath))
		.filter((node) => node.type === FileType.File
			&& node.extension?.toLowerCase() === '.md'
			&& node.fileid !== undefined)
		.map((node) => ({
			fileId: node.fileid!,
			title: node.basename.slice(0, -node.extension!.length),
			filePath: `/${mountPoint}/${PAGES_FOLDER}/${node.basename}`,
		}))
		.sort((a, b) => a.title.localeCompare(b.title))
}

/**
 * Create a team page: an empty markdown file in the team folder's hidden
 * pages subfolder, which appears with the first page.
 *
 * @param mountPoint - The team folder mount point of the current user
 * @param name - The page name (without extension)
 */
export async function createTeamPage(mountPoint: string, name: string): Promise<void> {
	const pagesFolder = `${defaultRootPath}/${mountPoint}/${PAGES_FOLDER}`
	try {
		// Recursive: `.system` and the pages folder inside it appear with
		// the first page.
		await getClient().createDirectory(pagesFolder, { recursive: true })
	} catch (error) {
		// 405: the folder appeared between the existence probe and the MKCOL.
		if ((error as { status?: number })?.status !== 405) {
			throw error
		}
	}
	const written = await getClient().putFileContents(`${pagesFolder}/${name}.md`, '', {
		// Refuse to overwrite an existing page of the same name
		overwrite: false,
	})
	// The webdav client returns false instead of throwing on the 412 an
	// existing page produces with overwrite disabled.
	if (written === false) {
		throw Object.assign(new Error('A page with this name already exists'), { status: 412 })
	}
}

/**
 * Delete a team page: remove its markdown file from the team folder.
 *
 * @param page - The team page to delete
 */
export async function deleteTeamPage(page: TeamPage): Promise<void> {
	await getClient().deleteFile(`${defaultRootPath}${page.filePath}`)
}

/**
 * Rename a team page: move its markdown file to the new name within the
 * team folder. Refuses to overwrite an existing page of the same name.
 *
 * @param page - The team page to rename
 * @param name - The new page name (without extension)
 */
export async function renameTeamPage(page: TeamPage, name: string): Promise<void> {
	const directory = page.filePath.slice(0, page.filePath.lastIndexOf('/'))
	await getClient().moveFile(
		`${defaultRootPath}${page.filePath}`,
		`${defaultRootPath}${directory}/${name}.md`,
		{ overwrite: false },
	)
}

/**
 * Fetch the team-level tab order (readable by every member).
 *
 * @param teamId - The team single id
 * @return Tab ids, first to last. Empty when no order has been saved.
 */
export async function fetchTabOrder(teamId: string): Promise<string[]> {
	const { data } = await axios.get<OcsResponse<{ order: string[] }>>(generateOcsUrl('apps/circles/teams/{circleId}/tab-order', { circleId: teamId }))
	return data.ocs.data.order ?? []
}

/**
 * Save the team-level tab order. Requires team admin or above.
 *
 * @param teamId - The team single id
 * @param order - Tab ids, first to last
 */
export async function saveTabOrder(teamId: string, order: string[]): Promise<void> {
	await axios.put(
		generateOcsUrl('apps/circles/teams/{circleId}/tab-order', { circleId: teamId }),
		{ order },
	)
}

/**
 * Fetch the resources shared to a team from the core teams resource
 * providers (Talk rooms, calendars, collectives, …).
 *
 * @param teamId - The team single id
 */
export async function fetchTeamResources(teamId: string): Promise<SharedResource[]> {
	const res = await axios.get<OcsResponse<{ resources: SharedResource[] }>>(generateOcsUrl('teams/{teamId}/resources', { teamId }))
	return res.data.ocs.data.resources ?? []
}

/**
 * Create a collective named after a team. The collectives app links the
 * collective to the team by name, so no separate share step is needed.
 *
 * TODO: calls the collectives API directly; should eventually go through
 * a teams extension point instead of hardcoding another app's route.
 *
 * @param name - The collective name (the team's name)
 */
export async function createCollective(name: string): Promise<void> {
	await axios.post(
		generateOcsUrl('apps/collectives/api/v1.0/collectives'),
		{ name },
	)
}

/**
 * Create a Deck board linked to a team. The deck endpoint links the board
 * to the team itself, so no separate share step is needed.
 *
 * TODO: calls the deck API directly; should eventually go through a teams
 * extension point instead of hardcoding another app's route.
 *
 * @param teamId - The team single id
 * @param title - The board title
 */
export async function createDeckBoard(teamId: string, title: string): Promise<void> {
	const res = await axios.post<OcsResponse<{ id?: number }>>(
		generateOcsUrl('apps/deck/api/v1.0/boards/team'),
		{ title, teamId },
	)
	if (!res.data.ocs.data.id) {
		throw new Error('The board creation response contains no board id')
	}
}

/**
 * Search for potential new members (users, groups, emails, contacts, other
 * teams…) using the same sharee autocompletion endpoint as file sharing.
 * This restores the legacy "add members while creating a team" feature for
 * the team creation wizard.
 *
 * @param term - The search query. An empty term returns curated recommendations.
 */
export async function searchMemberCandidates(term: string): Promise<MemberCandidate[]> {
	const suggestions: RawSuggestion[] = term.trim()
		? await getSuggestions(term)
		: await getRecommendations()

	return suggestions.map((suggestion) => ({
		key: suggestion.id,
		shareWith: suggestion.shareWith,
		shareType: suggestion.shareType,
		displayName: suggestion.label,
		isUser: suggestion.user !== null && suggestion.user !== undefined,
	}))
}

/**
 * Add a batch of picked candidates as members of a team, typically right
 * after creating it from the wizard.
 *
 * @param teamId - The team single id
 * @param candidates - The candidates picked in the wizard's member step
 * @return The number of candidates that were actually added.
 */
export async function addTeamMembers(teamId: string, candidates: MemberCandidate[]): Promise<number> {
	const members = candidates.map((candidate) => ({
		id: candidate.shareWith,
		type: shareTypeToMemberType[candidate.shareType],
	}))
	const res = await axios.post<OcsResponse<Record<string, unknown>>>(
		generateOcsUrl('apps/circles/circles/{circleId}/members/multi', { circleId: teamId }),
		{ members },
	)
	return Object.keys(res.data.ocs.data ?? {}).length
}
