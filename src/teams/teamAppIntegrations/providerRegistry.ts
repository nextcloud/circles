/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TeamOwnedResourceProvider } from './types.ts'

import { loadState } from '@nextcloud/initial-state'
import { logger } from '../../logger.ts'

const providers = new Map<string, TeamOwnedResourceProvider>()
const errors = new Set<string>()
const prioritizedProviderIds = ['files', 'collectives', 'deck']
let initialized = false

/**
 *
 */
export function initializeTeamApps(): void {
	if (initialized) {
		return
	}
	initialized = true
	const expected = loadState<{ providers: TeamOwnedResourceProvider[], errors: string[] }>('circles', 'teamAppProviders')
	expected.errors.forEach((id) => errors.add(id))
	for (const provider of expected.providers) {
		const { id, name } = provider
		try {
			if (!/^[a-z][a-z0-9_]*$/.test(id) || providers.has(id) || !name.trim()) {
				throw new Error('Invalid or duplicate PHP provider descriptor')
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
export function getTeamOwnedResourceProviders(): TeamOwnedResourceProvider[] {
	return [...providers.values()].filter((provider) => !errors.has(provider.id))
		.sort((a, b) => {
			const aPriority = prioritizedProviderIds.indexOf(a.id)
			const bPriority = prioritizedProviderIds.indexOf(b.id)
			const priorityDifference = (aPriority === -1 ? prioritizedProviderIds.length : aPriority)
				- (bPriority === -1 ? prioritizedProviderIds.length : bPriority)
			return priorityDifference || a.name.localeCompare(b.name) || a.id.localeCompare(b.id)
		})
}

/**
 *
 * @param id Owning app ID
 */
export function getTeamOwnedResourceProvider(id: string): TeamOwnedResourceProvider | undefined {
	return getTeamOwnedResourceProviders().find((provider) => provider.id === id)
}

/**
 *
 */
export function getTeamAppRegistrationErrors(): string[] {
	return [...errors]
}
