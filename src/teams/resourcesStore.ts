/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TeamFolder, TeamPage } from './api.ts'
import type { TeamNavigationEntry } from './teamAppIntegrations/types.ts'
import type { SharedResource } from './types.ts'

import { defineStore } from 'pinia'
import { logger } from '../logger.ts'
import { fetchTeamPages, fetchTeamResources, getTeamFolder, saveNavigation, upgradeTeamFolder } from './api.ts'

/**
 * The shared resource state of one team: its folder, shared resources and
 * navigation order.
 */
export interface TeamResources {
	folder: TeamFolder | null
	/** The folder probe finished; `folder` is authoritative (may be null). */
	folderChecked: boolean
	/** The folder probe failed; `folder` is unknown, not absent. */
	folderError: boolean
	resources: SharedResource[]
	resourcesChecked: boolean
	resourcesError: boolean
	pages: TeamPage[]
	pagesChecked: boolean
	pagesError: boolean
	navigation: TeamNavigationEntry[]
	orderChecked: boolean
	/** The navigation fetch failed; `navigation` is not authoritative. */
	orderError: boolean
}

const EMPTY_SLOT = Object.freeze<TeamResources>({
	folder: null,
	folderChecked: false,
	folderError: false,
	resources: [],
	resourcesChecked: false,
	resourcesError: false,
	pages: [],
	pagesChecked: false,
	pagesError: false,
	navigation: [{ id: 'team-folder' }, { id: 'home' }],
	orderChecked: false,
	orderError: false,
})

/**
 * Store holding the per-team resource state, keyed by team id. Writes always
 * land in the slot of the team they were requested for, so late responses
 * from a previous team can never corrupt the currently shown team.
 *
 * The initial load is owned by the team page (the route component of the
 * team scope); the sidebar and the views only read the store. The ensure*
 * actions are also called for targeted refreshes after mutations.
 *
 * The ensure* actions never throw: consumers read the error flags off the
 * slot. The mutating actions (createFolder, saveOrder) throw so the calling
 * component can toast.
 */
export const useTeamResourcesStore = defineStore('teamResources', {
	state: () => ({
		slots: {} as Record<string, TeamResources>,
	}),

	getters: {
		/**
		 * The (possibly not yet loaded) resource state of a team.
		 *
		 * @param state - The store state
		 */
		forTeam(state) {
			return (teamId: string): TeamResources => state.slots[teamId] ?? EMPTY_SLOT
		},
	},

	actions: {
		/**
		 * The mutable slot of a team, created on first access.
		 *
		 * @param teamId - The team the slot belongs to
		 */
		slot(teamId: string): TeamResources {
			if (!this.slots[teamId]) {
				this.slots[teamId] = { ...EMPTY_SLOT }
			}
			return this.slots[teamId]
		},

		/**
		 * Load the team folder and shared resources. Navigation order is loaded
		 * together with app navigation by the Teams app store.
		 *
		 * @param teamId - The team to load
		 * @param refresh - Refetch data already in the cache
		 */
		async loadTeam(teamId: string, refresh = false): Promise<void> {
			await Promise.all([
				this.ensureFolder(teamId, refresh),
				this.ensureResources(teamId, refresh),
			])
			await this.ensurePages(teamId, refresh)
		},

		/**
		 * Probe for the team folder unless already known.
		 *
		 * @param teamId - The team to probe
		 * @param refresh - Refetch even when cached
		 */
		async ensureFolder(teamId: string, refresh = false): Promise<void> {
			const slot = this.slot(teamId)
			if (slot.folderChecked && !refresh) {
				return
			}
			try {
				slot.folder = await getTeamFolder(teamId)
				slot.folderChecked = true
				slot.folderError = false
			} catch (error) {
				logger.error('Could not load the team folder', { error, teamId })
				slot.folderError = true
			}
		},

		/**
		 * Load the resources shared to the team unless already known.
		 *
		 * @param teamId - The team to load resources for
		 * @param refresh - Refetch even when cached
		 */
		async ensureResources(teamId: string, refresh = false): Promise<void> {
			const slot = this.slot(teamId)
			if (slot.resourcesChecked && !refresh) {
				return
			}
			try {
				slot.resources = await fetchTeamResources(teamId)
				slot.resourcesChecked = true
				slot.resourcesError = false
			} catch (error) {
				logger.error('Could not load the team resources', { error, teamId })
				slot.resourcesError = true
			}
		},

		/**
		 * Load native Pages after the folder probe has settled.
		 *
		 * @param teamId - The team to load
		 * @param refresh - Refetch cached pages
		 */
		async ensurePages(teamId: string, refresh = false): Promise<void> {
			const slot = this.slot(teamId)
			if (slot.pagesChecked && !refresh) {
				return
			}
			await this.ensureFolder(teamId)
			if (slot.folderError) {
				slot.pagesError = true
				return
			}
			try {
				slot.pages = slot.folder ? await fetchTeamPages(slot.folder.mountPoint) : []
				slot.pagesChecked = true
				slot.pagesError = false
				this.applyNavigation(teamId, slot.navigation)
			} catch (error) {
				logger.error('Could not load the team pages', { error, teamId })
				slot.pagesError = true
			}
		},

		/**
		 * Merge native pages into provider navigation, preserving saved order.
		 *
		 * @param teamId - The team owning the navigation
		 * @param entries - The server's ordered navigation
		 */
		applyNavigation(teamId: string, entries: TeamNavigationEntry[]): void {
			const slot = this.slot(teamId)
			const pageIds = new Set(slot.pages.map((page) => `page-${page.fileId}`))
			const navigation = entries.map((entry): TeamNavigationEntry => {
				if ('providerId' in entry && entry.providerId === 'files' && pageIds.has(`page-${entry.entryId}`)) {
					return { id: `page-${Number(entry.entryId)}` }
				}
				return entry
			})
			const known = new Set(navigation.map(({ id }) => id))
			const missing = slot.pages.filter((page) => !known.has(`page-${page.fileId}`))
				.map((page): TeamNavigationEntry => ({ id: `page-${page.fileId}` }))
			const home = navigation.findIndex(({ id }) => id === 'home')
			navigation.splice(home < 0 ? navigation.length : home, 0, ...missing)
			slot.navigation = navigation.filter((entry, index) => navigation.findIndex(({ id }) => id === entry.id) === index)
		},

		/**
		 * Create the team folder and record it. Throws on failure.
		 *
		 * @param teamId - The team to create the folder for
		 */
		async createFolder(teamId: string): Promise<TeamFolder> {
			const folder = await upgradeTeamFolder(teamId)
			const slot = this.slot(teamId)
			slot.folder = folder
			slot.folderChecked = true
			slot.folderError = false
			return folder
		},

		/**
		 * Persist a new navigation order, applying it optimistically and
		 * rolling back on failure. Throws on failure.
		 *
		 * @param teamId - The team the order belongs to
		 * @param entries - The selected navigation entries in display order
		 */
		async saveOrder(teamId: string, entries: TeamNavigationEntry[]): Promise<void> {
			const slot = this.slot(teamId)
			const previous = slot.navigation
			slot.navigation = entries
			try {
				await saveNavigation(teamId, entries.map(({ id }) => id))
			} catch (error) {
				slot.navigation = previous
				throw error
			}
		},
	},
})
