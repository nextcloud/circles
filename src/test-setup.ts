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

// `appName` is a global injected by the Nextcloud runtime; the logger and the
// legacy global mixin reference it at module-evaluation time. Define it so
// transitive imports during tests don't throw a ReferenceError.
(globalThis as Record<string, unknown>).appName = 'circles'

// Some Vue components in the teams SPA rely on the build-time auto-import of
// `t` from `@nextcloud/l10n` (via @nextcloud/vite-config's unimport plugin).
// vitest doesn't run that pipeline, so expose `t`/`n` as globals for the
// component specs that transitively import those files. Components that
// import `t` explicitly are unaffected.
;(globalThis as Record<string, unknown>).t = (pkg: string, text: string, vars?: Record<string, unknown>) => {
	if (vars && typeof text === 'string') {
		return text.replace(/\{(\w+)\}/g, (_, k: string) => String((vars as Record<string, unknown>)[k] ?? `{${k}}`))
	}
	return text
}
;(globalThis as Record<string, unknown>).n = (pkg: string, text: string) => text

// Minimal `window.OC` / `window.OCA` stubs. Several modules read runtime
// globals at import time (e.g. `window.OC.config`, `window.OCA.appsWithoutDirectShare`),
// and the component under test reads `window.OC.appswebroots` to detect
// installed apps. Provide just enough shape for the test suites.
;(window as unknown as Record<string, unknown>).OC = {
	config: {
		version: '35.0.0.0',
		'sharing.maxAutocompleteResults': 25,
	},
	appswebroots: {
		files: '/apps/files',
		spreed: '/apps/spreed',
		collectives: '/apps/collectives',
		calendar: '/apps/calendar',
	},
}
;(window as unknown as Record<string, unknown>).OCA = {}

// Add other global test mocks here as the front-end grows, e.g.:
// - mock `@nextcloud/l10n` so `t()` / `n()` return the source string
// - stub `loadState` from `@nextcloud/initial-state`
// - define `window.OC` / `window.OCA` globals expected by some components
