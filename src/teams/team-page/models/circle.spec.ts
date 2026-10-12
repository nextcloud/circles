/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it, vi } from 'vitest'
import Circle from './circle.ts'
import { CircleConfigs, MemberLevels } from './constants.ts'

vi.mock('../components/CircleDetails/CircleConfigs/CircleConfigCheckboxesList.vue', () => ({ default: {} }))
vi.mock('../components/CircleDetails/CircleConfigs/CircleConfigInvitationLink.vue', () => ({ default: {} }))

describe('team member controls', () => {
	it('identifies federated circles', () => {
		const federated = new Circle({ id: 'federated', config: CircleConfigs.FEDERATED, owner: {}, initiator: {} })
		const local = new Circle({ id: 'local', config: 0, owner: {}, initiator: {} })
		expect(federated.isFederated).toBe(true)
		expect(local.isFederated).toBe(false)
	})

	it.each([0, CircleConfigs.FRIEND])('keeps visitor active but unable to manage members with config %i', (config) => {
		const circle = new Circle({ id: 'team', config, owner: { level: 9 }, initiator: { level: MemberLevels.VISITOR } })
		expect(circle.isMember).toBe(true)
		expect(circle.canLeave).toBe(true)
		expect(circle.canManageMembers).toBe(false)
	})

	it('retains Member FRIEND invites and Moderator management', () => {
		const member = new Circle({ id: 'team', config: CircleConfigs.FRIEND, owner: {}, initiator: { level: MemberLevels.MEMBER } })
		const moderator = new Circle({ id: 'team', config: 0, owner: {}, initiator: { level: MemberLevels.MODERATOR } })
		expect(member.canManageMembers).toBe(true)
		expect(moderator.canManageMembers).toBe(true)
	})
})