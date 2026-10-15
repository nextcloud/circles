/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { flushPromises, shallowMount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import Circle from '../../models/circle.ts'
import { MemberLevels } from '../../models/constants.ts'
import MemberList from './MemberList.vue'

vi.mock('@nextcloud/vue', () => ({
	NcEmptyContent: {}, NcLoadingIcon: {}, NcSelect: {}, NcTextField: {},
}))
vi.mock('@nextcloud/dialogs', () => ({ showError: vi.fn(), showWarning: vi.fn() }))
vi.mock('@nextcloud/event-bus', () => ({ subscribe: vi.fn() }))
vi.mock('@vueuse/core', () => ({ refDebounced: (value: unknown) => value }))
vi.mock('virtua/vue', () => ({ VList: {} }))
vi.mock('../../mixins/IsMobileMixin.ts', () => ({ default: { computed: { isMobile: () => false } } }))
vi.mock('../../services/collaborationAutocompletion.js', () => ({ getRecommendations: vi.fn(), getSuggestions: vi.fn() }))
vi.mock('../EntityPicker/EntityPicker.vue', () => ({ default: {} }))
vi.mock('./MemberGridItem.vue', () => ({ default: {} }))
vi.mock('../CircleDetails/CircleConfigs/CircleConfigCheckboxesList.vue', () => ({ default: {} }))
vi.mock('../CircleDetails/CircleConfigs/CircleConfigInvitationLink.vue', () => ({ default: {} }))

describe('member role filter', () => {
	it('orders semantic roles and requests visitor using equality role 2', async () => {
		const circle = new Circle({ id: 'team', config: 0, owner: { level: 9 }, initiator: { level: 9 } })
		const dispatch = vi.fn().mockResolvedValue(undefined)
		const wrapper = shallowMount(MemberList, {
			props: { list: [] },
			global: { mocks: {
				$route: { params: { teamId: 'team' } },
				$store: { getters: { getCircle: () => circle, isLoadingCircleMembers: false }, dispatch },
				logger: { debug: vi.fn(), error: vi.fn() },
				isMobile: false,
				t: (_app: string, text: string) => text,
			} },
		})
		expect(wrapper.vm.roles.map(({ id }: { id: number }) => id)).toEqual([
			MemberLevels.NONE, MemberLevels.VISITOR, MemberLevels.MEMBER,
			MemberLevels.MODERATOR, MemberLevels.ADMIN, MemberLevels.OWNER,
		])
		;(wrapper.vm as unknown as { searchRole: unknown }).searchRole = wrapper.vm.roles[1]
		await flushPromises()
		expect(dispatch).toHaveBeenCalledWith('getCircleMembers', { circleId: 'team', search: null, role: 2 })
	})
})