/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it } from 'vitest'
import { isTeamDeckRoute, positiveDeckId, teamDeckRoute, teamViewKey } from './deck.ts'

describe('embedded Deck routes', () => {
	it('builds board and card routes with canonical string identifiers', () => {
		expect(teamDeckRoute('team-1', 7)).toEqual({
			name: 'team-deck',
			params: { teamId: 'team-1', boardId: '7' },
		})
		expect(teamDeckRoute('team-1', 7, 9)).toEqual({
			name: 'team-deck-card',
			params: { teamId: 'team-1', boardId: '7', cardId: '9' },
		})
	})

	it('keeps the same view key for a board and its cards', () => {
		const board = { name: 'team-deck', params: { teamId: 'team-1', boardId: '7' }, path: '/team/team-1/deck/7' }
		const card = { name: 'team-deck-card', params: { ...board.params, cardId: '9' }, path: `${board.path}/card/9` }
		expect(teamViewKey(board)).toBe(teamViewKey(card))
		expect(teamViewKey({ ...board, params: { ...board.params, boardId: '8' } })).not.toBe(teamViewKey(board))
		expect(teamViewKey({ ...board, params: { ...board.params, teamId: 'team-2' } })).not.toBe(teamViewKey(board))
	})

	it('preserves path-based remounting for existing team pages', () => {
		expect(teamViewKey({ name: 'team-page', params: {}, path: '/team/team-1/page/5' })).toBe('/team/team-1/page/5')
		expect(isTeamDeckRoute('team-folder')).toBe(false)
		expect(isTeamDeckRoute('team-deck-card')).toBe(true)
	})
})

describe('Deck identifier validation', () => {
	it.each([null, undefined, '', '0', 0, -1, '1.5', '1e2', ' 7', NaN, Infinity, Number.MAX_SAFE_INTEGER + 1])('rejects %s', (value) => {
		expect(positiveDeckId(value)).toBeNull()
	})

	it('accepts numeric and string positive safe integers', () => {
		expect(positiveDeckId(7)).toBe(7)
		expect(positiveDeckId('7')).toBe(7)
	})
})
