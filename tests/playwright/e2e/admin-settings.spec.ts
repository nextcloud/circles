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

	for (const width of [1280, 768, 390]) {
		test(`shows storage pools and rejects invalid limits at ${width}px`, async ({ page }, testInfo) => {
			await page.setViewportSize({ width, height: 900 })
			const writes: string[] = []
			await page.route('**/ocs/v2.php/apps/circles/admin/storage-pools', async (route) => {
				if (route.request().method() !== 'GET') {
					writes.push(route.request().method())
				}
				await route.fulfill({ contentType: 'application/json', body: JSON.stringify({ ocs: { data: {
					supported: true,
					pools: [
						{ id: 'default', groupId: null, limit: 107374182400, allocated: 21474836480, used: 1073741824, hasUnlimitedFolders: false },
						{ id: 'group:engineering', groupId: 'engineering', limit: 53687091200, allocated: 42949672960, used: 2147483648, hasUnlimitedFolders: false },
						{ id: 'team:team1', groupId: null, teamId: 'team1', limit: 85899345920, allocated: 32212254720, used: 4294967296, hasUnlimitedFolders: false },
					],
				} } }) })
			})
			await page.route('**/ocs/v2.php/apps/circles/settings/groups', (route) => route.fulfill({
				contentType: 'application/json',
				body: JSON.stringify({ ocs: { data: [{ id: 'engineering', label: 'Engineering' }] } }),
			}))
			await page.route('**/ocs/v2.php/apps/circles/admin/teamfolders', (route) => route.fulfill({
				contentType: 'application/json',
				body: JSON.stringify({ ocs: { data: [{ teamId: 'team1', teamName: 'Design', defaultQuota: 1073741824, folder: { id: 42, mountPoint: 'Design folder', quota: 1073741824 }, storagePoolId: 'team:team1', usedStorage: 3221225472 }] } }),
			}))
			await page.goto('settings/admin/teams', { waitUntil: 'networkidle' })
			await expect(page.getByRole('cell', { name: 'Storage pool', exact: true })).toContainText('Design (Team)')
			await expect(page.getByRole('cell', { name: '80 GB', exact: true })).toBeVisible()
			await expect(page.getByRole('cell', { name: '3 GB', exact: true })).toBeVisible()
			await page.getByRole('tab', { name: 'Default quotas', exact: true }).click()
			const defaultLimit = page.getByRole('textbox', { name: 'Total storage for everyone else', exact: true })
			await expect(defaultLimit).toBeVisible()
			await expect(page.getByRole('textbox', { name: 'Total storage for Engineering', exact: true })).toBeVisible()
			await expect(page.getByRole('textbox', { name: 'Total storage for Design', exact: true })).toHaveValue('80 GB')
			const teamPoolRow = page.getByRole('row').filter({ has: page.getByRole('textbox', { name: 'Total storage for Design', exact: true }) })
			await expect(teamPoolRow.getByText('30 GB', { exact: true })).toBeVisible()
			await expect(teamPoolRow.getByText('4 GB', { exact: true })).toBeVisible()
			if (width > 1200) {
				const quotaBounds = await teamPoolRow.getByRole('cell', { name: 'Default quota for Design', exact: true }).boundingBox()
				const limitBounds = await page.getByRole('textbox', { name: 'Total storage for Design', exact: true }).boundingBox()
				expect(quotaBounds).not.toBeNull()
				expect(limitBounds).not.toBeNull()
				expect(quotaBounds!.x + quotaBounds!.width).toBeLessThanOrEqual(limitBounds!.x)
			}
			for (const control of await page.locator('#team-folder-default-quotas-panel input, #team-folder-default-quotas-panel button').all()) {
				if (await control.isVisible()) {
					const bounds = await control.boundingBox()
					expect(bounds).not.toBeNull()
					expect(bounds!.x).toBeGreaterThanOrEqual(0)
					expect(bounds!.x + bounds!.width).toBeLessThanOrEqual(width)
				}
			}
			await defaultLimit.fill('-1 GB')
			await page.locator('.team-folders__quota-save').getByRole('button', { name: 'Save', exact: true }).click()
			await expect(page.getByText('Storage pool limit must be a non-negative size or Unlimited.')).toBeVisible()
			expect(writes).toEqual([])
			await page.screenshot({ path: testInfo.outputPath(`storage-pools-${width}.png`), fullPage: true })
		})
	}
})
