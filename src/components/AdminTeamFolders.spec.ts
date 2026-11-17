/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type * as L10n from '@nextcloud/l10n'

import { flushPromises, shallowMount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const mocks = vi.hoisted(() => ({
	getStoragePools: vi.fn(),
	setStoragePoolLimit: vi.fn(),
	assignTeamStoragePool: vi.fn(),
	getAdminTeamFolders: vi.fn(),
	deleteStoragePool: vi.fn(),
	updateTeamFolderDefaultQuota: vi.fn(),
	updateTeamFolderQuota: vi.fn(),
	showConfirmation: vi.fn(),
	showError: vi.fn(),
}))

vi.mock('../teams/api.ts', () => ({
	...mocks,
	deleteTeam: vi.fn(),
	getLinkableTeamFolders: vi.fn(),
	linkTeamFolder: vi.fn(),
	upgradeTeamFolder: vi.fn(),
}))
vi.mock('@nextcloud/dialogs', () => ({ showError: mocks.showError, showSuccess: vi.fn(), showConfirmation: mocks.showConfirmation }))
vi.mock('@nextcloud/password-confirmation', () => ({ confirmPassword: vi.fn() }))
vi.mock('@nextcloud/initial-state', () => ({ loadState: (_app: string, _key: string, fallback: unknown) => fallback }))
vi.mock('@nextcloud/l10n', async (importOriginal) => ({
	...await importOriginal<typeof L10n>(),
	t: (_app: string, text: string, values: Record<string, string> = {}) => text.replace(/\{([^}]+)\}/g, (_match, key: string) => values[key] ?? key),
}))
vi.mock('@nextcloud/files', () => ({
	formatFileSize: (bytes: number) => `${bytes} B`,
	parseFileSize: (value: string) => /^\d+\s*B?$/.test(value.trim()) ? Number.parseInt(value, 10) : null,
}))
vi.mock('@nextcloud/router', () => ({ generateOcsUrl: (path: string) => path }))
vi.mock('../logger.ts', () => ({ logger: { error: vi.fn() } }))
vi.mock('@nextcloud/axios', () => ({
	default: {
		get: vi.fn(async (url: string) => ({ data: { ocs: { data: url.endsWith('/groups')
			? [{ id: 'engineering', label: 'Engineering' }]
			: { team_folder_group_quotas: {} } } } })),
		post: vi.fn(async () => ({ data: { ocs: { meta: { status: 'ok' } } } })),
	},
}))

import AdminTeamFolders from './AdminTeamFolders.vue'

function mountSettings() {
	return shallowMount(AdminTeamFolders, {
		global: {
			stubs: {
				NcSettingsSection: { template: '<section><slot /></section>' },
				NcActions: { template: '<div><slot /></div>' },
				NcActionButton: { props: ['disabled'], template: '<button :disabled="disabled"><slot /></button>' },
				NcButton: { template: '<button><slot /></button>' },
				NcTextField: {
					props: ['modelValue', 'label', 'disabled', 'placeholder'],
					emits: ['update:modelValue'],
					template: '<input :aria-label="label" :value="modelValue" :placeholder="placeholder" :disabled="disabled" @input="$emit(\'update:modelValue\', $event.target.value)" />',
				},
				NcSelect: {
					props: ['modelValue', 'options', 'disabled'],
					emits: ['update:modelValue'],
					template: '<select :value="modelValue?.id ?? \'\'" :disabled="disabled" @change="$emit(\'update:modelValue\', options.find(option => option.id === $event.target.value))"><option value=""></option><option v-for="option in options" :key="option.id" :value="option.id">{{ option.label }}</option></select>',
				},
			},
		},
	})
}

