/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import Circle from '../../models/circle.ts'
import Member from '../../models/member.ts'
import { CircleConfigs, MemberLevels, MemberStatus } from '../../models/constants.ts'
import MemberGridItem from './MemberGridItem.vue'
import { changeMemberLevel } from '../../services/circles.ts'

vi.mock('@nextcloud/vue', () => ({
	NcActionButton: { name: 'NcActionButton', template: '<button><slot /></button>' },
	NcActions: { name: 'NcActions', template: '<div><slot /></div>' },
	NcActionSeparator: { name: 'NcActionSeparator', template: '<div />' },
	NcActionText: { name: 'NcActionText', template: '<div><slot /></div>' },
	NcAvatar: { name: 'NcAvatar', template: '<div />' },
	NcButton: { name: 'NcButton', template: '<button><slot /></button>' },
}))
vi.mock('@nextcloud/dialogs', () => ({ DialogBuilder: vi.fn(), showError: vi.fn(), showSuccess: vi.fn() }))
vi.mock('../../services/circles.ts', () => ({ changeMemberLevel: vi.fn() }))
vi.mock('../CircleDetails/CircleConfigs/CircleConfigCheckboxesList.vue', () => ({ default: {} }))
vi.mock('../CircleDetails/CircleConfigs/CircleConfigInvitationLink.vue', () => ({ default: {} }))

function mountItem(actorLevel: number, targetLevel: number, config = 0, targetId = 'target', targetLocal = true) {
	const circle = new Circle({ id: 'team', displayName: 'Team', config, owner: { id: 'owner', level: 9 }, initiator: { id: 'actor', singleId: 'actor', level: actorLevel, local: true } })
	const status = targetLevel === MemberLevels.NONE ? MemberStatus.REQUESTING : MemberStatus.MEMBER
	const member = new Member({ id: targetId, singleId: targetId, displayName: 'Target', level: targetLevel, status, local: targetLocal }, circle)
	const dispatch = vi.fn().mockResolvedValue(undefined)
	const wrapper = shallowMount(MemberGridItem, {
		props: { member },
		global: {
			mocks: {
				$route: { params: { teamId: 'team' } },
				$store: { getters: { getCircle: () => circle }, dispatch },
				logger: { error: vi.fn(), debug: vi.fn() },
				t: (_app: string, text: string) => text,
			},
		},
	})
	return { wrapper, member, dispatch }
}

describe('MemberGridItem Visitor actions', () => {
	beforeEach(() => vi.mocked(changeMemberLevel).mockReset())

	it('offers Visitor to an Admin targeting another Admin without a rank restriction', () => {
		const { wrapper } = mountItem(8, 8)
		expect(wrapper.vm.availableLevelsChange).toContain(MemberLevels.VISITOR)
		expect(wrapper.vm.levelChangeLabel(MemberLevels.VISITOR)).toBe('Demote to Visitor')
	})

	it('hides Visitor in federated teams', () => {
		const { wrapper } = mountItem(8, 8, CircleConfigs.FEDERATED)
		expect(wrapper.vm.availableLevelsChange).not.toContain(MemberLevels.VISITOR)
	})

	it('hides Visitor for remote members', () => {
		const { wrapper } = mountItem(8, 8, 0, 'target', false)
		expect(wrapper.vm.availableLevelsChange).not.toContain(MemberLevels.VISITOR)
	})

	it('offers Member as a promotion from Visitor and never offers the selected role', () => {
		const { wrapper } = mountItem(8, 2)
		expect(wrapper.vm.availableLevelsChange).not.toContain(MemberLevels.VISITOR)
		expect(wrapper.vm.availableLevelsChange).toContain(MemberLevels.MEMBER)
		expect(wrapper.vm.levelChangeLabel(MemberLevels.MEMBER)).toBe('Promote to Member')
	})

	it('keeps the Moderator menu unchanged', () => {
		const { wrapper } = mountItem(4, 1)
		expect(wrapper.vm.availableLevelsChange).toEqual([])
	})

	it.each([MemberLevels.OWNER, MemberLevels.NONE])('protects target level %i', (level) => {
		const { wrapper } = mountItem(9, level)
		expect(wrapper.vm.availableLevelsChange).toEqual([])
		expect(wrapper.vm.canChangeLevel).toBe(false)
	})

	it('protects the current user', () => {
		const { wrapper } = mountItem(9, 9, 0, 'actor')
		expect(wrapper.vm.availableLevelsChange).toEqual([])
	})

	it('prevents Visitor removal in FRIEND teams but retains leaving', () => {
		const { wrapper } = mountItem(2, 1, CircleConfigs.FRIEND)
		expect(wrapper.vm.canDelete).toBe(false)
		expect(wrapper.vm.canChangeLevel).toBe(false)
	})

	it('does not allow a Member to delete a Visitor without member-management rights', () => {
		const { wrapper } = mountItem(MemberLevels.MEMBER, MemberLevels.VISITOR)
		expect(wrapper.vm.canDelete).toBe(false)
	})

	it('uses role rank when a Member can manage a Visitor in a FRIEND team', () => {
		const { wrapper } = mountItem(MemberLevels.MEMBER, MemberLevels.VISITOR, CircleConfigs.FRIEND)
		expect(wrapper.vm.canDelete).toBe(true)
	})

	it('updates only after a successful request', async () => {
		const { wrapper, member } = mountItem(8, 1)
		vi.mocked(changeMemberLevel).mockResolvedValue([])
		await wrapper.vm.changeLevel(MemberLevels.VISITOR)
		expect(changeMemberLevel).toHaveBeenCalledWith('team', 'target', 2)
		expect(member.level).toBe(2)
		expect(wrapper.vm.loading).toBe(false)
	})

	it.each(['backend rejection', 'network failure'])('%s leaves the role unchanged and permits retry', async () => {
		const { wrapper, member } = mountItem(8, 1)
		vi.mocked(changeMemberLevel).mockRejectedValueOnce(new Error('unavailable')).mockResolvedValueOnce([])
		await wrapper.vm.changeLevel(MemberLevels.VISITOR)
		expect(member.level).toBe(1)
		expect(wrapper.vm.loading).toBe(false)
		await wrapper.vm.changeLevel(MemberLevels.VISITOR)
		expect(member.level).toBe(2)
		expect(changeMemberLevel).toHaveBeenCalledTimes(2)
	})

	it('refetches both circle and members on owner transfer', async () => {
		const { wrapper, member, dispatch } = mountItem(9, 2)
		vi.mocked(changeMemberLevel).mockResolvedValue([])
		await wrapper.vm.changeLevel(MemberLevels.OWNER)
		expect(dispatch).toHaveBeenCalledWith('getCircle', 'team')
		expect(dispatch).toHaveBeenCalledWith('getCircleMembers', { circleId: 'team' })
		expect(member.level).toBe(2)
		expect(wrapper.vm.loading).toBe(false)
	})
})