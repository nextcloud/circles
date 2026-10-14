/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { RouteLocationNormalizedLoaded, RouteLocationRaw } from 'vue-router'

/**
 * Keep Deck navigation within the selected team.
 *
 * @param teamId - Active team identifier.
 * @param boardId - Deck board identifier.
 * @param cardId - Optional card to open.
 */
export function teamDeckRoute(teamId: string, boardId: string | number, cardId: number | null = null): RouteLocationRaw {
	return {
		name: cardId === null ? 'team-deck' : 'team-deck-card',
		params: {
			teamId,
			boardId: String(boardId),
			...(cardId === null ? {} : { cardId: String(cardId) }),
		},
	}
}

/**
 * Identify both board and card routes.
 *
 * @param name - Current route name.
 */
export function isTeamDeckRoute(name: RouteLocationNormalizedLoaded['name']): boolean {
	return name === 'team-deck' || name === 'team-deck-card'
}

/**
 * Keep the mounted board across card changes, but not team or board changes.
 *
 * @param route - Current host route.
 */
export function teamViewKey(route: Pick<RouteLocationNormalizedLoaded, 'name' | 'params' | 'path'>): string {
	return isTeamDeckRoute(route.name)
		? `deck:${route.params.teamId}:${route.params.boardId}`
		: route.path
}

/**
 * Validate identifiers from route parameters and custom-element events.
 *
 * @param value - Untrusted identifier.
 */
export function positiveDeckId(value: unknown): number | null {
	if (typeof value !== 'number' && (typeof value !== 'string' || !/^[1-9]\d*$/.test(value))) {
		return null
	}
	const id = Number(value)
	return Number.isSafeInteger(id) && id > 0 ? id : null
}
