/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type * as L10n from '@nextcloud/l10n'

import { mdiAccountMultipleOutline } from '@mdi/js'
import { flushPromises, shallowMount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import TeamActivityView from './TeamActivityView.vue'

const fetchTeamActivities = vi.hoisted(() => vi.fn())
const fetchTeamMembers = vi.hoisted(() => vi.fn())

vi.mock('@nextcloud/l10n', async (importOriginal) => {
	const actual = await importOriginal() as typeof L10n
	return {
		...actual,
		t: (_app: string, text: string) => text,
	}
})

vi.mock('../../logger.ts', () => ({
	logger: { error: vi.fn() },
}))

vi.mock('../api.ts', () => ({
	fetchTeamActivities,
	fetchTeamMembers,
}))

function mountView() {
	return shallowMount(TeamActivityView, {
		props: { teamId: 'team-1' },
		global: {
			stubs: {
				NcActionButton: {
					emits: ['click'],
					template: '<button class="nc-action-button" @click="$emit(\'click\')"><slot /></button>',
				},
				NcActions: { template: '<div><slot /></div>' },
				NcButton: {
					emits: ['click'],
					template: '<button class="nc-button" @click="$emit(\'click\')"><slot /></button>',
				},
				NcEmptyContent: {
					props: ['name', 'description'],
					template: '<div><span>{{ name }}</span><span>{{ description }}</span></div>',
				},
				NcTextField: {
					props: ['modelValue', 'helperText'],
					emits: ['update:modelValue'],
					template: '<div><input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)"><span>{{ helperText }}</span></div>',
				},
			},
		},
	})
}

describe('TeamActivityView', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		fetchTeamMembers.mockResolvedValue([])
	})

	it('loads and displays the current team activity', async () => {
		fetchTeamActivities.mockResolvedValueOnce({
			activities: [{
				activity_id: 1,
				subject: 'Alice joined Design',
				subject_rich: ['{user} joined {team}', {
					user: { id: 'alice', name: 'Alice', type: 'user' },
					team: { id: 'team-1', name: 'Design', type: 'circle' },
				}],
				datetime: '2026-09-30T12:00:00+00:00',
				user: 'alice',
				object_type: 'circles',
			}, {
				activity_id: 2,
				subject: 'Alice created a folder',
				datetime: '2026-09-29T12:00:00+00:00',
				user: 'alice',
			}],
		})

		const wrapper = mountView()
		await flushPromises()

		expect(fetchTeamActivities).toHaveBeenCalledWith('team-1', expect.objectContaining({ since: undefined }))
		const richSubject = wrapper.findComponent({ name: 'NcRichText' })
		expect(richSubject.props('text')).toBe('{user} joined {team}')
		expect(richSubject.props('arguments')).toMatchObject({
			user: { props: { displayName: 'Alice', user: 'alice' } },
			team: { props: { name: 'Design' } },
		})
		expect(wrapper.findComponent(NcIconSvgWrapper).props('path')).toBe(mdiAccountMultipleOutline)
		expect(wrapper.findAll('.team-activity__group')).toHaveLength(2)
	})

	it('shows the empty state when the team has no activity', async () => {
		fetchTeamActivities.mockResolvedValueOnce({ activities: [] })

		const wrapper = mountView()
		await flushPromises()

		expect(wrapper.text()).toContain('No activity yet')
	})

	it('shows an unavailable state when loading fails', async () => {
		fetchTeamActivities.mockRejectedValueOnce(new Error('Network error'))

		const wrapper = mountView()
		await flushPromises()

		expect(wrapper.text()).toContain('Activity unavailable')
	})

	it('loads the next activity page with the returned cursor', async () => {
		fetchTeamActivities
			.mockResolvedValueOnce({
				activities: [{ activity_id: 1, subject: 'First activity', datetime: '2026-09-30T12:00:00+00:00', user: 'alice' }],
				nextSince: 42,
			})
			.mockResolvedValueOnce({
				activities: [{ activity_id: 2, subject: 'Older activity', datetime: '2026-09-29T12:00:00+00:00', user: 'alice' }],
			})

		const wrapper = mountView()
		await flushPromises()

		await wrapper.get('.team-activity__more .nc-button').trigger('click')
		await flushPromises()

		expect(fetchTeamActivities).toHaveBeenLastCalledWith('team-1', expect.objectContaining({ since: 42 }))
		expect(wrapper.findAllComponents({ name: 'NcRichText' }).map((component) => component.props('text'))).toEqual([
			'First activity',
			'Older activity',
		])
		expect(wrapper.findAll('.team-activity__group')).toHaveLength(2)
		expect(wrapper.find('.team-activity__more').exists()).toBe(false)
	})

	it('applies a debounced search only after the minimum term length', async () => {
		vi.useFakeTimers()
		fetchTeamActivities.mockResolvedValue({ activities: [] })
		const wrapper = mountView()
		await flushPromises()

		const input = wrapper.get('.team-activity__search input')
		await input.setValue('a')
		expect(wrapper.text()).toContain('Enter at least {count} characters to search')
		await vi.advanceTimersByTimeAsync(300)
		await flushPromises()
		expect(fetchTeamActivities).toHaveBeenLastCalledWith('team-1', expect.objectContaining({ search: undefined }))

		await input.setValue(' folders ')
		await vi.advanceTimersByTimeAsync(300)
		await flushPromises()
		expect(fetchTeamActivities).toHaveBeenLastCalledWith('team-1', expect.objectContaining({ search: 'folders', since: undefined }))
		vi.useRealTimers()
	})

	it('applies custom date inputs as Activity timestamps', async () => {
		fetchTeamActivities.mockResolvedValue({ activities: [] })
		const wrapper = mountView()
		await flushPromises()

		await wrapper.findAll('.nc-action-button').find((button) => button.text() === 'Custom range')!.trigger('click')
		const [fromInput, toInput] = wrapper.findAll('input[type="date"]')
		await fromInput.setValue('2026-09-01')
		await toInput.setValue('2026-09-30')
		await flushPromises()

		const from = new Date(2026, 8, 1)
		from.setHours(0, 0, 0, 0)
		const to = new Date(2026, 8, 30)
		to.setHours(23, 59, 59, 999)
		expect(fetchTeamActivities).toHaveBeenLastCalledWith('team-1', expect.objectContaining({
			from: Math.floor(from.getTime() / 1000),
			to: Math.floor(to.getTime() / 1000),
		}))
	})
})
