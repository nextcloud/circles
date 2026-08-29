/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Page, Response } from '@playwright/test'

const CIRCLES_API_PATH = '/apps/circles/'

/**
 * Wait for a circles API response matching the given HTTP method.
 * Must be called BEFORE the action that triggers the request, e.g.:
 *
 *   const res = waitForApiResponse(page, 'POST')
 *   await page.getByRole('button', { name: 'Create' }).click()
 *   await res
 *
 * @param page - the current page
 * @param method - the request method to wait for (GET, POST, ...)
 */
export function waitForApiResponse(page: Page, method: string): Promise<Response> {
	return page.waitForResponse((response) => response.request().method() === method
		&& response.request().url().includes(CIRCLES_API_PATH))
}
