/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { userTest as test } from '../support/fixtures.ts'

// SKELETON for the in-app Teams page (the SPA from nextcloud/circles#2561).
//
// This is the template the app-page team should grow. The tests use `test.fixme`
// so they are reported as expected-to-fail and don't break the suite until the
// page lands. Once the SPA route (`/apps/circles/teams`, see appinfo/routes.php)
// exists:
//   1. remove the `test.fixme(...)` line,
//   2. replace the placeholder selectors with real roles/labels.
//
// Selector rules (see the nextcloud-testing skill):
//   - prefer getByRole() with an accessible name
//   - never select by CSS class, especially third-party ones
//   - use waitForApiResponse() from ../support/helpers.ts before assertions on mutations
test.describe('Teams app page', () => {
	test.fixme(true, 'The in-app Teams page (circles#2561) is not merged yet')

	test('loads the app for a regular user', async ({ page }) => {
		await page.goto('apps/circles/teams', { waitUntil: 'networkidle' })
		await page.waitForURL(/apps\/circles\/teams/)

		// TODO: replace with a real landmark of the page, e.g.
		// await expect(page.getByRole('heading', { name: 'Teams' })).toBeVisible()
		await expect(page.getByRole('main')).toBeVisible()
	})

	test('lets a user create a team', async ({ page }) => {
		await page.goto('apps/circles/teams', { waitUntil: 'networkidle' })

		// TODO: drive the real create-team flow, e.g.
		// const created = waitForApiResponse(page, 'POST')
		// await page.getByRole('button', { name: 'Create team' }).click()
		// await created
		// await expect(page.getByRole('heading', { name: 'My team' })).toBeVisible()
	})
})
