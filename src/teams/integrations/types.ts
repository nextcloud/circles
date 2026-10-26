/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TeamAppNavigationAction as ActionDescriptor, TeamAppNavigationEntry as EntryDescriptor, TeamAppNavigation as NavigationDescriptor, SharedResource, TeamFolder } from '#server-types/teams'

export interface TeamAppNavigationAction extends ActionDescriptor {
	run: () => void | Promise<void>
}

export type TeamAppNavigationEntry = EntryDescriptor

export interface TeamAppNavigation extends Omit<NavigationDescriptor, 'entries'> {
	entries: TeamAppNavigationEntry[]
	createAction?: TeamAppNavigationAction
	selectedEntryIds: string[]
}

export interface TeamAppNavigationResponse extends NavigationDescriptor {
	selectedEntryIds: string[]
}

export interface TeamAppContext {
	teamId: string
	teamName: string
	isAdmin: boolean
	folder: TeamFolder | null
	resources: SharedResource[]
	signal: AbortSignal
	refresh: () => Promise<void>
	open: (entryId: string) => void
}

export interface TeamAppProvider {
	id: string
	name: string
	order: number
}
