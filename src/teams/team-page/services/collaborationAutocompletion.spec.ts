/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { getCapabilities } from '@nextcloud/capabilities'
import { ShareType } from '@nextcloud/sharing'
import { beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('@nextcloud/axios', () => ({
	default: { get: vi.fn() },
}))

vi.mock('@nextcloud/router', () => ({
	generateOcsUrl: (path: string) => `/ocs/${path}`,
}))

vi.mock('@nextcloud/capabilities', () => ({
	getCapabilities: vi.fn(),
}))

vi.mock('../../../logger.ts', () => ({
	logger: { info: vi.fn() },
}))

vi.mock('../models/constants.ts', () => ({
	SHARES_TYPES_MEMBER_MAP: {},
	CircleConfigs: { FEDERATED: 32768 },
}))

const { getSuggestions } = await import('./collaborationAutocompletion.js')

/**
 * @param alwaysShowUnique - value of the files_sharing always_show_unique capability
 */
function mockCapabilities(alwaysShowUnique: boolean) {
	vi.mocked(getCapabilities).mockReturnValue({
		files_sharing: { sharee: { always_show_unique: alwaysShowUnique } },
	})
}

describe('getSuggestions', () => {
	beforeEach(() => {
		vi.mocked(axios.get).mockResolvedValue({
			data: {
				ocs: {
					data: {
						exact: { users: [] },
						users: [
							{ label: 'Alex', value: { shareType: ShareType.User, shareWith: 'alex1' }, shareWithDisplayNameUnique: 'alex@one.example' },
							{ label: 'Alex', value: { shareType: ShareType.User, shareWith: 'alex2' }, shareWithDisplayNameUnique: 'alex@two.example' },
						],
						emails: [
							{ label: 'Ext', value: { shareType: ShareType.Email, shareWith: 'ext@example.com' } },
						],
					},
				},
			},
		})
	})

	it('shows the email to tell apart users with the same name', async () => {
		mockCapabilities(true)

		const results = await getSuggestions('alex')

		expect(results.map((r: { subname: string }) => r.subname)).toEqual(['alex@one.example', 'alex@two.example', 'ext@example.com'])
	})

	it('hides the user email when the admin disabled it', async () => {
		mockCapabilities(false)

		const results = await getSuggestions('alex')

		expect(results.map((r: { subname: string }) => r.subname)).toEqual(['', '', 'ext@example.com'])
	})
})
