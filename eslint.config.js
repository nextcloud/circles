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
]
