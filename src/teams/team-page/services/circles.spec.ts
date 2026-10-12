/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { describe, expect, it, vi } from 'vitest'
import Member from '../models/member.ts'
import { changeMemberLevel } from './circles.ts'

vi.mock('@nextcloud/axios', () => ({ default: { put: vi.fn() } }))
vi.mock('@nextcloud/router', () => ({ generateOcsUrl: (path: string, params: Record<string, string>) => path.replace(/\{(\w+)\}/g, (_, key: string) => params[key]) }))
vi.mock('../components/CircleDetails/CircleConfigs/CircleConfigCheckboxesList.vue', () => ({ default: {} }))
vi.mock('../components/CircleDetails/CircleConfigs/CircleConfigInvitationLink.vue', () => ({ default: {} }))

describe('visitor level changes', () => {
	it('accepts numeric 2 in the member model and sends it to the level endpoint', async () => {
		const member = new Member({ id: 'member1', level: 1 }, {} as ConstructorParameters<typeof Member>[1])
		member.level = 2
		expect(member.level).toBe(2)
		vi.mocked(axios.put).mockResolvedValueOnce({ data: { ocs: { data: { level: 2 } } } })
		await expect(changeMemberLevel('team1', 'member1', 2)).resolves.toEqual([2])
		expect(axios.put).toHaveBeenCalledWith('apps/circles/circles/team1/members/member1/level', { level: 2 })
	})

	it.each([3, 5])('rejects unsupported numeric level %i before sending', async (level) => {
		const member = new Member({ id: 'member1', level: 1 }, {} as ConstructorParameters<typeof Member>[1])
		expect(() => { member.level = level }).toThrow('Invalid level')
		vi.mocked(axios.put).mockClear()
		await expect(changeMemberLevel('team1', 'member1', level)).rejects.toThrow('Invalid level')
		expect(axios.put).not.toHaveBeenCalled()
	})
})