/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TeamAppNavigationsResponse, TeamReference } from './teamAppIntegrations/types.ts'
import type { DiscoverableTeam, Member, MemberCandidate, Resource, SharedResource, Team, TeamFolder, TeamRole } from './types.ts'

import axios from '@nextcloud/axios'
import { FileType } from '@nextcloud/files'
import { defaultRootPath, getClient, getDefaultPropfind, resultToNode } from '@nextcloud/files/dav'
import { generateOcsUrl } from '@nextcloud/router'
import { logger } from '../logger.ts'
import { SHARES_TYPES_MEMBER_MAP } from './team-page/models/constants.ts'
import { getRecommendations, getSuggestions } from './team-page/services/collaborationAutocompletion.js'

export type { TeamFolder } from './types.ts'

/** `Circle::CFG_OPEN`: anyone can join or request to join. */
const CIRCLE_CONFIG_OPEN = 16
/** `Member::STATUS_REQUEST`: a join request awaiting moderator approval. */
const MEMBER_STATUS_REQUEST = 'Requesting'

/** `SHARES_TYPES_MEMBER_MAP` is built dynamically, so type its shape explicitly. */
const shareTypeToMemberType = SHARES_TYPES_MEMBER_MAP as Record<number, number>

/** Minimal shape of an OCS response envelope. */
interface OcsResponse<T> {
	ocs: { data: T }
}

/**
 * Resolve deduplicated local resource URLs without prefetching external links.
 *
 * @param urls Team resource URLs
 * @param signal Active team load
 */
export async function resolveTeamReferences(urls: string[], signal: AbortSignal): Promise<Record<string, TeamReference | null>> {
	const references = [...new Set(urls.filter((url) => URL.canParse(url, window.location.href))
		.map((url) => new URL(url, window.location.href))
		.filter((url) => ['http:', 'https:'].includes(url.protocol) && url.origin === window.location.origin && !url.username && !url.password)
		.map((url) => url.href))]
	if (references.length === 0) {
		return {}
	}
	const url = generateOcsUrl('references/resolve')
	const response = await axios.post<OcsResponse<{ references: Record<string, TeamReference | null> }>>(url, { references, limit: references.length }, { signal, timeout: 5000 })
	return response.data.ocs.data.references
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
	config?: number
	initiator?: { level?: number, status?: string } | null
}

/** Teams the user is part of, and visible teams they could join. */
export interface TeamsOverview {
	teams: Team[]
	discoverableTeams: DiscoverableTeam[]
}

/**
 * Whether the user's only link to the circle is a pending join request.
 *
 * @param circle - The raw circle
 */
function isPendingRequest(circle: RawCircle): boolean {
	return circle.initiator?.status === MEMBER_STATUS_REQUEST
}

/**
 * Map a circle the user is not an active member of.
 *
 * @param circle - The raw circle
 */
