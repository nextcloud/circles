<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

# Front-end tests

The app has two layers of front-end tests:

- **Vitest** for unit and component tests. Fast, runs in jsdom, no server needed.
- **Playwright** for end-to-end tests. Starts a throwaway Nextcloud instance in Docker and drives a real browser.

PHP tests (PHPUnit, Psalm) live under `tests/` and are documented separately.

## Teams provider migration checks

Run the focused host tests with:

```bash
LANG=C npx vitest run src/teams/integrations src/teams/views/TeamAppView.spec.ts src/teams/firstTab.spec.ts src/teams/resourcesStore.spec.ts
npm run ts:check
```

PHP registry and navigation authorization tests are
`tests/unit/lib/Service/TeamAppProviderRegistryTest.php` and
`tests/unit/lib/Controller/TeamAppNavigationControllerTest.php`. The normal PHPUnit
bootstrap requires an installed Nextcloud test instance. Never initialize or
replace the shared development database just to run tests. Collectives' PHP
tests must run only in a disposable isolated test instance.

On an authenticated development instance, manually verify:

1. Enable matching Text, Deck, Collectives, and Whiteboard versions. All four
   register their own entries and creation actions; an unregistered or disabled
   app contributes none. Teams navigation registration is PHP-only: check that
   `teamAppProviders` has no module URLs. The page dispatches `RenderReferenceEvent`
   to load existing Smart Picker scripts. Navigations come from authenticated PHP
   endpoints; the initial load and each refresh make one
   `/teams/{teamId}/apps/navigation` request for all providers, not one request
   per app. Action dialogs revalidate through this same batch endpoint; no
   per-provider navigation or selection requests should be made.
   Opened entries resolve through the core references API.
2. Open Text pages through the Teams navigation and reorder entries. Reload: old saved ordering
   still applies, and the first preview entry is an eligible landing view even
   when it also declares an Open URL. Link-only entries are not landing views.
3. Create a Whiteboard, select existing boards from subfolders, and reload.
   Shared navigation entries persist. Rename keeps the entry identity; a duplicate name
   cannot overwrite a file.
4. Remove an open Whiteboard from navigation: its file remains in Files. Cancel deletion, then
   confirm deletion of a disposable board: only confirmation removes the file.
5. Switch rapidly between teams and preview entries. Widget apps unmount cleanly,
   with no previous-team references or late mounts. Interactive widgets must be
   active immediately on opening and switching entries, without a click
   to activate them. Repeat after leaving a preview idle.
6. Repeat as a member, team admin, and nonmember. Members cannot change navigation entries;
   nonmembers cannot read them. Read-only file permissions still apply.
7. Disable an integration or simulate a failed navigation request. Provider failures
   show an error, do not leave an indefinite loading indicator, and do not hide
   resources from the generic shared list.
8. Test existing file, Deck board, and Collectives page widgets. Text/Whiteboard
   use existing file previews rather than Teams-specific editors. No matching
   widget, a null reference, or OpenGraph-only resolution must leave the Open link
   available. A failed reference resolution is logged by the shared list and the
   Open link remains available. Inaccessible references must not expose content.
   Check translations and widget styles after a fresh load.
9. Open all apps directly and use Smart Picker outside Teams. Their existing app
   bundles and previews must still work; no Teams-specific view module should be
   requested. Bypass browser caching when validating rebuilt assets.

Focused widget lifecycle tests also live in server
`apps/files/src/reference-files.spec.ts`, Deck `src/reference.spec.js`, and
Collectives `src/tests/reference.spec.ts`. They cover enclosing-root cleanup,
interactive rerenders, independent widget instances, and late asynchronous mounts.

## Vitest (unit & component)

### Run

```bash
npm test                 # watch mode
npx vitest run           # single run (for CI / pre-push)
npm run test:coverage    # single run with a coverage report
```

The `test` scripts set `LANG=C` so assertions on formatted strings are stable.

### Where tests live

Specs sit next to the code they cover, as `*.spec.ts` (or `*.test.ts`):

```
src/components/TeamsListItem.vue
src/components/TeamsListItem.spec.ts   ← test for the component above
```

Config is in [`vitest.config.ts`](vitest.config.ts). Global setup (a `matchMedia`
stub, plus the place to add l10n / `window.OC` mocks as the app grows) is in
[`src/test-setup.ts`](src/test-setup.ts).

### Writing a component test

[`src/components/TeamsListItem.spec.ts`](src/components/TeamsListItem.spec.ts) is the
reference. Use `shallowMount` to isolate the component under test from its children:

