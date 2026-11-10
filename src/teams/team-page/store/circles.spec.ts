/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createStore } from 'vuex'
import Circle from '../models/circle.ts'
import Member from '../models/member.ts'
import * as api from '../services/circles.ts'
import circles from './circles.js'

vi.mock('../services/circles.ts', () => ({ getCircleMembers: vi.fn() }))
vi.mock('@nextcloud/dialogs', () => ({ showError: vi.fn() }))

function createCircleStore(canViewMembers: boolean) {
	const store = createStore({
		...circles,
		state: () => ({ circles: {}, loadingCircleMembers: false }),
	})
	const circle = new Circle({
		id: 'team',
		owner: { id: 'owner', level: 9 },
		initiator: { id: 'actor', level: 1 },
		canViewMembers,
	})
	circle.addMember(new Member({ id: 'cached', singleId: 'cached' }, circle))
	store.commit('addCircle', circle)
	return store
}

describe('Member list loading', () => {
	beforeEach(() => vi.clearAllMocks())

	it('does not fetch restricted members and clears cached data', async () => {
		const store = createCircleStore(false)

		await store.dispatch('getCircleMembers', { circleId: 'team' })

		expect(api.getCircleMembers).not.toHaveBeenCalled()
		expect(store.getters.getCircle('team').members).toEqual({})
		expect(store.getters.isLoadingCircleMembers).toBe(false)
	})

	it('fetches permitted members with the requested filters', async () => {
		const store = createCircleStore(true)
		vi.mocked(api.getCircleMembers).mockResolvedValue([{ id: 'alice', singleId: 'alice' }])

		await store.dispatch('getCircleMembers', { circleId: 'team', search: 'alice', role: '1', limit: 5 })

		expect(api.getCircleMembers).toHaveBeenCalledWith('team', 'alice', '1', 5)
		expect(Object.keys(store.getters.getCircle('team').members)).toEqual(['alice'])
		expect(store.getters.isLoadingCircleMembers).toBe(false)
	})

	it('handles revoked access without retaining members or failing the team view', async () => {
		const store = createCircleStore(true)
		vi.mocked(api.getCircleMembers).mockRejectedValue({ response: { status: 403 } })

		await store.dispatch('getCircleMembers', { circleId: 'team' })

		expect(store.getters.getCircle('team').members).toEqual({})
		expect(store.getters.getCircle('team').canViewMembers).toBe(false)
		expect(store.getters.isLoadingCircleMembers).toBe(false)
	})

	it('still reports unrelated request failures', async () => {
		const store = createCircleStore(true)
		const error = new Error('Network unavailable')
		vi.mocked(api.getCircleMembers).mockRejectedValue(error)

		await expect(store.dispatch('getCircleMembers', { circleId: 'team' })).rejects.toThrow(error)
		expect(store.getters.isLoadingCircleMembers).toBe(false)
	})
})