function mapDiscoverableTeam(circle: RawCircle): DiscoverableTeam {
	const pending = isPendingRequest(circle)
	return {
		id: circle.id,
		displayName: circle.displayName || circle.name,
		description: circle.description ?? '',
		memberCount: circle.population ?? 0,
		canJoin: !pending && ((circle.config ?? 0) & CIRCLE_CONFIG_OPEN) !== 0,
		pending,
	}
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
	subname: string
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
 * Fetch the current user's teams and discoverable teams, merging active team
 * metadata (name, description, member count, our role) with previews.
 */
export async function fetchTeams(): Promise<TeamsOverview> {
	const [circlesRes, dashRes, favoritesRes] = await Promise.allSettled([
		axios.get<OcsResponse<RawCircle[]>>(generateOcsUrl('apps/circles/circles') + '?limit=-1'),
		axios.get<OcsResponse<RawDashboardTeam[]>>(generateOcsUrl('apps/circles/teams/dashboard/widget') + '?limit=200&offset=0'),
		axios.get<OcsResponse<{ circleIds: string[] }>>(generateOcsUrl('apps/circles/teams/favorites')),
	])

	// The team list is required; without it we have nothing to show.
	if (circlesRes.status === 'rejected') {
		throw circlesRes.reason
	}
	const allCircles = circlesRes.value.data.ocs.data ?? []
	const memberCircles = allCircles.filter((circle) => circle.initiator && !isPendingRequest(circle))
	const discoverableTeams = allCircles
		.filter((circle) => !circle.initiator || isPendingRequest(circle))
		.map(mapDiscoverableTeam)
		.sort((a, b) => a.displayName.localeCompare(b.displayName))

	// The dashboard only enriches each team with member/resource previews, so
	// treat a failure there as "no previews" rather than failing the whole page.
	let dashboard: RawDashboardTeam[] = []
	if (dashRes.status === 'fulfilled') {
		dashboard = dashRes.value.data.ocs.data ?? []
	} else {
		logger.warn('Failed to load team dashboard previews', { error: dashRes.reason })
	}
	const dashboardById = new Map(dashboard.map((team) => [team.singleId, team]))
	let favoriteCircleIds: string[] = []
	if (favoritesRes.status === 'fulfilled') {
		favoriteCircleIds = favoritesRes.value.data.ocs.data.circleIds
	} else {
		logger.warn('Failed to load favorite teams', { error: favoritesRes.reason })
	}
	const favoritePositions = new Map(favoriteCircleIds.map((circleId, position) => [circleId, position]))

	return {
		teams: memberCircles.map((circle) => {
			const extra = dashboardById.get(circle.id)
			return {
				id: circle.id,
				displayName: circle.displayName || circle.name,
				description: circle.description ?? '',
				isFavorite: favoritePositions.has(circle.id),
				favoritePosition: favoritePositions.get(circle.id) ?? null,
				memberCount: circle.population ?? extra?.members.length ?? 0,
				myRole: levelToRole(circle.initiator?.level),
				members: (extra?.members ?? []).map(mapPreviewMember),
				resources: (extra?.resources ?? []).map(mapResource),
			}
		}),
		discoverableTeams,
	}
}

/**
 * Update a favorite and return the canonical order.
 *
 * @param teamId - Team identifier
 * @param isFavorite - Desired favorite state
 */
export async function setTeamFavorite(teamId: string, isFavorite: boolean): Promise<string[]> {
	const response = await axios.put<OcsResponse<{ circleIds: string[] }>>(generateOcsUrl('apps/circles/teams/{circleId}/favorite', { circleId: teamId }), { isFavorite })
	return response.data.ocs.data.circleIds
}

/**
 * Save a new order only if the server still has the expected order.
 *
 * @param teamIds - Desired order
 * @param expectedCircleIds - Previously loaded order
 */
export async function reorderFavoriteTeams(teamIds: string[], expectedCircleIds: string[]): Promise<string[]> {
	const response = await axios.put<OcsResponse<{ circleIds: string[] }>>(generateOcsUrl('apps/circles/teams/favorites/order'), { circleIds: teamIds, expectedCircleIds })
	return response.data.ocs.data.circleIds
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
 * @param createTeamFolder - Whether to auto-create a team folder. Defaults to
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
 * Join a team, or request to join it when it requires approval.
 *
 * @param teamId - The team single id
 * @return 'requested' when the join awaits approval, otherwise 'joined'
 */
export async function joinTeam(teamId: string): Promise<'joined' | 'requested'> {
	const res = await axios.put<OcsResponse<{ status?: string }>>(
		generateOcsUrl('apps/circles/circles/{circleId}/join', { circleId: teamId }),
		{},
	)
	return res.data.ocs.data?.status === MEMBER_STATUS_REQUEST ? 'requested' : 'joined'
}

/**
 * Delete a team.
 *
 * @param teamId - The team single id
 */
export async function deleteTeam(teamId: string): Promise<void> {
	await axios.delete(generateOcsUrl('apps/circles/circles/{circleId}', { circleId: teamId }))
}

/** Team and its linked folder as exposed to administrators. */
export interface AdminTeamFolder {
	teamId: string
	teamName: string
	defaultQuota: number | null
	folder: TeamFolder | null
}

/**
 * Fetch all teams and their optionally linked team folders for the admin settings view.
 */
export async function getAdminTeamFolders(): Promise<AdminTeamFolder[]> {
	const { data } = await axios.get<OcsResponse<AdminTeamFolder[]>>(generateOcsUrl('/apps/circles/admin/teamfolders'))
	return data.ocs.data
}

/**
 * Update the default quota associated with a team.
 *
 * @param teamId - The team single id
 * @param quota - Quota in bytes, zero for unlimited, or null to remove the override
 */
export async function updateTeamFolderDefaultQuota(teamId: string, quota: number | null): Promise<{ teamId: string, defaultQuota: number | null }> {
	const { data } = await axios.put<OcsResponse<{ teamId: string, defaultQuota: number | null }>>(
		generateOcsUrl('/apps/circles/admin/teamfolders/{circleId}/default-quota', { circleId: teamId }),
		{ quota },
	)
	return data.ocs.data
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
 * @param name - Optional team folder name
 * @return The created (or existing) team folder.
 */
export async function upgradeTeamFolder(teamId: string, name?: string): Promise<TeamFolder> {
	const { data } = await axios.post<OcsResponse<{ folderId: number, folder: TeamFolder }>>(
		generateOcsUrl('apps/circles/teams/{circleId}/folder', { circleId: teamId }),
		{ name },
	)
	return data.ocs.data.folder
}

/**
 * Fetch team folders that are not yet linked to a team.
 *
 * @param teamId - The team single id
 */
export async function getLinkableTeamFolders(teamId: string): Promise<TeamFolder[]> {
	const { data } = await axios.get<OcsResponse<TeamFolder[]>>(generateOcsUrl('apps/circles/teams/{circleId}/folder/linkable', { circleId: teamId }))
	return data.ocs.data
}

/**
 * Link a team to a team folder.
 *
 * @param teamId - The team single id
 * @param folderId - The id of the existing team folder
 */
export async function linkTeamFolder(teamId: string, folderId: number): Promise<TeamFolder> {
	const { data } = await axios.post<OcsResponse<{ folderId: number, folder: TeamFolder }>>(
		generateOcsUrl('apps/circles/teams/{circleId}/folder/link', { circleId: teamId }),
		{ folderId },
	)
	return data.ocs.data.folder
}

/**
 * Update the storage quota of the team folder linked to a team.
 *
 * @param teamId - The team single id
 * @param quota - Quota in bytes, or zero for unlimited
 */
export async function updateTeamFolderQuota(teamId: string, quota: number): Promise<TeamFolder> {
	const { data } = await axios.put<OcsResponse<{ folderId: number, folder: TeamFolder }>>(
		generateOcsUrl('apps/circles/teams/{circleId}/folder/quota', { circleId: teamId }),
		{ quota },
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
 * Fetch ordered team navigation and provider metadata for selection and creation.
 *
 * @param teamId - The team single id
 * @param signal - Active team scope signal
 */
export async function getTeamAppNavigations(teamId: string, signal: AbortSignal): Promise<TeamAppNavigationsResponse> {
	const { data } = await axios.get<OcsResponse<TeamAppNavigationsResponse>>(
		generateOcsUrl('apps/circles/teams/{teamId}/apps/navigation', { teamId }),
		{ signal },
	)
	return data.ocs.data
}

/**
 * Save the selected team navigation entries in display order. Requires team admin or above.
 *
 * @param teamId - The team single id
 * @param order - Navigation entry IDs, first to last
 */
export async function saveNavigation(teamId: string, order: string[]): Promise<void> {
	await axios.put(
		generateOcsUrl('apps/circles/teams/{circleId}/apps/navigation', { circleId: teamId }),
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
		subname: suggestion.subname,
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
