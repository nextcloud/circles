/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TeamAppAction as ActionDescriptor, TeamAppEntry as EntryDescriptor, TeamAppNavigation as NavigationDescriptor, SharedResource, TeamAppResourcePickerOptions, TeamAppView, TeamFolder } from '#server-types/teams'

export type {
	TeamAppResource,
	TeamAppResourcePickerOptions,
	TeamAppView,
} from '#server-types/teams'

export interface TeamAppAction extends ActionDescriptor {
	run: () => void | Promise<void>
}

export interface TeamAppEntry extends Omit<EntryDescriptor, 'actions'> {
	actions?: TeamAppAction[]
}

export interface TeamAppNavigation extends Omit<NavigationDescriptor, 'entries' | 'actions'> {
	entries: TeamAppEntry[]
	actions?: TeamAppAction[]
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
	getNavigationEntryIds: () => Promise<string[]>
	setNavigationEntryIds: (navigationEntryIds: string[]) => Promise<void>
	chooseResources: (options: TeamAppResourcePickerOptions) => Promise<string[] | null>
	prompt: (title: string, value?: string) => Promise<string | null>
	confirm: (title: string, message: string) => Promise<boolean>
}

export interface TeamAppProvider {
	id: string
	order: number
	load: (context: TeamAppContext) => Promise<NavigationDescriptor>
	mount?: TeamAppView['mount']
	routeAliases?: Array<{ path: string, entryParam: string }>
}
