/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it } from 'vitest'
import { nextTick } from 'vue'
import TeamAppResourcePicker from './TeamAppResourcePicker.vue'
import { chooseTeamResources, finishResourcePicker, resourcePickerState } from '../integrations/resourcePicker.ts'

const stubs = {
	NcDialog: {
		props: ['name'],
		emits: ['update:open'],
		template: '<section role="dialog" :aria-label="name"><slot /><footer><slot name="actions" /></footer><button aria-label="Close" @click="$emit(\'update:open\', false)" /></section>',
	},
	NcTextField: {
		props: ['modelValue', 'label'],
		emits: ['update:modelValue'],
		template: '<input :aria-label="label" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)">',
	},
	NcCheckboxRadioSwitch: {
		props: ['modelValue', 'value', 'disabled'],
		emits: ['update:modelValue'],
		template: '<label><input type="checkbox" :value="value" :checked="modelValue.includes(value)" :disabled="disabled" @change="$emit(\'update:modelValue\', $event.target.checked ? [...modelValue, value] : modelValue.filter(id => id !== value))"><slot /></label>',
	},
	NcButton: {
		props: ['disabled'],
		emits: ['click'],
		template: '<button :disabled="disabled" @click="$emit(\'click\')"><slot /></button>',
	},
	NcIconSvgWrapper: true,
	NcEmptyContent: { props: ['name'], template: '<p>{{ name }}</p>' },
}

const options = {
	title: 'Choose boards',
	resources: [{ id: '1', label: 'First', description: 'Folder/Sketch' }, { id: '2', label: 'Second' }],
	selectedIds: ['1'],
}

describe('Teams resource picker dialog', () => {
	afterEach(() => finishResourcePicker(null))

	it('searches names and paths without dropping hidden selections, then saves', async () => {
		const selection = chooseTeamResources(options, new AbortController().signal)
		const wrapper = mount(TeamAppResourcePicker, { global: { stubs } })
		expect(wrapper.get('[role="dialog"]').attributes('aria-label')).toBe('Choose boards')
		await wrapper.get('input[aria-label="Search resources"]').setValue('Second')
		expect(wrapper.findAll('input[type="checkbox"]')).toHaveLength(1)
		await wrapper.get('input[type="checkbox"]').setValue(true)
		await wrapper.get('input[aria-label="Search resources"]').setValue('Folder')
		expect(wrapper.text()).toContain('First')
		expect(resourcePickerState.selectedIds).toEqual(['1', '2'])
		await wrapper.findAll('button').find((button) => button.text() === 'Save')!.trigger('click')
		await expect(selection).resolves.toEqual(['1', '2'])
		wrapper.unmount()
	})

	it('saves an explicitly empty selection', async () => {
		const selection = chooseTeamResources(options, new AbortController().signal)
		const wrapper = mount(TeamAppResourcePicker, { global: { stubs } })
		await wrapper.get('input[value="1"]').setValue(false)
		await wrapper.findAll('button').find((button) => button.text() === 'Save')!.trigger('click')
		await expect(selection).resolves.toEqual([])
		wrapper.unmount()
	})

	it('cancels through the dialog close control and on component teardown', async () => {
		const first = chooseTeamResources(options, new AbortController().signal)
		const wrapper = mount(TeamAppResourcePicker, { global: { stubs } })
		await wrapper.get('button[aria-label="Close"]').trigger('click')
		await expect(first).resolves.toBeNull()
		const second = chooseTeamResources(options, new AbortController().signal)
		await nextTick()
		wrapper.unmount()
		await expect(second).resolves.toBeNull()
	})

	it('shows an empty state and disables additional choices at the limit', async () => {
		const resources = Array.from({ length: 101 }, (_, index) => ({ id: String(index), label: `Board ${index}` }))
		chooseTeamResources({ title: 'Boards', resources, selectedIds: resources.slice(0, 100).map(({ id }) => id) }, new AbortController().signal)
		const wrapper = mount(TeamAppResourcePicker, { global: { stubs } })
		expect(wrapper.get('input[value="100"]').attributes('disabled')).toBeDefined()
		expect(wrapper.get('input[value="0"]').attributes('disabled')).toBeUndefined()
		await wrapper.get('input[aria-label="Search resources"]').setValue('missing')
		expect(wrapper.text()).toContain('No resources found')
		wrapper.unmount()
	})
})
