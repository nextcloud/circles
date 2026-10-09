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
	fetchTeamMembers: vi.fn().mockResolvedValue([]),
}))

function mountView() {
	return shallowMount(TeamActivityView, {
		props: { teamId: 'team-1' },
		global: {
			stubs: {
				NcEmptyContent: {
					props: ['name', 'description'],
					template: '<div><span>{{ name }}</span><span>{{ description }}</span></div>',
				},
			},
		},
	})
}

describe('TeamActivityView', () => {
	beforeEach(() => {
		vi.clearAllMocks()
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
})
