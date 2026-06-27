/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vitest/config'

// Vitest uses this config in preference to vite.config.ts (the app build config),
// so the production build and the test runner stay isolated from each other.
export default defineConfig({
	plugins: [vue()],
	test: {
		// Co-locate specs next to the code they cover: src/**/*.spec.ts (or .test.ts).
		include: ['src/**/*.{test,spec}.?(c|m)[jt]s?(x)'],
		environment: 'jsdom',
		environmentOptions: {
			jsdom: {
				url: 'http://localhost',
			},
		},
		// lcov is what the codecov CI step uploads; text prints a summary locally.
		coverage: {
			provider: 'v8',
			reporter: ['text', 'lcov'],
		},
		setupFiles: ['src/test-setup.ts'],
		server: {
			deps: {
				// @nextcloud/vue ships untranspiled CSS imports; inline it so jsdom can load components.
				inline: ['@nextcloud/vue'],
			},
		},
	},
})
