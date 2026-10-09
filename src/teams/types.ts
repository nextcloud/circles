/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** Permission level inside a team, derived from the circles member level. */
export type TeamRole = 'owner' | 'admin' | 'moderator' | 'member'

/**
 * A member of a team. Maps to a circles Member: `id` is the member's single id,
 * `userId` is set for real Nextcloud users (controls avatar rendering).
 */
export interface Member {
	id: string
	userId: string | null
	displayName: string
	isUser: boolean
	role: TeamRole
}

/**
 * A resource shared with a team. These come from the circles team-resource
 * provider (currently files and folders shared with the team).
 */
export interface Resource {
	id: string
	name: string
	type: 'folder' | 'file'
	/** Icon/preview image URL provided by the backend. */
	iconUrl: string
	/** Icon to fall back to if {@link iconUrl} fails to load. */
	fallbackIcon: string
	/** URL the resource opens at. */
	url: string
}

/** The app/provider a shared resource originates from (Talk, Calendar, …). */
export interface SharedResourceProvider {
	id: string
	name: string
}

/**
 * A resource shared to a team, as returned by the core
 * `/teams/{teamId}/resources` OCS endpoint. Distinct from {@link Resource},
 * which is the dashboard-widget preview shape.
 */
export interface SharedResource {
	id: string | number
	label: string
	/** URL the resource opens at. */
	url: string
	/** Inline SVG markup for the icon. */
	iconSvg?: string
	/** Icon image URL, used when no inline SVG is provided. */
	iconURL?: string
	provider: SharedResourceProvider
}

export interface Team {
	id: string
	displayName: string
	description: string
	/** Total number of members (may exceed the previewed {@link members}). */
	memberCount: number
	/** The current user's role in this team. */
	myRole: TeamRole
	/** A small preview of members for avatars (not the full list). */
	members: Member[]
	resources: Resource[]
}

/** A formatted Activity event scoped to a team. */
export interface TeamActivity {
	activity_id: number
	subject: string
	datetime: string
	user: string
	type?: string
	icon?: string
	object_type?: string
	message?: string
	subject_rich?: [string, Record<string, RichObject> | []]
	message_rich?: [string, Record<string, RichObject> | []]
}

/** A rich object embedded in an Activity subject or message. */
export interface RichObject {
	id: string | number
	name: string
	type: string
	link?: string
	server?: string
}

/** Server-side filters for the Team Activity stream. */
export interface TeamActivityQuery {
	since?: number
	limit?: number
	sort?: 'asc' | 'desc'
	search?: string
	from?: number
	to?: number
	actor?: string
}

/** One cursor-paginated page of Team Activity events. */
export interface TeamActivityPage {
	activities: TeamActivity[]
	nextSince?: number
}

/**
 * A candidate member surfaced by the sharee autocompletion search (users,
 * groups, emails, contacts, other teams…), before they have been added to a
 * team. Used by the team creation wizard's member selection step.
 */
export interface MemberCandidate {
	/** Unique key across all suggestion types, safe to use as a list `:key`. */
	key: string
	/** The raw id expected by the "add members" endpoint. */
	shareWith: string
	/** The sharee share type, see `@nextcloud/sharing`'s `ShareType`. */
	shareType: number
	displayName: string
	/** Secondary line telling apart candidates with the same name (e.g. their email). */
	subname: string
	/** Whether this candidate is a real Nextcloud user (controls avatar rendering). */
	isUser: boolean
}
