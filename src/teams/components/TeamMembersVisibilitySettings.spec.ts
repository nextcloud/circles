/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showError, showSuccess } from '@nextcloud/dialogs'
import { flushPromises, shallowMount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import NcRadioGroup from '@nextcloud/vue/components/NcRadioGroup'
import NcRadioGroupButton from '@nextcloud/vue/components/NcRadioGroupButton'
import TeamMembersVisibilitySettings from './TeamMembersVisibilitySettings.vue'
import Circle from '../team-page/models/circle.ts'

const dispatch = vi.hoisted(() => vi.fn())
vi.mock('vuex', () => ({ useStore: () => ({ dispatch }) }))
vi.mock('@nextcloud/dialogs', () => ({ showError: vi.fn(), showSuccess: vi.fn() }))

function mountSettings(level = 8, permission?: string) {
	return shallowMount(TeamMembersVisibilitySettings, {
		props: {
			circle: new Circle({
				id: 'team',
				owner: { id: 'owner', level: 9 },
				initiator: { id: 'actor', level },
				settings: permission ? { membersViewPermissionLevel: permission } : {},
			}),
		},
		global: { renderStubDefaultSlot: true },
	})
}

describe('Member visibility setting', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		dispatch.mockResolvedValue(undefined)
	})

	it('defaults to all members and offers the three role thresholds', () => {
		const wrapper = mountSettings()

		expect(wrapper.getComponent(NcRadioGroup).props('modelValue')).toBe('1')
		expect(wrapper.findAllComponents(NcRadioGroupButton).map((button) => button.props('value'))).toEqual(['8', '4', '1'])
	})

	it.each(['1', '4', '8'])('loads saved threshold %s', (permission) => {
		expect(mountSettings(8, permission).getComponent(NcRadioGroup).props('modelValue')).toBe(permission)
	})

	it.each([8, 9])('lets role %s save a new threshold', async (level) => {
		const wrapper = mountSettings(level)
		wrapper.getComponent(NcRadioGroup).vm.$emit('update:modelValue', '4')
		await flushPromises()

		expect(dispatch).toHaveBeenCalledWith('editCircleSetting', {
			circleId: 'team',
			setting: { setting: 'membersViewPermissionLevel', value: '4' },
		})
		expect(showSuccess).toHaveBeenCalled()
	})

	it('keeps the persisted selection and displays an error on failure', async () => {
		dispatch.mockRejectedValue(new Error('Failed'))
		const wrapper = mountSettings(8, '4')
		wrapper.getComponent(NcRadioGroup).vm.$emit('update:modelValue', '8')
		await flushPromises()

		expect(wrapper.getComponent(NcRadioGroup).props('modelValue')).toBe('4')
		expect(wrapper.getComponent(NcRadioGroup).attributes('disabled')).toBe('false')
		expect(showError).toHaveBeenCalled()
		expect(showSuccess).not.toHaveBeenCalled()
	})

	it('prevents duplicate saves while a request is pending', async () => {
		dispatch.mockReturnValue(new Promise(() => {}))
		const wrapper = mountSettings()
		const group = wrapper.getComponent(NcRadioGroup)
		group.vm.$emit('update:modelValue', '4')
		group.vm.$emit('update:modelValue', '8')
		await wrapper.vm.$nextTick()

		expect(dispatch).toHaveBeenCalledTimes(1)
		expect(group.attributes('disabled')).toBe('true')
	})

	it.each([1, 4])('does not allow role %s to change visibility', async (level) => {
		const wrapper = mountSettings(level)
		const group = wrapper.getComponent(NcRadioGroup)
		group.vm.$emit('update:modelValue', '8')
		await flushPromises()

		expect(group.attributes('disabled')).toBe('true')
		expect(dispatch).not.toHaveBeenCalled()
	})
})