```typescript
import { shallowMount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import MyComponent from './MyComponent.vue'

describe('MyComponent', () => {
	it('renders the label', () => {
		const wrapper = shallowMount(MyComponent, { props: { label: 'Hello' } })
		expect(wrapper.text()).toContain('Hello')
	})
})
```

- `shallowMount` stubs child components so the test asserts only this component's behaviour. Use `mount` when you need real child output.
- Import `describe` / `it` / `expect` from `vitest`; there are no globals.

## Playwright (end-to-end)

### Requirements

- Docker running locally (the test runner creates the Nextcloud container).
- The Chromium binary, installed once:

```bash
npx playwright install chromium
```

### Run

```bash
npm run test:e2e                                   # all specs (boots the server automatically)
npx playwright test --ui                           # interactive UI, best for local development
npx playwright test --headed                       # watch the browser live
npx playwright test playwright/e2e/admin-settings.spec.ts   # a single spec
npx playwright show-report                          # open the HTML report after a run
```

The container is reused between runs locally (`reuseExistingServer: true` in
[`playwright.config.ts`](playwright.config.ts)), so repeat runs start faster. The
first run pulls the image and can take a few minutes.

### How it works

[`start-nextcloud-server.mjs`](playwright/start-nextcloud-server.mjs) boots a throwaway
Nextcloud container (on the `stable*` branch matching `appinfo/info.xml`) with this app
bind-mounted, exposed on port `8089`. The `setup` project then enables the app via
[`support/setup.ts`](playwright/support/setup.ts) before the test project runs. All of
this is wired through `@nextcloud/e2e-test-server`, the same harness the Forms app uses.

### Directory layout

```
playwright/
├── e2e/
│   ├── admin-settings.spec.ts   # working smoke test: the Federated Teams admin section
│   └── app-page.spec.ts         # skeleton for the Teams SPA (circles#2561), see below
├── support/
│   ├── fixtures.ts              # authenticated test fixtures (see table)
│   ├── helpers.ts               # waitForApiResponse()
│   └── setup.ts                 # enables the app in the container (runs once)
└── start-nextcloud-server.mjs   # boots the throwaway Nextcloud container
```

### Fixtures

Import `test` from [`support/fixtures.ts`](playwright/support/fixtures.ts) so the page
arrives already authenticated:

| Fixture | Logs in as | Use for |
|---|---|---|
| `adminTest` | the default admin | admin-settings flows |
| `userTest` | a fresh random user | regular end-user flows (e.g. the Teams page) |

### The Teams page skeleton

[`app-page.spec.ts`](playwright/e2e/app-page.spec.ts) targets the in-app Teams page at
`/apps/circles/teams` (the SPA from [circles#2561](https://github.com/nextcloud/circles/pull/2561)).
It is marked `test.fixme` so it does not fail the suite until that page lands. Once it
does:

1. remove the `test.fixme(...)` line,
2. replace the placeholder selectors with real roles and labels.

### Writing a spec

```typescript
import { expect } from '@playwright/test'
import { userTest as test } from '../support/fixtures.ts'

test.describe('Teams page', () => {
	test('creates a team', async ({ page }) => {
		await page.goto('apps/circles/teams', { waitUntil: 'networkidle' })
		await expect(page.getByRole('button', { name: 'Create team' })).toBeVisible()
	})
})
```

Selector rules (full list in the `nextcloud-testing` conventions):

- Prefer `getByRole()` with an accessible name. Never select by CSS class, especially third-party ones.
- Call `waitForApiResponse()` from [`support/helpers.ts`](playwright/support/helpers.ts) **before** the action that triggers the request, then await it after, to avoid a race.
- For `NcCheckboxRadioSwitch`, click with `{ force: true }` (the native input is visually hidden).

### Traces

Traces are captured on the first retry of a failing test. Open one with:

```bash
npx playwright show-trace test-results/<test-name>/trace.zip
```

## CI

Both layers run automatically on pull requests, so there's nothing to set up before writing tests:

- **Vitest** — `.github/workflows/node-test.yml` runs `npm run test` and `npm run test:coverage` and uploads coverage. This is the org workflow template (synced from `nextcloud/.github`); don't hand-edit it, the template sync would overwrite your changes.
- **Playwright** — `.github/workflows/playwright.yml` builds the app, installs Chromium, and runs `npx playwright test`. The HTML report is uploaded as a build artifact (`playwright-report`, kept 30 days). This one is app-specific, so edit it here as the suite grows.
