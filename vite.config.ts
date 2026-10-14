/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Api } from '@vitejs/plugin-vue'
import type { Plugin } from 'vite'

import { createAppConfig } from '@nextcloud/vite-config'
import { join } from 'node:path'

export default (env) => createAppConfig({
	main: join(import.meta.dirname, 'src/main.ts'),
	dashboard: join(import.meta.dirname, 'src/dashboard.ts'),
	'settings-admin': join(import.meta.dirname, 'src/settings-admin.ts'),
	'settings-team-folders': join(import.meta.dirname, 'src/settings-team-folders.ts'),
	'settings-personal': join(import.meta.dirname, 'src/settings-personal.ts'),
}, {
	appName: 'circles',
	assetsPrefix: 'teams-',
	emptyOutputDirectory: { additionalDirectories: ['css'] },
	extractLicenseInformation: {
		includeSourceMaps: true,
	},
	config: {
		plugins: [{
			name: 'teams-deck-custom-element',
			configResolved(config) {
				const plugin = config.plugins.find((candidate): candidate is Plugin<Api> => candidate.name === 'vite:vue')
				if (!plugin?.api) {
					throw new Error('The Vue compiler is required for the Deck custom element')
				}
				const template = plugin.api.options.template ?? {}
				const compilerOptions = template.compilerOptions ?? {}
				const previous = compilerOptions.isCustomElement
				plugin.api.options.template = {
					...template,
					compilerOptions: {
						...compilerOptions,
						isCustomElement: (tag) => tag === 'nextcloud-deck-board' || previous?.(tag) === true,
					},
				}
			},
		}],
		build: {
			watch: process.argv.includes('--watch')
				? { allowInputInsideOutputPath: true }
				: undefined,
		},
		css: {
			modules: {
				// Expose module class names as camelCase so templates can use
				// `$style.teamCardHead` instead of `$style['team-card__head']`.
				localsConvention: 'camelCaseOnly',
			},
		},
	},
})(env)
