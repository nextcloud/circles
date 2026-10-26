/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { afterEach, describe, expect, it } from 'vitest'
import { chooseTeamResources, finishResourcePicker, resourcePickerState } from './resourcePicker.ts'

const options = {
	title: 'Choose resources',
	resources: [{ id: '1', label: 'First' }, { id: '2', label: 'Second' }],
	selectedIds: ['1', 'missing', '1'],
}

describe('Teams resource picker lifecycle', () => {
	afterEach(() => finishResourcePicker(null))

	it('copies candidates, normalizes selected navigation entries, and returns a selection', async () => {
		const selection = chooseTeamResources(options, new AbortController().signal)
		expect(resourcePickerState.open).toBe(true)
		expect(resourcePickerState.resources).toEqual(options.resources)
		expect(resourcePickerState.resources).not.toBe(options.resources)
		expect(resourcePickerState.selectedIds).toEqual(['1'])
		finishResourcePicker(['2'])
		await expect(selection).resolves.toEqual(['2'])
		expect(resourcePickerState.open).toBe(false)
		expect(resourcePickerState.resources).toEqual([])
	})

	it('distinguishes cancellation from explicitly clearing navigation', async () => {
		const cancelled = chooseTeamResources(options, new AbortController().signal)
		finishResourcePicker(null)
		await expect(cancelled).resolves.toBeNull()
		const cleared = chooseTeamResources(options, new AbortController().signal)
		finishResourcePicker([])
		await expect(cleared).resolves.toEqual([])
	})

	it('cancels when leaving the team and ignores later completions', async () => {
		const controller = new AbortController()
		const selection = chooseTeamResources(options, controller.signal)
		controller.abort()
		await expect(selection).resolves.toBeNull()
		finishResourcePicker(['2'])
		expect(resourcePickerState.open).toBe(false)
	})

	it('does not open for an aborted context or replace an active picker', () => {
		const controller = new AbortController()
		controller.abort()
		expect(() => chooseTeamResources(options, controller.signal)).toThrow()
		chooseTeamResources(options, new AbortController().signal)
		expect(() => chooseTeamResources(options, new AbortController().signal)).toThrow('already open')
	})

	it('rejects duplicate or invalid candidate IDs', () => {
		for (const resources of [[options.resources[0]!, options.resources[0]!], [{ id: '', label: 'Invalid' }], [{ id: 'x'.repeat(65), label: 'Too long' }]]) {
			expect(() => chooseTeamResources({ ...options, resources }, new AbortController().signal)).toThrow()
		}
		expect(resourcePickerState.open).toBe(false)
	})

	it('rejects unknown IDs and selections exceeding the navigation entry limit', async () => {
		const resources = Array.from({ length: 101 }, (_, index) => ({ id: String(index), label: String(index) }))
		const selection = chooseTeamResources({ ...options, resources }, new AbortController().signal)
		expect(() => finishResourcePicker(['unknown'])).toThrow()
		expect(() => finishResourcePicker(resources.map(({ id }) => id))).toThrow()
		finishResourcePicker(['2', '2'])
		await expect(selection).resolves.toEqual(['2'])
	})
})
