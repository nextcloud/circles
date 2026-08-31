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
})
