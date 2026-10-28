<!--
  - SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Nextcloud Teams
[![REUSE status](https://api.reuse.software/badge/github.com/nextcloud/circles)](https://api.reuse.software/info/github.com/nextcloud/circles)

### Bring cloud-users closer together

Nextcloud Teams allows people to create their own groups of people/colleagues/friends.
Those groups can then be used by any other app for sharing purposes.

The app was formerly named circles which is still used as internal identifier a lot.


# Installation
The *Teams* app is installed and enabled by default.

# Documentation
You can use the `\Psr\Container\ContainerInterface`, see [dependency injection](https://docs.nextcloud.com/server/stable/developer_manual/basics/dependency_injection.html), to get the `\OCA\Circles\CirclesManager` class, see our [API documentation](https://nextcloud.github.io/circles/) for its interface.

# Testing
Front-end tests (Vitest for unit/component, Playwright for end-to-end) are documented in [TESTING.md](TESTING.md).

## App-owned Teams navigation and embedded views

Apps contribute their own integrations through
[`ITeamAppProvider`](lib/Contracts/ITeamAppProvider.php). This is a Circles API,
not an OCP bootstrap extension. It complements the existing
`OCP\Teams\ITeamResourceProvider`: shared resources continue to use that API.

In the owning app's bootstrap, register a listener for
`OCA\Circles\Events\RegisterTeamAppProvidersEvent`. The listener calls
`$event->registerProvider('myapp', MyTeamsProvider::class)`. The provider's
`getId()` must match its owning app ID. Its `load()` method adds its lightweight
browser registration script and any initial state. The registry instantiates
only providers whose apps are enabled for the current user. Use lazy event
listeners rather than eagerly loading the provider when Circles is absent.

The script registers a browser provider with `window.OCA.Circles.Teams`.
If that API is not present yet, listen once for `circles:teams:ready` and register
then. Check `apiVersion === 1`. The host publishes the API before announcing
readiness and waits for the page's deferred scripts before loading providers.

```typescript
const provider = {
	id: 'myapp',
	order: 50,
	async load(context) {
		return {
			entries: [{ id: 'example', label: 'Example' }],
			actions: [],
			resourceKeys: [],
		}
	},
	async mount(element, context, entryId) {
		context.signal.throwIfAborted()
		const view = await mountMyView(element, context.teamId, entryId)
		return () => view.destroy()
	},
}
```

The complete structural TypeScript contract is in
[`types.ts`](src/teams/integrations/types.ts). Apps may type the subset they use
locally; they do not need runtime imports from Circles or access to its Vue,
Pinia, or router instances.

- `load(context)` returns stable resource IDs, localized labels/icons, actions,
  and either external `href` targets or entries rendered by `mount`.
- `mount` is optional for external-link providers. It must resolve the resource
  for the current team and user, respect `context.signal`, and return cleanup.
  Never treat an entry ID or pin as proof of authorization. Heavy editors should
  be imported lazily here.
- `context` supplies the team, role information, folder, shared resources,
  `prompt`, `confirm`, provider-scoped `getPins`/`setPins`, `refresh`, and `open`.
  After mutations, await `refresh()` before opening a newly created entry.
  The context is invalidated when leaving the team.
- Each action has `id`, `label`, optional MDI `icon`, and `run`. Errors must
  propagate to the host, which logs them and notifies the user. Do not return
  success-shaped results after a failed operation.
- `resourceKeys` lists only `{ providerId, resourceId }` pairs actually
  represented by navigation entries. This prevents duplicate shared-resource
  rows without hiding resources when a provider fails.
- Navigation IDs are namespaced as `provider:entry`. Individual entries can
  declare `legacyIds` to retain an older saved ordering. A provider can declare
  `routeAliases`, such as `{ path: 'page/:fileId', entryParam: 'fileId' }`, to
  redirect old team-relative URLs to the generic embedded route. Alias paths
  must be one literal segment followed by one parameter.
- Members can read pins at
  `GET /ocs/v2.php/apps/circles/teams/{teamId}/apps/{providerId}/pins`.
  Admins/owners can write `{ "ids": ["42"] }` with `PUT`. Responses contain
  `{ "ids": [...] }` inside the OCS data envelope. IDs must be non-empty strings,
  at most 64 bytes each, with at most 100 pins. Pins are shared team metadata,
  scoped to a registered provider, and never grant file access.

Text and Whiteboard provide embedded views. Deck and Collectives contribute
external links. Native Teams sections remain in Circles. There is no hard-coded
fallback for older app versions: upgrade the host and integration apps together.
Provider failures are reported separately and do not hide generic shared
resources.
