/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type * as L10n from '@nextcloud/l10n'

import { flushPromises, shallowMount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import TeamDashboardSettingsDialog from './TeamDashboardSettingsDialog.vue'

vi.mock('@nextcloud/l10n', async (importOriginal) => ({ ...await importOriginal<typeof L10n>(), t: (_app: string, text: string) => text }))

function render(widgetIds: string[] | null = ['missing']) {
	const saveLayout = vi.fn<(ids: string[] | null) => Promise<boolean>>(async () => true)
	const wrapper = shallowMount(TeamDashboardSettingsDialog, {
		props: { catalog: [{ id: 'pages', title: 'Recent pages', order: 1, icon_url: '', icon_class: '', item_icons_round: false }], widgetIds, saving: false, saveLayout },
		global: { stubs: {
			NcDialog: { template: '<div><slot /><slot name="actions" /></div>' },
			NcButton: { template: '<button><slot /></button>' },
			NcCheckboxRadioSwitch: { props: ['modelValue'], template: '<label><input type="checkbox" :checked="modelValue" @change="$emit(\'update:modelValue\', $event.target.checked)"><slot /></label>' },
		} },
	})
	return { wrapper, saveLayout }
}

describe('TeamDashboardSettingsDialog', () => {
	it('keeps unavailable IDs and saves a changed draft', async () => {
		const { wrapper, saveLayout } = render()
		expect(wrapper.text()).toMatch(/missing\s+\(Unavailable\)/)
		await wrapper.get('input').setValue(true)
		await wrapper.findAll('button')[2].trigger('click')
		await flushPromises()
		expect(saveLayout).toHaveBeenCalledWith(['missing', 'pages'])
		expect(wrapper.emitted('close')).toHaveLength(1)
	})

	it('restores automatic defaults with null', async () => {
		const { wrapper, saveLayout } = render()
		await wrapper.findAll('button')[0].trigger('click')
		await flushPromises()
		expect(saveLayout).toHaveBeenCalledWith(null)
	})

	it('cancels without saving', async () => {
		const { wrapper, saveLayout } = render(null)
		await wrapper.findAll('button')[1].trigger('click')
		expect(saveLayout).not.toHaveBeenCalled()
		expect(wrapper.emitted('close')).toHaveLength(1)
	})

	it('stays open on save failure', async () => {
		const { wrapper, saveLayout } = render()
		saveLayout.mockRejectedValueOnce(new Error('offline'))
		await wrapper.findAll('button')[2].trigger('click')
		await flushPromises()
		expect(wrapper.get('[role="alert"]').text()).toContain('Could not save')
		expect(wrapper.emitted('close')).toBeUndefined()
	})
})
