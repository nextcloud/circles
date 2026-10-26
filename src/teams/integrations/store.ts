/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TeamAppAction as ActionDescriptor, TeamAppActionResult } from '#server-types/teams'
import type { TeamAppAction, TeamAppContext, TeamAppEntry, TeamAppNavigationResponse } from './types.ts'

import axios from '@nextcloud/axios'
import { showConfirmation, showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { mdiMinus, mdiPlus } from '@mdi/js'
import { defineStore } from 'pinia'
import { markRaw, reactive } from 'vue'
import { promptForName } from '../components/TeamAppPrompt.vue'
import { logger } from '../../logger.ts'
import { getTeamAppNavigations } from '../api.ts'
import { getTeamAppProviders, getTeamAppRegistrationErrors } from './registry.ts'

export interface AppNavigationEntry extends TeamAppEntry {
	providerId: string
	entryId: string
}

interface ProviderNavigationState {
	navigation: TeamAppNavigationResponse
	loading: boolean
	error: boolean
	ready: boolean
}

export type TeamAppScope = Pick<TeamAppContext, 'teamId' | 'teamName' | 'isAdmin' | 'folder' | 'resources'>
	& { refresh: () => Promise<void>, open: (providerId: string, entryId: string) => void, sourceError?: boolean }

const contexts = new Map<string, TeamAppContext>()
const controllers = new Map<string, AbortController>()

/**
 *
 * @param teamId Team scope
 * @param providerId Owning app ID
 */
function key(teamId: string, providerId: string): string {
	return `${teamId}/${providerId}`
}

/**
 *
 * @param providerId Owning app ID
 * @param entryId App-local resource ID
 */
export function navigationId(providerId: string, entryId: string): string {
	return `${providerId}-${entryId}`
}

export const useTeamAppsStore = defineStore('teamApps', {
	state: () => ({
		providerStates: {} as Record<string, Record<string, ProviderNavigationState>>,
		busy: false,
	}),
	getters: {
		entries: (state) => (teamId: string): AppNavigationEntry[] => getTeamAppProviders().flatMap((provider) => {
			const providerState = state.providerStates[teamId]?.[provider.id]
			if (!providerState?.ready) {
				return []
			}
			const selectedEntryIds = new Set(providerState.navigation.selectedEntryIds ?? providerState.navigation.entries.map((entry) => entry.id))
			const context = contexts.get(key(teamId, provider.id))
			const bind = (action: ActionDescriptor): TeamAppAction => ({ ...action, run: () => executeAction(provider.id, action.id, context!) })
			return providerState.navigation.entries.filter((entry) => selectedEntryIds.has(entry.id)).map((entry) => ({
				...entry,
				actions: providerState.loading || providerState.error ? [] : [
					...(entry.actions?.map(bind) ?? []),
					...(context?.isAdmin ? [{
						id: `circles-remove-${provider.id}-${entry.id}`,
						label: t('circles', 'Remove from navigation'),
						icon: mdiMinus,
						run: () => setEntryNavigation(provider.id, entry.id, false, context),
					}] : []),
				],
				id: navigationId(provider.id, entry.id),
				providerId: provider.id,
				entryId: entry.id,
			}))
		}),
		actions: (state) => (teamId: string): TeamAppAction[] => getTeamAppProviders().flatMap((provider) => {
			const providerState = state.providerStates[teamId]?.[provider.id]
			if (!providerState?.ready || providerState.loading || providerState.error) {
				return []
			}
			const context = contexts.get(key(teamId, provider.id))
			const actions = providerState.navigation.actions?.map((action) => ({
				...action,
				icon: action.icon ?? mdiPlus,
				run: () => executeAction(provider.id, action.id, context!),
			})) ?? []
			if (!context?.isAdmin) {
				return actions
			}
			const selectedEntryIds = new Set(providerState.navigation.selectedEntryIds ?? providerState.navigation.entries.map((entry) => entry.id))
			return [
				...actions,
				...providerState.navigation.entries.filter((entry) => !selectedEntryIds.has(entry.id)).map((entry) => ({
					id: `circles-add-${provider.id}-${entry.id}`,
					label: t('circles', 'Add \'{name}\' to navigation', { name: entry.label }),
					icon: mdiPlus,
					run: () => setEntryNavigation(provider.id, entry.id, true, context),
				})),
			]
		}),
		allProvidersSettled: (state) => (teamId: string): boolean => getTeamAppProviders()
			.every((provider) => state.providerStates[teamId]?.[provider.id]?.loading === false),
		providerReady: (state) => (teamId: string, providerId: string): boolean => state.providerStates[teamId]?.[providerId]?.ready === true,
		errors: (state) => (teamId: string): string[] => [...new Set([
			...getTeamAppRegistrationErrors(),
			...Object.entries(state.providerStates[teamId] ?? {}).filter(([, providerState]) => providerState.error).map(([id]) => id),
		])],
	},
	actions: {
		async loadTeam(scope: TeamAppScope): Promise<void> {
			const controller = controllers.get(scope.teamId) ?? new AbortController()
			controllers.set(scope.teamId, controller)
			const signal = controller.signal
			const pending = getTeamAppProviders().map((provider) => {
				const id = key(scope.teamId, provider.id)
				const contextValues: TeamAppContext = {
					...scope,
					signal,
					open: (entryId) => {
						signal.throwIfAborted()
						scope.open(provider.id, entryId)
					},
					refresh: async () => {
						signal.throwIfAborted()
						await scope.refresh()
					},
					prompt: (title, value) => promptForName(title, value, signal),
					confirm: async (title, message) => {
						signal.throwIfAborted()
						const confirmed = await showConfirmation({
							name: title,
							text: message,
							labelConfirm: title,
							labelReject: t('circles', 'Cancel'),
							severity: 'warning',
						})
						signal.throwIfAborted()
						return confirmed
					},
				}
				const existingContext = contexts.get(id)
				const context = existingContext && !signal.aborted
					? Object.assign(existingContext, contextValues)
					: contextValues
				contexts.set(id, markRaw(context))
				this.providerStates[scope.teamId] ??= {}
				const previousProviderState = this.providerStates[scope.teamId]![provider.id]
				const providerState: ProviderNavigationState = reactive({
					navigation: previousProviderState?.navigation ?? { entries: [], selectedEntryIds: [] },
					loading: true,
					error: false,
					ready: previousProviderState?.ready ?? false,
				})
				this.providerStates[scope.teamId]![provider.id] = providerState
				return { provider, context, providerState }
			})
			if (pending.length === 0) {
				return
			}
			const navigationRequest = scope.sourceError
				? Promise.reject(new Error('Team folder or shared resources could not be loaded'))
				: getTeamAppNavigations(scope.teamId, signal)
			await Promise.all(pending.map(async ({ provider, context, providerState }) => {
				try {
					const result = await navigationRequest
					signal.throwIfAborted()
					if (result.errors.includes(provider.id)) {
						throw new Error('Teams app provider could not load navigation')
					}
					const navigation = result.providers[provider.id]
					if (!navigation) {
						throw new Error('Teams app provider navigation is missing')
					}
					validateNavigation(provider.id, navigation)
					if (this.providerStates[scope.teamId]?.[provider.id] === providerState) {
						const bind = (action: ActionDescriptor): TeamAppAction => ({ ...action, run: () => executeAction(provider.id, action.id, context) })
						providerState.navigation = {
							...navigation,
							entries: navigation.entries.map((entry) => ({ ...entry, actions: entry.actions?.map(bind) })),
							actions: navigation.actions?.map(bind),
						}
					}
				} catch (error) {
					if (!signal.aborted && this.providerStates[scope.teamId]?.[provider.id] === providerState) {
						providerState.error = true
						logger.error('Could not load Teams app navigation', { provider: provider.id, teamId: scope.teamId, error })
					}
				} finally {
					providerState.loading = false
					if (this.providerStates[scope.teamId]?.[provider.id] === providerState) {
						providerState.ready = true
					}
				}
			}))
		},
		context(teamId: string, providerId: string): TeamAppContext | undefined {
			return contexts.get(key(teamId, providerId))
		},
		async refresh(teamId: string): Promise<void> {
			const context = getTeamAppProviders().map((provider) => this.context(teamId, provider.id)).find(Boolean)
			if (context) {
				await context.refresh()
			}
		},
		unloadTeam(teamId: string): void {
			controllers.get(teamId)?.abort()
			controllers.delete(teamId)
			for (const provider of getTeamAppProviders()) {
				const id = key(teamId, provider.id)
				contexts.delete(id)
			}
			delete this.providerStates[teamId]
		},
		async run(action: TeamAppAction): Promise<void> {
			if (this.busy) {
				return
			}
			this.busy = true
			try {
				await action.run()
			} catch (error) {
				logger.error('Teams app action failed', { action: action.id, error })
				showError(t('circles', 'Could not complete the action'))
			} finally {
				this.busy = false
			}
		},
	},
})

/**
 *
 * @param providerId Owning app ID
 * @param navigation App-owned navigation and actions
 */
	function validateNavigation(providerId: string, navigation: TeamAppNavigationResponse): void {
	if (!navigation || !Array.isArray(navigation.entries)) {
		throw new Error('Invalid Teams navigation')
	}
	const ids = new Set<string>()
	for (const entry of navigation.entries) {
		if (!entry.id || navigationId(providerId, entry.id).length > 64 || ids.has(entry.id)
			|| !entry.label || !entry.href) {
			throw new Error('Invalid or duplicate Teams navigation entry')
		}
		if (![entry.href, entry.referenceUrl].every((url) => url === undefined || ['https:', 'http:'].includes(new URL(url, window.location.href).protocol))) {
			throw new Error('Unsafe Teams navigation URL')
		}
		ids.add(entry.id)
	}
}

/**
 *
 * @param providerId Owning app ID
 * @param actionId PHP-declared action ID
 * @param context Active team scope
 */
async function executeAction(providerId: string, actionId: string, context: TeamAppContext): Promise<void> {
	context.signal.throwIfAborted()
	const result = await getTeamAppNavigations(context.teamId, context.signal)
	context.signal.throwIfAborted()
	const latest = result.providers[providerId]
	if (result.errors.includes(providerId) || !latest) {
		throw new Error('Teams app provider could not load navigation')
	}
	validateNavigation(providerId, latest)
	const action = [...(latest.actions ?? []), ...latest.entries.flatMap((entry) => entry.actions ?? [])].find((action) => action.id === actionId)
	if (!action) {
		throw new Error('Teams action is no longer available')
	}
	const parameters: Record<string, unknown> = {}
	const dialog = action.dialog
	switch (dialog?.type) {
		case 'prompt': {
			const value = await context.prompt(dialog.title, dialog.value)
			if (value === null) {
				return
			}
			parameters.value = value
			break
		}
		case 'confirm':
			if (!await context.confirm(dialog.title, dialog.message)) {
				return
			}
			break
		case undefined:
			break
		default:
			throw new Error('Unsupported Teams action dialog')
	}
	context.signal.throwIfAborted()
	const { data } = await axios.post<{ ocs: { data: TeamAppActionResult } }>(generateOcsUrl('apps/circles/teams/{teamId}/apps/{providerId}/actions/{actionId}', { teamId: context.teamId, providerId, actionId }), { parameters }, { signal: context.signal })
	context.signal.throwIfAborted()
	await context.refresh()
	if (data.ocs.data.open) {
		context.open(data.ocs.data.open)
	}
}

/**
 *
 * @param providerId Owning app ID
 * @param entryId App-local navigation entry ID
 * @param inNavigation Whether the entry should be included in team navigation
 * @param context Active team scope
 */
async function setEntryNavigation(providerId: string, entryId: string, inNavigation: boolean, context: TeamAppContext): Promise<void> {
	context.signal.throwIfAborted()
	await axios.post(generateOcsUrl('apps/circles/teams/{teamId}/apps/{providerId}/entries/navigation', { teamId: context.teamId, providerId }), { entryId, inNavigation }, { signal: context.signal })
	context.signal.throwIfAborted()
	await context.refresh()
}
