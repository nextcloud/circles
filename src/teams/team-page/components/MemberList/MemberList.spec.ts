/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import MemberList from './MemberList.vue'

vi.mock('@nextcloud/dialogs', () => ({ showError: vi.fn(), showWarning: vi.fn() }))

describe('Restricted member list', () => {
	it('shows a permission explanation instead of members and search controls', () => {
		const wrapper = shallowMount(MemberList, {
			props: { list: [{ singleId: 'cached', displayName: 'Cached member' }] },
			global: {
				mocks: {
					t: (_app: string, text: string) => text,
					$route: { params: { teamId: 'team' } },
					$store: {
						getters: {
							getCircle: () => ({ isMember: true, canViewMembers: false }),
							isLoadingCircleMembers: false,
						},
					},
				},
			},
		})

		expect(wrapper.getComponent({ name: 'NcEmptyContent' }).props('name')).toBe('You do not have permission to view the members of this team')
		expect(wrapper.findComponent({ name: 'NcTextField' }).exists()).toBe(false)
		expect(wrapper.text()).not.toContain('Cached member')
		wrapper.unmount()
	})
})
