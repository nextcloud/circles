/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TeamBoard, TeamFolder, TeamPage } from './api.ts'
import type { SharedResource } from './types.ts'

import { defineStore } from 'pinia'
import { logger } from '../logger.ts'
import { fetchTabOrder, fetchTeamDeckBoards, fetchTeamPages, fetchTeamResources, getTeamFolder, saveTabOrder, upgradeTeamFolder } from './api.ts'

/**
 * The shared resource state of one team: its team folder, the resources
 * shared to it, its pages and the navigation tab order.
 */
export interface TeamResources {
	folder: TeamFolder | null
	/** The folder probe finished; `folder` is authoritative (may be null). */
	folderChecked: boolean
	/** The folder probe failed; `folder` is unknown, not absent. */
	folderError: boolean
	resources: SharedResource[]
	resourcesChecked: boolean
	/** Deck boards attached to the team itself, shown as navigation entries. */
	boards: TeamBoard[]
	boardsChecked: boolean
	pages: TeamPage[]
	pagesChecked: boolean
	pagesError: boolean
	tabOrder: string[]
	orderChecked: boolean
	/** The order fetch failed; `tabOrder` is unknown, not empty. */
	orderError: boolean
}

const EMPTY_SLOT: TeamResources = Object.freeze({
	folder: null,
	folderChecked: false,
	folderError: false,
	resources: [],
	resourcesChecked: false,
	boards: [],
	boardsChecked: false,
	pages: [],
	pagesChecked: false,
	pagesError: false,
	tabOrder: [],
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
		 * Load everything the team-scoped pages need.
		 *
		 * @param teamId - The team to load
		 * @param refresh - Refetch data already in the cache
		 */
		async loadTeam(teamId: string, refresh = false): Promise<void> {
			await Promise.all([
				// Pages live in the folder, so they load once it is known;
				// boards are found among the resources, same dependency.
				this.ensureFolder(teamId, refresh).then(() => this.ensurePages(teamId, refresh)),
				this.ensureResources(teamId, refresh).then(() => this.ensureBoards(teamId, refresh)),
				this.ensureOrder(teamId, refresh),
			])
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
			} catch (error) {
				logger.error('Could not load the team resources', { error, teamId })
			}
		},

		/**
		 * Find the deck boards attached to the team itself among the shared
		 * resources. Marked checked even on failure: consumers only use the
		 * boards to pin tabs and to filter the shared list, and the boards
		 * stay reachable there.
		 *
		 * @param teamId - The team to load boards for
		 * @param refresh - Refetch even when cached
		 */
		async ensureBoards(teamId: string, refresh = false): Promise<void> {
			const slot = this.slot(teamId)
			if (slot.boardsChecked && !refresh) {
				return
			}
			await this.ensureResources(teamId)
			const candidates = slot.resources.filter((resource) => resource.provider.id === 'deck')
			try {
				slot.boards = await fetchTeamDeckBoards(teamId, candidates)
			} catch (error) {
				logger.error('Could not load the team boards', { error, teamId })
				slot.boards = []
			}
			slot.boardsChecked = true
		},

		/**
		 * Load the team pages unless already known. Probes the folder first
		 * when needed — pages are the markdown files in the team folder.
		 *
		 * @param teamId - The team to load pages for
		 * @param refresh - Refetch the pages even when cached
		 */
		async ensurePages(teamId: string, refresh = false): Promise<void> {
			const slot = this.slot(teamId)
			if (slot.pagesChecked && !refresh) {
				return
			}
			await this.ensureFolder(teamId)
			if (!slot.folder) {
				slot.pages = []
				slot.pagesChecked = slot.folderChecked
				slot.pagesError = slot.folderError
				return
			}
			try {
				slot.pages = await fetchTeamPages(slot.folder.mountPoint)
				slot.pagesChecked = true
				slot.pagesError = false
			} catch (error) {
				logger.error('Could not load the team pages', { error, teamId })
				slot.pages = []
				slot.pagesError = true
			}
		},

		/**
		 * Load the team-level navigation order unless already known.
		 *
		 * @param teamId - The team to load the order for
		 * @param refresh - Refetch even when cached
		 */
		async ensureOrder(teamId: string, refresh = false): Promise<void> {
			const slot = this.slot(teamId)
			if (slot.orderChecked && !refresh) {
				return
			}
			try {
				slot.tabOrder = await fetchTabOrder(teamId)
				slot.orderChecked = true
				slot.orderError = false
			} catch (error) {
				logger.error('Could not load the navigation order', { error, teamId })
				slot.orderError = true
			}
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
			// A fresh folder has no pages yet.
			slot.pages = []
			slot.pagesChecked = true
			slot.pagesError = false
			return folder
		},

		/**
		 * Persist a new navigation order, applying it optimistically and
		 * rolling back on failure. Throws on failure.
		 *
		 * @param teamId - The team the order belongs to
		 * @param order - The entry ids in their new order
		 */
		async saveOrder(teamId: string, order: string[]): Promise<void> {
			const slot = this.slot(teamId)
			const previous = slot.tabOrder
			slot.tabOrder = order
			try {
				await saveTabOrder(teamId, order)
			} catch (error) {
				slot.tabOrder = previous
				throw error
			}
		},
	},
})
