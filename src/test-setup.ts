/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// Global setup run once before the test suite (see `setupFiles` in vitest.config.ts).
//
// jsdom does not implement matchMedia, but several @nextcloud/vue components call it
// on mount. Provide a no-op stub so component tests don't crash.
if (!window.matchMedia) {
	window.matchMedia = (query: string) => ({
		matches: false,
		media: query,
		onchange: null,
		addEventListener: () => {},
		removeEventListener: () => {},
		addListener: () => {}, // deprecated, kept for older consumers
		removeListener: () => {}, // deprecated
		dispatchEvent: () => false,
	}) as unknown as MediaQueryList
}

// Add other global test mocks here as the front-end grows, e.g.:
// - mock `@nextcloud/l10n` so `t()` / `n()` return the source string
// - stub `loadState` from `@nextcloud/initial-state`
// - define `window.OC` / `window.OCA` globals expected by some components
