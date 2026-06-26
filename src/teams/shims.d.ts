/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// vuex 4 ships types that don't resolve under "bundler" module resolution;
// the ported team page only needs them loosely.
declare module 'vuex'

// vue-material-design-icons ships .vue single-file components without type
// declarations; treat them as generic Vue components.
declare module 'vue-material-design-icons/*.vue' {
	import type { DefineComponent } from 'vue'

	const component: DefineComponent<{
		size?: number | string,
		fillColor?: string,
		title?: string,
	}>
	export default component
}
