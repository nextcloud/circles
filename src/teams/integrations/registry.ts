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

const providers = new Map<string, TeamAppProvider>()
const errors = new Set<string>()
let initialized = false

/**
 *
 */
export function initializeTeamApps(): void {
	if (initialized) {
		return
	}
	initialized = true
	const expected = loadState<{ providers: Array<{ id: string, order: number }>, errors: string[] }>('circles', 'teamAppProviders')
	expected.errors.forEach((id) => errors.add(id))
	for (const { id, order } of expected.providers) {
		try {
			if (!/^[a-z][a-z0-9_]*$/.test(id) || providers.has(id) || !Number.isFinite(order)) {
				throw new Error('Invalid or duplicate PHP provider descriptor')
			}
			const provider: TeamAppProvider = {
				id,
				order,
				async getNavigation(context) {
					const { data } = await axios.get<{ ocs: { data: TeamAppNavigation } }>(generateOcsUrl('apps/circles/teams/{teamId}/apps/{providerId}/navigation', { teamId: context.teamId, providerId: id }), { signal: context.signal })
					return data.ocs.data
				},
			}
			providers.set(id, provider)
		} catch (error) {
			errors.add(id)
			providers.delete(id)
			logger.error('Could not register Teams app provider', { provider: id, error })
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
