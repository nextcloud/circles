/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server'
import { createRandomUser, login } from '@nextcloud/e2e-test-server/playwright'
import { test as base } from '@playwright/test'

/**
 * Build a test fixture whose `page` is authenticated as the resolved user.
 * Each test gets a fresh page (no shared storage state); the API login sets
 * cookies on the page's context, so later `page.goto()` calls are authenticated.
 *
 * @param getUser - resolves the user to log in as
 */
function authenticatedTest(getUser: () => User | Promise<User>) {
	return base.extend({
		page: async ({ browser, baseURL }, use) => {
			const page = await browser.newPage({ storageState: undefined, baseURL })
			await login(page.request, await getUser())
			await use(page)
			await page.close()
		},
	})
}

/** Authenticated as the default admin (admin/admin). Use for admin-settings flows. */
export const adminTest = authenticatedTest(() => new User('admin', 'admin'))

/** Authenticated as a fresh random (non-admin) user. Use for regular end-user flows, e.g. the future Teams app page. */
export const userTest = authenticatedTest(createRandomUser)
