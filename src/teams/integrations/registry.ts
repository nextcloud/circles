/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TeamAppNavigation } from '#server-types/teams'
import type { TeamAppProvider } from './types.ts'

import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { generateOcsUrl } from '@nextcloud/router'
import { logger } from '../../logger.ts'
import { loadTeamAppModule } from './module.ts'

const providers = new Map<string, TeamAppProvider>()
const errors = new Set<string>()
let initialized = false

/**
 *
 */
export async function initializeTeamApps(): Promise<void> {
	if (initialized) {
		return
	}
	initialized = true
	const expected = loadState<{ providers: Array<{ id: string, order: number, routeAliases: NonNullable<TeamAppProvider['routeAliases']>, module: string | null }>, errors: string[] }>('circles', 'teamAppProviders')
	expected.errors.forEach((id) => errors.add(id))
	for (const { id, order, routeAliases, module } of expected.providers) {
		try {
			if (!/^[a-z][a-z0-9_]*$/.test(id) || providers.has(id) || !Number.isFinite(order)) {
				throw new Error('Invalid or duplicate PHP provider descriptor')
			}
			const provider: TeamAppProvider = {
				id,
				order,
				routeAliases,
				async load(context) {
					const { data } = await axios.get<{ ocs: { data: TeamAppNavigation } }>(generateOcsUrl('apps/circles/teams/{teamId}/apps/{providerId}/navigation', { teamId: context.teamId, providerId: id }), { signal: context.signal })
					return data.ocs.data
				},
			}
			if (module !== null) {
				const url = new URL(module, window.location.href)
				if (url.origin !== window.location.origin || !/\.m?js$/.test(url.pathname)) {
					throw new Error('Teams app modules must be same-origin JavaScript assets')
				}
				const { apiVersion, default: view } = await loadTeamAppModule(url.href)
				if (apiVersion !== 2 || !view || typeof view.mount !== 'function') {
					throw new Error('Invalid or incompatible Teams embedded-view module')
				}
				provider.mount = view.mount
			}
			const aliases = new Set<string>()
			for (const alias of provider.routeAliases ?? []) {
				if (!/^[a-z][a-z0-9-]*\/:[a-zA-Z]+$/.test(alias.path)
					|| !alias.path.endsWith(`:${alias.entryParam}`) || aliases.has(alias.path)
					|| [...providers.values()].some((other) => other.routeAliases?.some((otherAlias) => otherAlias.path === alias.path))) {
					throw new Error('Invalid or duplicate Teams route alias')
				}
				aliases.add(alias.path)
			}
			providers.set(id, provider)
		} catch (error) {
			errors.add(id)
			providers.delete(id)
			logger.error('Could not load Teams app module', { provider: id, error })
		}
	}
}

/**
 *
 */
export function getTeamAppProviders(): TeamAppProvider[] {
	return [...providers.values()].filter((provider) => !errors.has(provider.id))
		.sort((a, b) => a.order - b.order || a.id.localeCompare(b.id))
}

/**
 *
 * @param id Owning app ID
 */
export function getTeamAppProvider(id: string): TeamAppProvider | undefined {
	return getTeamAppProviders().find((provider) => provider.id === id)
}

/**
 *
 */
export function getTeamAppRegistrationErrors(): string[] {
	return [...errors]
}
