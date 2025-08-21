/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createAppConfig } from '@nextcloud/vite-config'
import { join } from 'node:path'

export default (env) => createAppConfig({
	dashboard: join(import.meta.dirname, 'src/dashboard.ts'),
}, {
	appName: 'teams',
	emptyOutputDirectory: { additionalDirectories: ['css'] },
	extractLicenseInformation: {
		includeSourceMaps: true,
	},
	config: {
		build: {
			watch: env.mode === 'development'
				? { allowInputInsideOutputPath: true }
				: undefined,
		},
	},
})(env)
