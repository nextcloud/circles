/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { CreationStoragePools } from '../api.ts'

import { flushPromises, shallowMount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import TeamCreationWizard from './TeamCreationWizard.vue'

const { fetchCreationStoragePools, createTeam, push } = vi.hoisted(() => ({
	fetchCreationStoragePools: vi.fn<() => Promise<CreationStoragePools | null>>(),
	createTeam: vi.fn(),
	push: vi.fn(),
}))

vi.mock('../api.ts', () => ({ fetchCreationStoragePools }))
vi.mock('../store.ts', () => ({
	useTeamsStore: () => ({ createTeam, searchMemberCandidates: vi.fn(async () => []) }),
}))
vi.mock('@nextcloud/initial-state', () => ({ loadState: (_app: string, _key: string, fallback: unknown) => fallback }))
vi.mock('@nextcloud/dialogs', () => ({ showError: vi.fn(), showSuccess: vi.fn(), showWarning: vi.fn() }))
vi.mock('vue-router', () => ({ useRouter: () => ({ push }) }))
vi.mock('../../logger.ts', () => ({ logger: { error: vi.fn() } }))

function mountWizard() {
	return shallowMount(TeamCreationWizard, {
		global: {
			stubs: {
				NcDialog: { name: 'NcDialog', props: ['buttons'], template: '<div><slot /></div>' },
				NcButton: { name: 'NcButton', emits: ['click'], template: '<button @click="$emit(\'click\')"><slot /></button>' },
			},
		},
	})
}

describe('team creation storage pool', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		fetchCreationStoragePools.mockResolvedValue({
			defaultPoolId: 'group:engineering',
			pools: [
				{ id: 'group:engineering', label: 'Engineering' },
				{ id: 'group:sales', label: 'Sales' },
			],
		})
		createTeam.mockResolvedValue({ id: 'new-team' })
	})

	it('loads eligible pools only when advanced options are opened and preselects the automatic default', async () => {
		const wrapper = mountWizard()
		expect(fetchCreationStoragePools).not.toHaveBeenCalled()

		await wrapper.get('button').trigger('click')
		await flushPromises()

		const select = wrapper.getComponent({ name: 'NcSelect' })
		expect(select.props('modelValue')).toMatchObject({ id: 'group:engineering' })
		expect(select.props('options')).toHaveLength(2)
		expect(select.props('clearable')).toBe(false)
		await wrapper.get('button').trigger('click')
		await wrapper.get('button').trigger('click')
		expect(fetchCreationStoragePools).toHaveBeenCalledTimes(1)
		wrapper.unmount()
	})

	it.each(['group:sales', 'default'])('sends the explicitly selected %s pool when creating a team', async (poolId) => {
		if (poolId === 'default') {
			fetchCreationStoragePools.mockResolvedValueOnce({ defaultPoolId: 'default', pools: [{ id: 'default', label: '' }] })
		}
		const wrapper = mountWizard()
		await wrapper.get('button').trigger('click')
		await flushPromises()
		const select = wrapper.getComponent({ name: 'NcSelect' })
		select.vm.$emit('update:modelValue', { id: poolId, label: poolId })
		wrapper.getComponent({ name: 'NcTextField' }).vm.$emit('update:modelValue', 'Design')
		await flushPromises()
		wrapper.getComponent({ name: 'NcDialog' }).vm.$emit('submit')
		await flushPromises()
		wrapper.getComponent({ name: 'NcDialog' }).vm.$emit('submit')
		await flushPromises()

		expect(createTeam).toHaveBeenCalledWith('Design', true, '', expect.any(Function), poolId)
		expect(push).toHaveBeenCalledWith({ name: 'team', params: { teamId: 'new-team' } })
		wrapper.unmount()
	})

	it('offers a retry after failing to load pools', async () => {
		fetchCreationStoragePools.mockRejectedValueOnce(new Error('offline'))
		const wrapper = mountWizard()
		await wrapper.get('button').trigger('click')
		await flushPromises()
		expect(wrapper.get('[role="alert"]').text()).toBe('Could not load storage pools')

		await wrapper.get('[aria-label="Retry loading storage pools"]').trigger('click')
		await flushPromises()
		expect(wrapper.find('[role="alert"]').exists()).toBe(false)
		expect(wrapper.getComponent({ name: 'NcSelect' }).props('modelValue')).toMatchObject({ id: 'group:engineering' })
		wrapper.unmount()
	})

	it('does not offer pool selection for an unsupported provider', async () => {
		fetchCreationStoragePools.mockResolvedValueOnce(null)
		const wrapper = mountWizard()
		await wrapper.get('button').trigger('click')
		await flushPromises()
		expect(wrapper.findComponent({ name: 'NcSelect' }).exists()).toBe(false)
		wrapper.unmount()
	})
})
