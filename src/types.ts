/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

interface ATeamMember {
	userId?: string
	singleId?: string
	displayName: string
	type: number
	isUser: boolean
	url: string
}

export type ITeamMember = (ATeamMember & { userId: string }) | (ATeamMember & { singleId: string })

export interface ITeamResource {
	id: string
	name: string
	fallbackIcon: string
	iconUrl: string
	url: string
}

export interface ITeam {
	id: string
	displayName: string
	url: string
	members: ITeamMember[]
	resources: ITeamResource[]
}
