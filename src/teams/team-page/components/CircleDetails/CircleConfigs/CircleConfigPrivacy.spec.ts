/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import TeamMembersVisibilitySettings from '../../../../components/TeamMembersVisibilitySettings.vue'
import CircleConfigCheckboxesList from './CircleConfigCheckboxesList.vue'
import CircleConfigPrivacy from './CircleConfigPrivacy.vue'
import Circle from '../../../models/circle.ts'
import { CircleConfigs, MemberLevels } from '../../../models/constants.ts'

vi.mock('@nextcloud/dialogs', () => ({
	showError: vi.fn(),
	showSuccess: vi.fn(),
	showConfirmation: vi.fn(),
}))

describe('Circle privacy settings', () => {
	it.each([
		{ level: MemberLevels.OWNER, canEdit: true },
		{ level: MemberLevels.ADMIN, canEdit: true },
		{ level: MemberLevels.MODERATOR, canEdit: false },
		{ level: MemberLevels.MEMBER, canEdit: false },
	])('shows member visibility only to administrators for role $level', ({ level, canEdit }) => {
		const circle = new Circle({
			id: 'team',
			owner: { id: 'owner', level: MemberLevels.OWNER },
			initiator: { id: 'actor', level },
		})
		const wrapper = shallowMount(CircleConfigPrivacy, { props: { circle } })

		expect(wrapper.getComponent(CircleConfigCheckboxesList).props()).toMatchObject({
			circle,
			configs: { [CircleConfigs.VISIBLE]: 'Visible to everyone' },
		})
		expect(wrapper.findComponent(TeamMembersVisibilitySettings).exists()).toBe(canEdit)
		if (canEdit) {
			expect(wrapper.getComponent(TeamMembersVisibilitySettings).props('circle')).toStrictEqual(circle)
		}
		wrapper.unmount()
	})
})
