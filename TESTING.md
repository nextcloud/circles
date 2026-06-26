<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

# Front-end tests

The app has two layers of front-end tests:

- **Vitest** for unit and component tests. Fast, runs in jsdom, no server needed.
- **Playwright** for end-to-end tests. Starts a throwaway Nextcloud instance in Docker and drives a real browser.

PHP tests (PHPUnit, Psalm) live under `tests/` and are documented separately.

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
