/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it, vi } from 'vitest'
import { CIRCLES_MEMBER_LEVELS, compareLevels, MemberLevels } from './constants.ts'

vi.mock('../components/CircleDetails/CircleConfigs/CircleConfigCheckboxesList.vue', () => ({ default: {} }))
vi.mock('../components/CircleDetails/CircleConfigs/CircleConfigInvitationLink.vue', () => ({ default: {} }))

describe('member level ranks', () => {
	it('labels visitor separately from Member', () => {
		expect(MemberLevels.VISITOR).toBe(2)
		expect(CIRCLES_MEMBER_LEVELS[MemberLevels.VISITOR]).toBe('Visitor')
	})

	it('places visitor below Member while retaining the other role order', () => {
		const levels = [MemberLevels.NONE, MemberLevels.VISITOR, MemberLevels.MEMBER, MemberLevels.MODERATOR, MemberLevels.ADMIN, MemberLevels.OWNER]
		for (const [index, level] of levels.entries()) {
			if (index > 0) {
				expect(compareLevels(level, levels[index - 1])).toBeGreaterThan(0)
				expect(compareLevels(levels[index - 1], level)).toBeLessThan(0)
			}
		}
		expect(compareLevels(123, MemberLevels.NONE)).toBe(0)
		expect(compareLevels(MemberLevels.NONE, -1)).toBe(0)
	})
})