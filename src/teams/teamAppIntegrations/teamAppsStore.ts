/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TeamAppContext, TeamAppNavigationAction, TeamAppNavigationResponse, TeamReference } from './types.ts'

import { mdiPlus } from '@mdi/js'
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { defineStore } from 'pinia'
import { markRaw, reactive } from 'vue'
import { logger } from '../../logger.ts'
import { getTeamAppNavigations, resolveTeamReferences } from '../api.ts'
import { useTeamResourcesStore } from '../resourcesStore.ts'
import { getTeamAppRegistrationErrors, getTeamOwnedResourceProviders } from './providerRegistry.ts'

export interface AppNavigationEntry {
	id: string
	label: string
	href: string
	referenceUrl: string
	icon?: string
	iconSvg?: string
	iconURL?: string
	reference?: TeamReference
	providerId: string
	entryId: string
}

interface ProviderNavigationState {
	navigation: TeamAppNavigationResponse
	references: Record<string, TeamReference | null>
	loading: boolean
	error: boolean
	ready: boolean
}

export type TeamAppScope = Pick<TeamAppContext, 'teamId' | 'teamName' | 'isAdmin' | 'folder' | 'resources'>
	& { refresh: () => Promise<void>, open: (providerId: string, entryId: string) => void, sourceError?: boolean }

