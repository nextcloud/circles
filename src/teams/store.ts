/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Member, MemberCandidate, Team } from './types.ts'

import { defineStore } from 'pinia'
import { logger } from '../logger.ts'
import * as api from './api.ts'

interface TeamsState {
	/** All teams the current user is part of. */
	teams: Team[]
	/** Whether the teams list is currently loading. */
	loading: boolean
	/** Whether the last teams load failed. */
	loadError: boolean
	/** Whether the "create a new team" wizard is open (shared across the app). */
	createWizardOpen: boolean
}

/**
 * Store backing the app. Teams are fetched from the circles API; mutations
 * (create/leave/delete) hit the API and then reload the list so the UI stays
 * in sync with the backend.
 */
export const useTeamsStore = defineStore('teams', {
	state: (): TeamsState => ({
		teams: [],
		loading: false,
		loadError: false,
		createWizardOpen: false,
	}),

	getters: {
		// Find a single team by id.
		getTeam: (state) => (id: string): Team | undefined => state.teams.find((team) => team.id === id),

		// Filter teams by a free-text query against their display name.
		searchTeams: (state) => (query: string): Team[] => {
			const needle = query.trim().toLowerCase()
			if (!needle) {
				return state.teams
			}
			return state.teams.filter((team) => team.displayName.toLowerCase().includes(needle))
		},
	},

	actions: {
		/** Open the "create a new team" wizard. */
		openCreateTeamWizard(): void {
			this.createWizardOpen = true
		},

		/** Load (or reload) the list of teams from the backend. */
		async loadTeams(): Promise<void> {
			this.loading = true
			this.loadError = false
			try {
				this.teams = await api.fetchTeams()
			} catch (error) {
				this.loadError = true
				logger.error('Failed to load teams', { error })
			} finally {
				this.loading = false
			}
		},

		/**
		 * Fetch the full member list (with roles) for a team.
		 *
		 * @param id - The team id
		 */
		fetchTeamMembers(id: string): Promise<Member[]> {
			return api.fetchTeamMembers(id)
		},

		/**
		 * Create a team (optionally with a description), reload the list and
		 * return it.
		 *
		 * @param displayName - The team name
		 * @param description - An optional description
		 */
		async createTeam(displayName: string, description = ''): Promise<Team | undefined> {
			const id = await api.createTeam(displayName.trim())
			const trimmedDescription = description.trim()
			if (trimmedDescription) {
				try {
					await api.setTeamDescription(id, trimmedDescription)
				} catch (error) {
					logger.warn('Failed to set team description', { error })
				}
			}
			await this.loadTeams()
			return this.getTeam(id)
		},

		/**
		 * Search for potential new members while creating a team (users,
		 * groups, emails, contacts, other teams…).
		 *
		 * @param term - The search query
		 */
		searchMemberCandidates(term: string): Promise<MemberCandidate[]> {
			return api.searchMemberCandidates(term)
		},

		/**
		 * Add initial members to a freshly created team.
		 *
		 * @param teamId - The team id
		 * @param candidates - The candidates picked in the wizard's member step
		 * @return The number of candidates that were actually added.
		 */
		addTeamMembers(teamId: string, candidates: MemberCandidate[]): Promise<number> {
			return api.addTeamMembers(teamId, candidates)
		},

		/**
		 * Leave a team and reload the list.
		 *
		 * @param id - The team id
		 */
		async leaveTeam(id: string): Promise<void> {
			await api.leaveTeam(id)
			await this.loadTeams()
		},

		/**
		 * Delete a team and reload the list.
		 *
		 * @param id - The team id
		 */
		async deleteTeam(id: string): Promise<void> {
			await api.deleteTeam(id)
			await this.loadTeams()
		},
	},
})
