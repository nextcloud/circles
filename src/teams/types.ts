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
