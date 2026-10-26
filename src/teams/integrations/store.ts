/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TeamAppAction as ActionDescriptor, TeamAppNavigation as NavigationDescriptor, TeamAppActionResult } from '#server-types/teams'
import type { TeamAppAction, TeamAppContext, TeamAppEntry, TeamAppNavigation } from './types.ts'

import axios from '@nextcloud/axios'
import { showConfirmation, showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { defineStore } from 'pinia'
import { markRaw, reactive } from 'vue'
import { logger } from '../../logger.ts'
import { promptForName } from './prompt.ts'
import { getTeamAppProvider, getTeamAppProviders, getTeamAppRegistrationErrors } from './registry.ts'
import { chooseTeamResources } from './resourcePicker.ts'

export interface AppNavigationEntry extends TeamAppEntry {
	providerId: string
	entryId: string
}

interface ProviderSlot {
	navigation: TeamAppNavigation
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
	return `${providerId}:${entryId}`
}

export const useTeamAppsStore = defineStore('teamApps', {
	state: () => ({
		slots: {} as Record<string, Record<string, ProviderSlot>>,
		busy: false,
	}),
	getters: {
		entries: (state) => (teamId: string): AppNavigationEntry[] => getTeamAppProviders().flatMap((provider) => {
			const slot = state.slots[teamId]?.[provider.id]
			if (!slot?.ready) {
				return []
			}
			return slot.navigation.entries.map((entry) => ({
				...entry,
				actions: slot.loading || slot.error ? [] : entry.actions,
				id: navigationId(provider.id, entry.id),
				providerId: provider.id,
				entryId: entry.id,
			}))
		}),
		actions: (state) => (teamId: string): TeamAppAction[] => getTeamAppProviders().flatMap((provider) => {
			const slot = state.slots[teamId]?.[provider.id]
			return !slot?.ready || slot.loading || slot.error ? [] : slot.navigation.actions ?? []
		}),
		settled: (state) => (teamId: string): boolean => getTeamAppProviders()
			.every((provider) => state.slots[teamId]?.[provider.id]?.loading === false),
		providerReady: (state) => (teamId: string, providerId: string): boolean => state.slots[teamId]?.[providerId]?.ready === true,
		errors: (state) => (teamId: string): string[] => [...new Set([
			...getTeamAppRegistrationErrors(),
			...Object.entries(state.slots[teamId] ?? {}).filter(([, slot]) => slot.error).map(([id]) => id),
		])],
		resourceKeys: (state) => (teamId: string): Set<string> => new Set(Object.values(state.slots[teamId] ?? {})
			.filter((slot) => slot.ready && !slot.error)
			.flatMap((slot) => slot.navigation.resourceKeys ?? [])
			.map((resource) => JSON.stringify([resource.providerId, resource.resourceId]))),
	},
	actions: {
		async loadTeam(scope: TeamAppScope): Promise<void> {
			await Promise.all(getTeamAppProviders().map(async (provider) => {
				const id = key(scope.teamId, provider.id)
				const controller = controllers.get(id) ?? new AbortController()
				controllers.set(id, controller)
				const signal = controller.signal
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
					getNavigationEntryIds: async () => {
						signal.throwIfAborted()
						const { data } = await axios.get<{ ocs: { data: { navigationEntryIds: string[] } } }>(
							generateOcsUrl('apps/circles/teams/{teamId}/apps/{providerId}/navigation/selection', { teamId: scope.teamId, providerId: provider.id }),
							{ signal },
						)
						return data.ocs.data.navigationEntryIds
					},
					setNavigationEntryIds: async (navigationEntryIds) => {
						signal.throwIfAborted()
						await axios.put(generateOcsUrl('apps/circles/teams/{teamId}/apps/{providerId}/navigation/selection', { teamId: scope.teamId, providerId: provider.id }), { navigationEntryIds }, { signal })
					},
					chooseResources: (options) => chooseTeamResources(options, signal),
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
				this.slots[scope.teamId] ??= {}
				const previousSlot = this.slots[scope.teamId]![provider.id]
				const slot: ProviderSlot = reactive({
					navigation: previousSlot?.navigation ?? { entries: [] },
					loading: true,
					error: false,
					ready: previousSlot?.ready ?? false,
				})
				this.slots[scope.teamId]![provider.id] = slot
				try {
					if (scope.sourceError) {
						throw new Error('Team folder or shared resources could not be loaded')
					}
					const navigation = await provider.load(context)
					signal.throwIfAborted()
					validateNavigation(provider.id, navigation)
					if (this.slots[scope.teamId]?.[provider.id] === slot) {
						const bind = (action: ActionDescriptor): TeamAppAction => ({ ...action, run: () => executeAction(provider.id, action.id, context) })
						slot.navigation = {
							...navigation,
							entries: navigation.entries.map((entry) => ({ ...entry, actions: entry.actions?.map(bind) })),
							actions: navigation.actions?.map(bind),
						}
					}
				} catch (error) {
					if (!signal.aborted && this.slots[scope.teamId]?.[provider.id] === slot) {
						slot.error = true
						logger.error('Could not load Teams app navigation', { provider: provider.id, teamId: scope.teamId, error })
					}
				} finally {
					slot.loading = false
					if (this.slots[scope.teamId]?.[provider.id] === slot) {
						slot.ready = true
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
			for (const provider of getTeamAppProviders()) {
				const id = key(teamId, provider.id)
				controllers.get(id)?.abort()
				controllers.delete(id)
				contexts.delete(id)
			}
			delete this.slots[teamId]
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
function validateNavigation(providerId: string, navigation: NavigationDescriptor): void {
	if (!navigation || !Array.isArray(navigation.entries)) {
		throw new Error('Invalid Teams navigation')
	}
	const ids = new Set<string>()
	for (const entry of navigation.entries) {
		if (!entry.id || navigationId(providerId, entry.id).length > 64 || ids.has(entry.id)
			|| !entry.label || (!entry.href && !getTeamAppProvider(providerId)?.mount)) {
			throw new Error('Invalid or duplicate Teams navigation entry')
		}
		if (entry.href && !['https:', 'http:'].includes(new URL(entry.href, window.location.href).protocol)) {
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
	const latest = await getTeamAppProvider(providerId)!.load(context)
	const action = [...(latest.actions ?? []), ...latest.entries.flatMap((entry) => entry.actions ?? [])].find((action) => action.id === actionId)
	if (!action) {
		throw new Error('Teams action is no longer available')
	}
	const parameters: Record<string, unknown> = {}
	if (action.prompt) {
		const value = await context.prompt(action.prompt.title, action.prompt.value)
		if (value === null) {
			return
		}
		parameters.value = value
	}
	if (action.confirm && !await context.confirm(action.confirm.title, action.confirm.message)) {
		return
	}
	if (action.resourcePicker) {
		const ids = await context.chooseResources(action.resourcePicker)
		if (ids === null) {
			return
		}
		parameters.ids = ids
	}
	context.signal.throwIfAborted()
	const { data } = await axios.post<{ ocs: { data: TeamAppActionResult } }>(generateOcsUrl('apps/circles/teams/{teamId}/apps/{providerId}/actions/{actionId}', { teamId: context.teamId, providerId, actionId }), { parameters }, { signal: context.signal })
	context.signal.throwIfAborted()
	await context.refresh()
	if (data.ocs.data.open) {
		context.open(data.ocs.data.open)
	}
}
