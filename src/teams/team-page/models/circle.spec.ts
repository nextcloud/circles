/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it, vi } from 'vitest'
import Circle from './circle.ts'
import Member from './member.ts'

vi.mock('@nextcloud/dialogs', () => ({ showError: vi.fn() }))

function circleData(canViewMembers?: boolean, level = 1) {
	return {
		id: 'team',
		owner: { id: 'owner', level: 9 },
		initiator: { id: 'actor', level },
		canViewMembers,
	}
}

describe('Circle member visibility', () => {
	it.each([1, 4, 8, 9])('respects a denied capability for role %s', (level) => {
		expect(new Circle(circleData(false, level)).canViewMembers).toBe(false)
	})

	it('preserves member access on older servers without the capability field', () => {
		expect(new Circle(circleData()).canViewMembers).toBe(true)
	})

	it('does not grant pending members access', () => {
		expect(new Circle(circleData(true, 0)).canViewMembers).toBe(false)
	})

	it('clears cached members when visibility is revoked', () => {
		const circle = new Circle(circleData(true))
		circle.addMember(new Member({ id: 'alice', singleId: 'alice' }, circle))

		circle.updateData(circleData(false))

		expect(circle.members).toEqual({})
		expect(circle.canViewMembers).toBe(false)
	})

	it('clears the initiator and cached members after leaving', () => {
		const circle = new Circle(circleData(true))
		circle.addMember(new Member({ id: 'alice', singleId: 'alice' }, circle))

		circle.updateData({ ...circleData(), initiator: null })

		expect(circle.initiator).toBeNull()
		expect(circle.canViewMembers).toBe(false)
		expect(circle.members).toEqual({})
	})
})
