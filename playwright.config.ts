/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineConfig, devices } from '@playwright/test'

// See https://playwright.dev/docs/test-configuration
export default defineConfig({
	testDir: './playwright/e2e',

	/* Run tests in files in parallel */
	fullyParallel: true,
	/* Fail the build on CI if you accidentally left test.only in the source code. */
	forbidOnly: !!process.env.CI,
	/* Retry on CI only */
	retries: process.env.CI ? 2 : 0,
	/* Opt out of parallel tests on CI. */
	workers: process.env.CI ? 1 : undefined,
	/* Reporter to use. See https://playwright.dev/docs/test-reporters */
	reporter: process.env.CI ? 'github' : 'html',

	use: {
		/* Base URL so tests can navigate with relative paths, e.g. page.goto('settings/admin/sharing') */
		baseURL: 'http://localhost:8089/index.php/',
		/* Collect a trace when retrying a failed test. See https://playwright.dev/docs/trace-viewer */
		trace: 'on-first-retry',
	},

	projects: [
		// One-off setup: enable circles and apply test config in the container.
		{
			name: 'setup',
			testDir: './playwright/support',
			testMatch: /setup\.ts$/,
		},
		{
			name: 'chromium',
			use: { ...devices['Desktop Chrome'] },
			dependencies: ['setup'],
		},
	],

	webServer: {
		// Spins up a throwaway Nextcloud container with circles mounted (see start-nextcloud-server.mjs).
		command: 'npm run start:nextcloud',
		reuseExistingServer: !process.env.CI,
		url: 'http://127.0.0.1:8089',
		stderr: 'pipe',
		stdout: 'pipe',
		timeout: 5 * 60 * 1000, // up to 5 minutes for first-run container creation
	},
})
