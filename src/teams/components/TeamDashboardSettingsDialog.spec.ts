/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type * as L10n from '@nextcloud/l10n'
import type { TeamDashboardWidget } from '../types.ts'

import { flushPromises, mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import TeamDashboardSettingsDialog from './TeamDashboardSettingsDialog.vue'

vi.mock('@nextcloud/l10n', async (importOriginal) => ({
	...await importOriginal<typeof L10n>(),
	t: (_app: string, text: string) => text,
}))

const catalog: TeamDashboardWidget[] = ['today', 'tomorrow', 'upcoming'].map((period) => ({
	id: `deck-team-${period}`,
	title: `Deck ${period}`,
	order: 20,
	icon_url: '',
	icon_class: '',
	item_icons_round: false,
}))

function render(widgetIds: string[] | null, saveLayout = vi.fn(async () => true)) {
	const wrapper = mount(TeamDashboardSettingsDialog, {
		props: { catalog, widgetIds, saving: false, saveLayout },
		global: { stubs: {
			NcDialog: { template: '<div><slot /><slot name="actions" /></div>' },
			NcCheckboxRadioSwitch: {
				props: ['modelValue', 'disabled'],
				emits: ['update:modelValue'],
				template: '<label><input type="checkbox" :checked="modelValue" :disabled="disabled" @change="$emit(\'update:modelValue\', $event.target.checked)"><slot /></label>',
			},
			NcButton: { template: '<button><slot /></button>' },
			NcIconSvgWrapper: true,
		} },
	})
	return { wrapper, saveLayout }
}

describe('TeamDashboardSettingsDialog', () => {
	it('offers all three independent Deck variants', () => {
		const { wrapper } = render(['deck-team-today'])
		expect(wrapper.findAll('input')).toHaveLength(3)
		expect((wrapper.findAll('input')[0]!.element as HTMLInputElement).checked).toBe(true)
		expect((wrapper.findAll('input')[1]!.element as HTMLInputElement).checked).toBe(false)
		wrapper.unmount()
	})

	it('keeps draft edits local until save and submits an explicit empty list', async () => {
		const { wrapper, saveLayout } = render(['deck-team-today'])
		await wrapper.findAll('input')[0]!.setValue(false)
		expect(saveLayout).not.toHaveBeenCalled()
		await wrapper.findAll('button').find((button) => button.text() === 'Save')!.trigger('click')
		await flushPromises()
		expect(saveLayout).toHaveBeenCalledWith([])
		expect(wrapper.emitted('close')).toHaveLength(1)
		wrapper.unmount()
	})

	it('keeps the dialog open with its draft on save failure', async () => {
		const saveLayout = vi.fn(async () => {
			throw new Error('Network')
		})
		const { wrapper } = render(['deck-team-today'], saveLayout)
		await wrapper.findAll('button').find((button) => button.text() === 'Save')!.trigger('click')
		await flushPromises()
		expect(wrapper.get('[role="alert"]').text()).toContain('Could not save')
		expect(wrapper.emitted('close')).toBeUndefined()
		wrapper.unmount()
	})

	it('preserves a saved selection whose app is unavailable', () => {
		const { wrapper } = render(['disabled-widget'])
		expect(wrapper.text()).toContain('disabled-widget')
		expect(wrapper.text()).toContain('Unavailable')
		expect((wrapper.findAll('input')[3]!.element as HTMLInputElement).checked).toBe(true)
		wrapper.unmount()
	})
})