describe('AdminTeamFolders storage pools', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		mocks.getStoragePools.mockResolvedValue({ supported: true, pools: [
			{ id: 'default', groupId: null, limit: 100, allocated: 20, hasUnlimitedFolders: false },
			{ id: 'group:engineering', groupId: 'engineering', limit: 50, allocated: 40, hasUnlimitedFolders: false },
		] })
		mocks.getAdminTeamFolders.mockResolvedValue([{
			teamId: 'team1',
			teamName: 'Design',
			defaultQuota: null,
			folder: { id: 42, mountPoint: 'Design', quota: 20 },
			storagePoolId: 'default',
		}])
		mocks.setStoragePoolLimit.mockResolvedValue({})
		mocks.deleteStoragePool.mockResolvedValue(undefined)
		mocks.showConfirmation.mockResolvedValue(true)
	})

	it('keeps a successfully updated folder quota without reporting an error', async () => {
		mocks.updateTeamFolderQuota.mockResolvedValue({ id: 42, mountPoint: 'Design', quota: 0 })
		const wrapper = mountSettings()
		await flushPromises()
		await wrapper.get('select[aria-label="Storage quota"]').setValue('0')
		await flushPromises()
		expect(mocks.updateTeamFolderQuota).toHaveBeenCalledExactlyOnceWith('team1', 0)
		expect((wrapper.get('select[aria-label="Storage quota"]').element as HTMLSelectElement).value).toBe('0')
		expect(mocks.showError).not.toHaveBeenCalled()
	})

	it('refreshes unlimited allocations after limiting an existing folder', async () => {
		mocks.getAdminTeamFolders.mockResolvedValue([{ teamId: 'team1', teamName: 'Design', defaultQuota: 20, folder: { id: 42, mountPoint: 'Design', quota: 0 }, storagePoolId: 'default' }])
		mocks.getStoragePools.mockResolvedValue({ supported: true, pools: [
			{ id: 'default', groupId: null, limit: null, allocated: 0, hasUnlimitedFolders: true },
		] })
		mocks.updateTeamFolderQuota.mockResolvedValue({ id: 42, mountPoint: 'Design', quota: 20 })
		const wrapper = mountSettings()
		await flushPromises()
		expect(wrapper.text()).toContain('Allocated storage for everyone else: Unlimited')
		mocks.getStoragePools.mockResolvedValue({ supported: true, pools: [
			{ id: 'default', groupId: null, limit: null, allocated: 20, hasUnlimitedFolders: false },
		] })
		await wrapper.get('select[aria-label="Storage quota"]').setValue('20 B')
		await flushPromises()
		expect(mocks.updateTeamFolderQuota).toHaveBeenCalledExactlyOnceWith('team1', 20)
		expect(wrapper.text()).toContain('Allocated storage for everyone else: 20 B')
		expect(mocks.showError).not.toHaveBeenCalled()
	})

	it('shows pool-only groups and server-calculated allocations', async () => {
		const wrapper = mountSettings()
		await flushPromises()
		expect(wrapper.find('input[aria-label="Total storage for Engineering"]').exists()).toBe(true)
		expect(wrapper.text()).toContain('40 B')
		expect(wrapper.text()).toContain('Allocated storage for everyone else: 20 B')
	})

	it('removes a pool-only row and releases its pool on save', async () => {
		const wrapper = mountSettings()
		await flushPromises()
		const row = wrapper.find('.team-folders__quota-mapping .team-folders__row')
		await row.find('button').trigger('click')
		await flushPromises()
		expect(mocks.showConfirmation).toHaveBeenCalled()
		expect(wrapper.find('input[aria-label="Total storage for Engineering"]').exists()).toBe(false)
		expect(mocks.deleteStoragePool).not.toHaveBeenCalled()
		await wrapper.find('#team-folder-default-quotas-panel button').trigger('click')
		await flushPromises()
		expect(mocks.deleteStoragePool).toHaveBeenCalledWith('engineering', null)
		expect(mocks.setStoragePoolLimit).not.toHaveBeenCalled()
	})

	it('deletes a team override and pool and refreshes the team assignment', async () => {
		mocks.getStoragePools.mockResolvedValue({ supported: true, pools: [
			{ id: 'default', groupId: null, limit: 100, allocated: 0, hasUnlimitedFolders: false },
			{ id: 'team:team1', groupId: null, teamId: 'team1', limit: 100, allocated: 20, hasUnlimitedFolders: false },
		] })
		mocks.getAdminTeamFolders.mockResolvedValue([{ teamId: 'team1', teamName: 'Design', defaultQuota: 20, folder: { id: 42, mountPoint: 'Design', quota: 20 }, storagePoolId: 'team:team1' }])
		const wrapper = mountSettings()
		await flushPromises()
		await wrapper.find('input[aria-label="Total storage for Design"]').setValue('200 B')
		await wrapper.find('.team-folders__quota-mapping .team-folders__row button').trigger('click')
		await flushPromises()
		expect(wrapper.find('input[aria-label="Total storage for Design"]').exists()).toBe(false)
		mocks.getStoragePools.mockResolvedValue({ supported: true, pools: [
			{ id: 'default', groupId: null, limit: 100, allocated: 20, hasUnlimitedFolders: false },
		] })
		mocks.getAdminTeamFolders.mockResolvedValue([{ teamId: 'team1', teamName: 'Design', defaultQuota: null, folder: { id: 42, mountPoint: 'Design', quota: 20 }, storagePoolId: 'default' }])
		await wrapper.find('#team-folder-default-quotas-panel button').trigger('click')
		await flushPromises()
		expect(mocks.deleteStoragePool).toHaveBeenCalledWith(null, 'team1')
		expect(mocks.updateTeamFolderDefaultQuota).toHaveBeenCalledWith('team1', null)
		expect(mocks.setStoragePoolLimit).not.toHaveBeenCalled()
		expect(wrapper.find('input[aria-label="Total storage for Design"]').exists()).toBe(false)
		expect((wrapper.find('select[aria-label="Storage pool"]').element as HTMLSelectElement).value).toBe('default')
	})

	it('keeps the pool row when deletion is cancelled', async () => {
		mocks.showConfirmation.mockResolvedValueOnce(false)
		const wrapper = mountSettings()
		await flushPromises()
		await wrapper.find('.team-folders__quota-mapping .team-folders__row button').trigger('click')
		await flushPromises()
		expect(wrapper.find('input[aria-label="Total storage for Engineering"]').exists()).toBe(true)
		expect(mocks.deleteStoragePool).not.toHaveBeenCalled()
	})

	it('shows unlimited limits as empty fields without saving unchanged pools', async () => {
		mocks.getStoragePools.mockResolvedValue({ supported: true, pools: [
			{ id: 'default', groupId: null, limit: null, allocated: 20, hasUnlimitedFolders: false },
			{ id: 'group:engineering', groupId: 'engineering', limit: null, allocated: 40, hasUnlimitedFolders: false },
		] })
		const wrapper = mountSettings()
		await flushPromises()
		for (const label of ['everyone else', 'Engineering']) {
			const input = wrapper.get(`input[aria-label="Total storage for ${label}"]`)
			expect((input.element as HTMLInputElement).value).toBe('')
			expect(input.attributes('placeholder')).toBe('Unlimited')
		}
		await wrapper.find('#team-folder-default-quotas-panel button').trigger('click')
		await flushPromises()
		expect(mocks.setStoragePoolLimit).not.toHaveBeenCalled()
	})

	it.each(['', '   ', 'Unlimited'])('saves %j as unlimited for existing pools', async (value) => {
		mocks.getStoragePools.mockResolvedValue({ supported: true, pools: [
			{ id: 'default', groupId: null, limit: 100, allocated: 20, hasUnlimitedFolders: false },
			{ id: 'group:engineering', groupId: 'engineering', limit: 50, allocated: 40, hasUnlimitedFolders: false },
			{ id: 'team:team1', groupId: null, teamId: 'team1', limit: 100, allocated: 20, hasUnlimitedFolders: false },
		] })
		const wrapper = mountSettings()
		await flushPromises()
		for (const label of ['everyone else', 'Engineering', 'Design']) {
			await wrapper.get(`input[aria-label="Total storage for ${label}"]`).setValue(value)
		}
		await wrapper.find('#team-folder-default-quotas-panel button').trigger('click')
		await flushPromises()
		expect(mocks.setStoragePoolLimit).toHaveBeenCalledTimes(3)
		expect(mocks.setStoragePoolLimit).toHaveBeenCalledWith(null, null, null)
		expect(mocks.setStoragePoolLimit).toHaveBeenCalledWith('engineering', null, null)
		expect(mocks.setStoragePoolLimit).toHaveBeenCalledWith(null, null, 'team1')
		expect(mocks.showError).not.toHaveBeenCalled()
	})

	it('creates an unlimited pool for a newly added group with an empty limit', async () => {
		mocks.getStoragePools.mockResolvedValue({ supported: true, pools: [
			{ id: 'default', groupId: null, limit: 100, allocated: 20, hasUnlimitedFolders: false },
		] })
		const wrapper = mountSettings()
		await flushPromises()
		await wrapper.get('.team-folders__add-row select').setValue('engineering')
		await wrapper.get('.team-folders__add-row button').trigger('click')
		expect((wrapper.get('input[aria-label="Total storage for Engineering"]').element as HTMLInputElement).value).toBe('')
		await wrapper.find('#team-folder-default-quotas-panel button').trigger('click')
		await flushPromises()
		expect(mocks.setStoragePoolLimit).toHaveBeenCalledExactlyOnceWith('engineering', null, null)
	})

	it('creates an unlimited pool for an existing override without a pool', async () => {
		mocks.getAdminTeamFolders.mockResolvedValue([{ teamId: 'team1', teamName: 'Design', defaultQuota: 20, folder: null, storagePoolId: 'default' }])
		const wrapper = mountSettings()
		await flushPromises()
		await wrapper.find('#team-folder-default-quotas-panel button').trigger('click')
		await flushPromises()
		expect(mocks.setStoragePoolLimit).toHaveBeenCalledExactlyOnceWith(null, null, 'team1')
	})

	it('does not save pools when the provider does not support them', async () => {
		mocks.getStoragePools.mockResolvedValue({ supported: false, pools: [] })
		mocks.getAdminTeamFolders.mockResolvedValue([{ teamId: 'team1', teamName: 'Design', defaultQuota: 20, folder: null }])
		const wrapper = mountSettings()
		await flushPromises()
		await wrapper.find('#team-folder-default-quotas-panel button').trigger('click')
		await flushPromises()
		expect(mocks.setStoragePoolLimit).not.toHaveBeenCalled()
	})

	it('saves zero as zero budget, not unlimited', async () => {
		const wrapper = mountSettings()
		await flushPromises()
		await wrapper.find('input[aria-label="Total storage for everyone else"]').setValue('0 B')
		await wrapper.find('#team-folder-default-quotas-panel button').trigger('click')
		await flushPromises()
		expect(mocks.setStoragePoolLimit).toHaveBeenCalledWith(null, 0, null)
	})

	it('rejects invalid input without persisting defaults or limits', async () => {
		const wrapper = mountSettings()
		await flushPromises()
		await wrapper.find('input[aria-label="Total storage for everyone else"]').setValue('-1 B')
		await wrapper.find('#team-folder-default-quotas-panel button').trigger('click')
		await flushPromises()
		expect(mocks.setStoragePoolLimit).not.toHaveBeenCalled()
		expect(mocks.showError).toHaveBeenCalled()
	})

	it('shows pool conflicts and preserves the original team assignment', async () => {
		mocks.assignTeamStoragePool.mockRejectedValueOnce({ response: { data: { ocs: { data: { code: 'storage_pool_exceeded' } } } } })
		const wrapper = mountSettings()
		await flushPromises()
		await wrapper.find('select[aria-label="Storage pool"]').setValue('group:engineering')
		await flushPromises()
		expect(mocks.assignTeamStoragePool).toHaveBeenCalledWith('team1', 'engineering', null)
		expect(mocks.showError).toHaveBeenCalledWith('The storage pool does not have enough available quota.')
		expect((wrapper.find('select[aria-label="Storage pool"]').element as HTMLSelectElement).value).toBe('default')
	})

	it('limits an unlimited team pool without a folder or default quota override', async () => {
		mocks.getAdminTeamFolders.mockResolvedValue([{ teamId: 'team1', teamName: 'Design', defaultQuota: null, folder: null, storagePoolId: 'team:team1' }])
		mocks.getStoragePools.mockResolvedValue({ supported: true, pools: [
			{ id: 'default', groupId: null, limit: 100, allocated: 20, hasUnlimitedFolders: false },
			{ id: 'team:team1', groupId: null, teamId: 'team1', limit: null, allocated: 0, hasUnlimitedFolders: false },
		] })
		const wrapper = mountSettings()
		await flushPromises()
		await wrapper.get('button[aria-label="Edit total storage for Design"]').trigger('click')
		await flushPromises()
		expect(wrapper.get('#team-folder-default-quotas-tab').attributes('aria-selected')).toBe('true')
		const input = wrapper.get('input[aria-label="Total storage for Design"]')
		expect(input.attributes('disabled')).toBeUndefined()
		await input.setValue('200 B')
		await wrapper.get('#team-folder-default-quotas-panel button').trigger('click')
		await flushPromises()
		expect(mocks.setStoragePoolLimit).toHaveBeenCalledExactlyOnceWith(null, 200, 'team1')
		expect(mocks.updateTeamFolderDefaultQuota).not.toHaveBeenCalled()
		expect(mocks.showError).not.toHaveBeenCalled()
	})

	it('lets administrators configure total storage for a team', async () => {
		mocks.getAdminTeamFolders.mockResolvedValue([{ teamId: 'team1', teamName: 'Design', defaultQuota: 20, folder: null, storagePoolId: 'default' }])
		const wrapper = mountSettings()
		await flushPromises()
		await wrapper.find('input[aria-label="Total storage for Design"]').setValue('200 B')
		await wrapper.find('#team-folder-default-quotas-panel button').trigger('click')
		await flushPromises()
		expect(mocks.setStoragePoolLimit).toHaveBeenCalledWith(null, 200, 'team1')
	})

	it('shows the team pool name and separates allocated, pooled usage and folder usage', async () => {
		mocks.getStoragePools.mockResolvedValue({ supported: true, pools: [
			{ id: 'team:team1', groupId: null, teamId: 'team1', limit: 100, allocated: 60, used: 17, hasUnlimitedFolders: false },
		] })
		mocks.getAdminTeamFolders.mockResolvedValue([{ teamId: 'team1', teamName: 'Design', defaultQuota: null, folder: { id: 42, mountPoint: 'Design', quota: 20 }, storagePoolId: 'team:team1', usedStorage: 9 }])
		const wrapper = mountSettings()
		await flushPromises()
		expect(wrapper.find('input[aria-label="Total storage for Design"]').exists()).toBe(true)
		expect(wrapper.find('select[aria-label="Storage pool"]').text()).toContain('Design (Team)')
		expect(wrapper.text()).toContain('60 B')
		expect(wrapper.text()).toContain('17 B')
		expect(wrapper.text()).toContain('9 B')
		expect(wrapper.text()).toContain('100 B')
	})
})