const contexts = new Map<string, TeamAppContext>()
const controllers = new Map<string, AbortController>()
const navigationLoads = new Map<string, symbol>()

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
		entries: (state) => (teamId: string): AppNavigationEntry[] => useTeamResourcesStore().forTeam(teamId).navigation.flatMap((item) => {
			if (!('providerId' in item)) {
				return []
			}
			const providerState = state.providerStates[teamId]?.[item.providerId]
			if (!providerState?.ready) {
				return []
			}
			const entry = providerState.navigation.entries.find((entry) => entry.id === item.entryId)
			return entry
				? [{
						...item,
						label: entry.label,
						href: entry.url,
						referenceUrl: entry.referenceUrl ?? entry.url,
						icon: entry.iconEmoji || undefined,
						iconSvg: entry.iconSvg || (!entry.iconURL ? entry.provider.icon : undefined) || undefined,
						iconURL: entry.iconURL || undefined,
						reference: providerState.references[new URL(entry.referenceUrl ?? entry.url, window.location.href).href] ?? undefined,
					}]
				: []
		}),
		actions: (state) => (teamId: string): TeamAppNavigationAction[] => getTeamOwnedResourceProviders().flatMap((provider) => {
			const providerState = state.providerStates[teamId]?.[provider.id]
			if (!providerState?.ready || providerState.loading || providerState.error) {
				return []
			}
			const context = contexts.get(key(teamId, provider.id))
			return context?.isAdmin && providerState.navigation.canAddResource
				? [{
						id: `add-${provider.id}`,
						label: t('circles', 'Add {appName}', { appName: provider.name }),
						iconUrl: provider.iconUrl,
						icon: mdiPlus,
						run: () => createResource(provider.id, context, () => useTeamResourcesStore().forTeam(teamId).navigation.map(({ id }) => id)),
					}]
				: []
		}),
		allProvidersSettled: (state) => (teamId: string): boolean => getTeamOwnedResourceProviders()
			.every((provider) => state.providerStates[teamId]?.[provider.id]?.loading === false),
		providerReady: (state) => (teamId: string, providerId: string): boolean => state.providerStates[teamId]?.[providerId]?.ready === true,
		errors: (state) => (teamId: string): string[] => [...new Set([
			...getTeamAppRegistrationErrors(),
			...Object.entries(state.providerStates[teamId] ?? {}).filter(([, providerState]) => providerState.error).map(([id]) => id),
		])],
	},
	actions: {
		async loadTeam(scope: TeamAppScope): Promise<void> {
			const load = Symbol()
			navigationLoads.set(scope.teamId, load)
			const controller = controllers.get(scope.teamId) ?? new AbortController()
			controllers.set(scope.teamId, controller)
			const signal = controller.signal
			const pending = getTeamOwnedResourceProviders().map((provider) => {
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
				}
				const existingContext = contexts.get(id)
				const context = existingContext && !signal.aborted
					? Object.assign(existingContext, contextValues)
					: contextValues
				contexts.set(id, markRaw(context))
				this.providerStates[scope.teamId] ??= {}
				const previousProviderState = this.providerStates[scope.teamId]![provider.id]
				const providerState: ProviderNavigationState = reactive({
					navigation: previousProviderState?.navigation ?? { entries: [], canAddResource: false },
					references: previousProviderState?.references ?? {},
					loading: true,
					error: false,
					ready: previousProviderState?.ready ?? false,
				})
				this.providerStates[scope.teamId]![provider.id] = providerState
				return { provider, context, providerState }
			})
			const resources = useTeamResourcesStore()
			const resourceState = resources.slot(scope.teamId)
			const navigationResponse = scope.sourceError
				? Promise.reject(new Error('Team folder or shared resources could not be loaded'))
				: getTeamAppNavigations(scope.teamId, signal)
			const navigationRequest = navigationResponse.then((result) => {
				signal.throwIfAborted()
				if (navigationLoads.get(scope.teamId) === load) {
					resources.applyNavigation(scope.teamId, result.entries)
					resourceState.orderChecked = true
					resourceState.orderError = false
				}
				return result
			}, (error: unknown) => {
				if (!signal.aborted && navigationLoads.get(scope.teamId) === load) {
					resourceState.orderError = true
				}
				throw error
			})
			if (pending.length === 0) {
				try {
					await navigationRequest
				} catch (error) {
					if (!signal.aborted) {
						logger.error('Could not load Teams navigation order', { teamId: scope.teamId, error })
					}
				}
				return
			}
			const referenceRequest = navigationRequest.then(async (result) => {
				const urls = result.entries.flatMap((item) => {
					if (!('providerId' in item) || result.errors.includes(item.providerId)) {
						return []
					}
					const entries = result.providers[item.providerId]?.entries
					const entry = Array.isArray(entries) ? entries.find((entry) => entry?.id === item.entryId) : undefined
					const url = entry?.referenceUrl ?? entry?.url
					return typeof url === 'string' && url ? [url] : []
				})
				try {
					return await resolveTeamReferences(urls, signal)
				} catch (error) {
					if (!signal.aborted) {
						logger.warn('Could not resolve Teams navigation references; opening resources as links', { teamId: scope.teamId, error })
					}
					return {}
				}
			})
			await Promise.all(pending.map(async ({ provider, providerState }) => {
				try {
					const [result, references] = await Promise.all([navigationRequest, referenceRequest])
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
						providerState.navigation = navigation
						providerState.references = references
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
			const context = getTeamOwnedResourceProviders().map((provider) => this.context(teamId, provider.id)).find(Boolean)
			if (context) {
				await context.refresh()
			}
		},
		unloadTeam(teamId: string): void {
			navigationLoads.delete(teamId)
			controllers.get(teamId)?.abort()
			controllers.delete(teamId)
			for (const provider of getTeamOwnedResourceProviders()) {
				const id = key(teamId, provider.id)
				contexts.delete(id)
			}
			delete this.providerStates[teamId]
		},
		async run(action: TeamAppNavigationAction): Promise<void> {
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
 * @param navigation App-owned navigation and optional create action
 */
function validateNavigation(providerId: string, navigation: TeamAppNavigationResponse): void {
	if (!navigation || !Array.isArray(navigation.entries)) {
		throw new Error('Invalid Teams navigation')
	}
	const ids = new Set<string>()
	for (const entry of navigation.entries) {
		if (!entry.id || navigationId(providerId, entry.id).length > 64 || ids.has(entry.id)
			|| !entry.label || !entry.url || entry.provider?.id !== providerId) {
			throw new Error('Invalid or duplicate Teams navigation entry')
		}
		if ((entry.referenceUrl !== undefined && entry.referenceUrl !== null && (typeof entry.referenceUrl !== 'string' || !entry.referenceUrl))
			|| ![entry.url, entry.referenceUrl, entry.iconURL].every((url) => !url || ['https:', 'http:'].includes(new URL(url, window.location.href).protocol))) {
			throw new Error('Unsafe Teams navigation URL')
		}
		ids.add(entry.id)
	}
}

/**
 *
 * @param providerId Owning app ID
 * @param context Active team scope
 * @param getOrder Current navigation order
 */
async function createResource(providerId: string, context: TeamAppContext, getOrder: () => string[]): Promise<void> {
	context.signal.throwIfAborted()
	const result = await getTeamAppNavigations(context.teamId, context.signal)
	context.signal.throwIfAborted()
	const latest = result.providers[providerId]
	if (result.errors.includes(providerId) || !latest) {
		throw new Error('Teams app provider could not load navigation')
	}
	validateNavigation(providerId, latest)
	if (!latest.canAddResource) {
		throw new Error('Adding a resource is no longer available for this team')
	}
	context.signal.throwIfAborted()
	const { data } = await axios.post<{ ocs: { data: string } }>(generateOcsUrl('apps/circles/teams/{teamId}/apps/{providerId}/resources', { teamId: context.teamId, providerId }), { order: getOrder() }, { signal: context.signal })
	context.signal.throwIfAborted()
	await context.refresh()
	context.open(data.ocs.data)
}
