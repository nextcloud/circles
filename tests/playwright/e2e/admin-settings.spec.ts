/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { adminTest as test } from '../support/fixtures.ts'

// Smoke test against UI that already ships today: the "Federated Teams" admin
// section (lib/Settings/Admin.php registers it under the "sharing" section).
// It proves the full harness works end to end: container up, circles enabled,
// admin authenticated, app bundle served.
test.describe('Admin settings', () => {
	test('shows the Federated Teams section', async ({ page }) => {
		await page.goto('settings/admin/sharing', { waitUntil: 'networkidle' })

		await expect(page.getByRole('heading', { name: 'Federated Teams' })).toBeVisible()
	})

	test('filters teams by name', async ({ page }) => {
		const matchingTeam = 'Search match'
		const otherTeam = 'Search other'
		await page.route('**/ocs/v2.php/apps/circles/admin/teamfolders', (route) => route.fulfill({
			contentType: 'application/json',
			body: JSON.stringify({
				ocs: {
					data: [
						{ teamId: 'matching-team', teamName: matchingTeam, defaultQuota: null, folder: null },
						{ teamId: 'other-team', teamName: otherTeam, defaultQuota: null, folder: null },
					],
				},
			}),
		}))

		await page.goto('settings/admin/teams', { waitUntil: 'networkidle' })
		const search = page.getByRole('searchbox', { name: 'Search team by name' })
		await expect(page.getByRole('cell', { name: matchingTeam })).toBeVisible()
		await expect(page.getByRole('cell', { name: otherTeam })).toBeVisible()

		await search.fill('match')
		await expect(page.getByRole('cell', { name: matchingTeam })).toBeVisible()
		await expect(page.getByRole('cell', { name: otherTeam })).not.toBeVisible()

		await search.fill('no matching team')
		await expect(page.getByText('No teams match your search.')).toBeVisible()
	})
})
