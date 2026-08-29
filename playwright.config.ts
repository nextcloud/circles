/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/// <reference types="@types/node" />

import { defineConfig, devices } from '@playwright/test'

export default defineConfig({
	testDir: './tests/playwright/e2e',
	fullyParallel: true,
	forbidOnly: !!process.env.CI,
	retries: process.env.CI ? 1 : 0,
	workers: process.env.CI ? 1 : undefined,
	timeout: process.env.CI ? 45_000 : undefined, // on CI allow 1.5x the default timeout to compensate for shared server resources
	reporter: process.env.CI ? [['blob'], ['dot'], ['github']] : 'html',
	use: {
		baseURL: 'http://localhost:8042/index.php/',
		trace: 'on-first-retry',
	},
	projects: [
		{
			name: 'default',
			use: {
				...devices['Desktop Chrome'],
				channel: process.env.CI
					? 'chrome' // on CI use the chrome browser provided by the GitHub Actions runner
					: 'chromium', // locally use the default playwright chromium browser
			},
		},
	],

	webServer: {
		command: 'node tests/playwright/start-nextcloud-server.js',
		env: {
			NEXTCLOUD_PORT: '8042',
		},
		stderr: 'pipe',
		stdout: 'pipe',
		gracefulShutdown: {
			signal: 'SIGTERM',
			timeout: 10_000,
		},
		reuseExistingServer: !process.env.CI,
		timeout: 300_000,
		wait: {
			stdout: /Nextcloud container ready to run Playwright tests/,
		},
	},
})
