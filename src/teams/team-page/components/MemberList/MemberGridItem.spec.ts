/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it, vi } from 'vitest'
import MemberGridItem from './MemberGridItem.vue'
import { MemberLevels, MemberStatus } from '../../models/constants.ts'

vi.mock('@nextcloud/dialogs', () => ({}))

const isPendingApproval = (member: { level: number, status: string }) => MemberGridItem.computed!.isPendingApproval.call({ member })

describe('MemberGridItem isPendingApproval', () => {
	it('is true for a join request awaiting moderator approval', () => {
		expect(isPendingApproval({ level: MemberLevels.NONE, status: MemberStatus.REQUESTING })).toBe(true)
	})

	it('is false for an invited member', () => {
		expect(isPendingApproval({ level: MemberLevels.NONE, status: MemberStatus.INVITED })).toBe(false)
	})

	it('is false for an existing member', () => {
		expect(isPendingApproval({ level: MemberLevels.MEMBER, status: MemberStatus.MEMBER })).toBe(false)
	})
})
