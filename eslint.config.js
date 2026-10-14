/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { recommended } from '@nextcloud/eslint-config'

export default [
	...recommended,
	{
		// Ported team-page code, kept verbatim — not linted while it's modernized.
		ignores: ['src/teams/team-page/**'],
	},
	{
		files: ['src/teams/views/TeamDeckView.vue'],
		// Native attributes and events must match Deck's custom-element contract.
		rules: {
			'vue/no-undef-components': ['warn', { ignorePatterns: ['nextcloud-deck-board'] }],
			'vue/attribute-hyphenation': 'off',
			'vue/v-on-event-hyphenation': 'off',
		},
	},
]
