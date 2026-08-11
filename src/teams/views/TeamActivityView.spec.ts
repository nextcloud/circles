/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type * as NextcloudL10n from '@nextcloud/l10n'

import { flushPromises, shallowMount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import TeamActivityView from './TeamActivityView.vue'

const {
	axiosGetMock,
	generateOcsUrlMock,
	getCircleMock,
	loggerErrorMock,
} = vi.hoisted(() => ({
	axiosGetMock: vi.fn(),
	generateOcsUrlMock: vi.fn(),
	getCircleMock: vi.fn(),
	loggerErrorMock: vi.fn(),
}))

vi.mock('@nextcloud/axios', () => ({
	default: {
		get: axiosGetMock,
	},
}))

vi.mock('@nextcloud/l10n', async (importOriginal) => {
	const actual = await importOriginal<NextcloudL10n>()

	return {
		...actual,
		t: (_app: string, message: string) => message,
	}
})

vi.mock('@nextcloud/router', () => ({
	generateOcsUrl: generateOcsUrlMock,
}))

vi.mock('vuex', () => ({
	useStore: () => ({
		getters: {
			getCircle: getCircleMock,
		},
	}),
}))

vi.mock('../../logger.ts', () => ({
	logger: {
		error: loggerErrorMock,
	},
}))

interface Activity {
	activity_id: number
	user: string
	subject: string
	datetime: string | number | null
}

function makeActivities(count: number, startAt = 1): Activity[] {
	return Array.from({ length: count }, (_, index) => ({
		activity_id: startAt + index,
		user: `user-${startAt + index}`,
		subject: `subject-${startAt + index}`,
		datetime: '2026-01-01T12:00:00.000Z',
	}))
}

function mockOcsResponse(activities: Activity[]) {
	return {
		data: {
			ocs: {
				data: activities,
			},
		},
	}
}

describe('TeamActivityView', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		generateOcsUrlMock.mockReturnValue('/ocs/activity/filter')
		getCircleMock.mockReturnValue({
			circleId: '42',
		})
		;(window as typeof window & { OC?: { appswebroots?: Record<string, string> } }).OC = {
			appswebroots: {
				activity: '/apps/activity',
			},
		}
	})

	it('renders unavailable state when team cannot be loaded', async () => {
		getCircleMock.mockReturnValue(null)

		const wrapper = shallowMount(TeamActivityView, {
			props: { teamId: 'team-1' },
		})

		await flushPromises()

		const emptyContent = wrapper.get('nc-empty-content-stub')
		expect(emptyContent.attributes('name')).toBe('Activity unavailable')
		expect(axiosGetMock).not.toHaveBeenCalled()
	})

	it('renders empty state when API returns no activities', async () => {
		axiosGetMock.mockResolvedValueOnce(mockOcsResponse([]))

		const wrapper = shallowMount(TeamActivityView, {
			props: { teamId: 'team-1' },
		})

		await flushPromises()

		expect(generateOcsUrlMock).toHaveBeenCalledWith('/apps/activity/api/v2/activity/filter')
		const emptyContent = wrapper.get('nc-empty-content-stub')
		expect(emptyContent.attributes('name')).toBe('No activity yet')
	})

	it('renders activities and unknown time fallback', async () => {
		axiosGetMock.mockResolvedValueOnce(mockOcsResponse([
			{
				activity_id: 1,
				user: 'alice',
				subject: 'Alice created the team',
				datetime: '2026-01-01T12:00:00.000Z',
			},
			{
				activity_id: 2,
				user: 'bob',
				subject: 'Bob joined the team',
				datetime: null,
			},
		]))

		const wrapper = shallowMount(TeamActivityView, {
			props: { teamId: 'team-1' },
		})

		await flushPromises()

		expect(axiosGetMock).toHaveBeenCalledWith('/ocs/activity/filter', {
			params: {
				object_type: 'circles',
				object_id: '42',
				limit: 50,
			},
		})
		expect(wrapper.text()).toContain('Alice created the team')
		expect(wrapper.text()).toContain('Bob joined the team')
		expect(wrapper.text()).toContain('Unknown time')
		expect(wrapper.findAll('nc-date-time-stub')).toHaveLength(1)
	})

	it('loads and appends more activities when clicking load more', async () => {
		const initialActivities = makeActivities(50, 1)
		const additionalActivities = makeActivities(2, 51)
		axiosGetMock
			.mockResolvedValueOnce(mockOcsResponse(initialActivities))
			.mockResolvedValueOnce(mockOcsResponse(additionalActivities))

		const wrapper = shallowMount(TeamActivityView, {
			props: { teamId: 'team-1' },
		})

		await flushPromises()

		const loadMoreButton = wrapper.find('.team-activity-view__load-more')
		expect(loadMoreButton.exists()).toBe(true)

		await loadMoreButton.trigger('click')
		await flushPromises()

		expect(axiosGetMock).toHaveBeenNthCalledWith(2, '/ocs/activity/filter', {
			params: {
				object_type: 'circles',
				object_id: '42',
				limit: 50,
				since: 50,
			},
		})
		expect(wrapper.findAll('.activity-item')).toHaveLength(52)
	})
})
