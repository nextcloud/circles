/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { defineComponent, nextTick } from 'vue'
import TeamAppPrompt, { promptForName } from './TeamAppPrompt.vue'

const dialogStub = defineComponent({
	props: { name: { type: String, required: true } },
	template: '<div><h2>{{ name }}</h2><slot /><slot name="actions" /></div>',
})

const buttonStub = defineComponent({
	emits: ['click'],
	template: '<button @click="$emit(\'click\')"><slot /></button>',
})

const textFieldStub = defineComponent({
	props: { modelValue: { type: String, default: '' } },
	emits: ['update:modelValue'],
	template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)">',
})

function mountPrompt() {
	return mount(TeamAppPrompt, {
		global: {
			stubs: {
				NcButton: buttonStub,
				NcDialog: dialogStub,
				NcTextField: textFieldStub,
			},
		},
	})
}

describe('Team app prompt', () => {
	it('resolves with the entered value', async () => {
		const wrapper = mountPrompt()
		const answer = promptForName('Name', 'Draft', new AbortController().signal)
		await nextTick()
		expect(wrapper.get('h2').text()).toBe('Name')
		await wrapper.get('input').setValue('Sketch')
		await wrapper.findAll('button').find((button) => button.text() === 'Save')!.trigger('click')

		await expect(answer).resolves.toBe('Sketch')
		expect(wrapper.find('h2').exists()).toBe(false)
		wrapper.unmount()
	})

	it('resolves with null when cancelled in the dialog', async () => {
		const wrapper = mountPrompt()
		const answer = promptForName('Name', undefined, new AbortController().signal)
		await nextTick()
		await wrapper.findAll('button').find((button) => button.text() === 'Cancel')!.trigger('click')

		await expect(answer).resolves.toBeNull()
		expect(wrapper.find('h2').exists()).toBe(false)
		wrapper.unmount()
	})

	it('resolves with null when the team scope is aborted', async () => {
		const wrapper = mountPrompt()
		const controller = new AbortController()
		const answer = promptForName('Name', undefined, controller.signal)
		await nextTick()
		controller.abort()

		await expect(answer).resolves.toBeNull()
		await nextTick()
		expect(wrapper.find('h2').exists()).toBe(false)
		wrapper.unmount()
	})

	it('rejects an already-aborted scope and a concurrent prompt', async () => {
		const aborted = new AbortController()
		aborted.abort()
		expect(() => promptForName('Name', undefined, aborted.signal)).toThrow()

		const controller = new AbortController()
		const answer = promptForName('Name', undefined, controller.signal)
		expect(() => promptForName('Another name', undefined, new AbortController().signal)).toThrow('Another Teams app dialog is already open')
		controller.abort()
		await expect(answer).resolves.toBeNull()
	})
})
