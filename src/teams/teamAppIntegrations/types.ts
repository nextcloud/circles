/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { NcReferenceWidget } from '@nextcloud/vue/components/NcRichText'
import type { SharedResource, TeamFolder } from '../types.ts'

export type TeamReference = InstanceType<typeof NcReferenceWidget>['$props']['reference']

type NavigationResource = SharedResource & { id: string }

export interface TeamAppNavigationDescriptor {
	entries: NavigationResource[]
	canAddResource: boolean
}

export interface TeamAppNavigationAction {
	id: string
	label: string
	icon?: string
	iconUrl?: string
	run: () => void | Promise<void>
}

export type TeamAppNavigationResponse = TeamAppNavigationDescriptor

export type TeamNavigationEntry = { id: 'team-folder' | 'home' | `page-${number}` } | { id: string, providerId: string, entryId: string }

export interface TeamAppNavigationsResponse {
	/** Ordered navigation entry references; app details come from providers. */
	entries: TeamNavigationEntry[]
	providers: Record<string, TeamAppNavigationResponse>
	errors: string[]
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

export interface TeamOwnedResourceProvider {
	id: string
	name: string
	iconUrl?: string
}
