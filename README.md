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
`OCP\Teams\ITeamAppProvider`, a public interface in Nextcloud Server since
36.0.0. It complements the existing
`OCP\Teams\ITeamResourceProvider`: shared resources continue to use that API.

Implement this interface and declare the provider once in the owning app's
`Application::register(IRegistrationContext $context)`:

```php
$context->registerTeamAppProvider(MyTeamsProvider::class);
```

No registration event or event listener is required. The provider's
`getId()` must match its owning app ID. The registry instantiates
only providers whose apps are enabled for the current user, using the core
bootstrap declarations. Implementing the interface alone does not discover a
class: the bootstrap declaration supplies its class name without eagerly loading
it. Apps supporting older server versions can guard the declaration with
`interface_exists(\OCP\Teams\ITeamAppProvider::class)`.

The registry loads app translations and provides app IDs and ordering to the
frontend. The frontend provider's `getNavigation(context)` fetches navigation
entries and actions from the corresponding PHP provider's `getNavigation()`.

Embedded content uses existing Smart Picker reference widgets. The Teams page dispatches
`OCP\Collaboration\Reference\RenderReferenceEvent`, allowing existing app listeners
to enqueue their reference scripts/styles. These use the normal `registerWidget`
hooks from `@nextcloud/vue/components/NcRichText`.

Return an entry with both `href` (the Open/fallback URL) and `referenceUrl` (the
canonical resource URL for preview resolution). The host resolves only the opened
entry through `NcReferenceList`, using the same reference API and widgets as the
Text editor. Existing widgets control their interactive capabilities; supported
interactive views open immediately, without requiring a click to activate them.
The host waits for deferred reference scripts before rendering the list.
Entries with only `href` remain external links.

The list displays the standard reference and, where supported, a fallback link
preview, while the Open link remains available. Resolution failures are logged by `NcReferenceList`,
as in the Text editor.
Reference-provider authorization and inaccessible-resource presentation still
apply. Widgets must provide real destruction hooks and handle repeated renders
and asynchronous cancellation. Team, entry, or reference URL changes destroy the
previous list; requests made by the list are not cancelled.
The typed entry/action contract remains in server `core/src/types/teams.d.ts`.

- PHP `getOrder()` controls provider order. PHP `getNavigation($context)` returns stable IDs,
  localized labels/icons, actions, and entries with an `href` and
  optional `referenceUrl`. Never treat an entry ID as proof of resource access.
- Entry, action and picker-resource `icon` values are either an emoji or SVG path
  data for a `0 0 24 24` view box. Paths start with an `M`/`m` move command;
  other values render as escaped text. Full SVG markup and image URLs are not supported.
- Both PHP provider methods receive the team ID/name, authenticated user ID,
  server-checked admin role, and optional team folder as typed parameters. They do not
  receive shared resources or saved navigation IDs; providers resolve team resources
  through `ITeamManager::getSharedWith()` when needed. Values are never constructed from
  browser-supplied role or resource data.
- Providers return all available navigation entries. Circles adds `selectedEntryIds` to
  the navigation response from its saved settings; the client displays selected entries
  in the sidebar and offers host-owned add/remove actions for every candidate. Providers
  do not receive the saved ID list.
- Actions are data: `id`, `label`, optional `icon`, and an optional prompt or confirmation
  dialog. JSON uses `{type: "prompt", title, value?}` or
  `{type: "confirm", title, message}`. The host refreshes the action description before
  opening its dialog. Cancel sends no mutation. Providers can return navigation ID changes
  for app-owned operations, and the host applies them to saved state. At most 100
  navigation entries can be saved.
- Individual entries can be added or removed through the host-owned
  `POST /ocs/v2.php/apps/circles/teams/{teamId}/apps/{providerId}/entries/navigation`
  endpoint with `entryId` and `inNavigation`. Adding requires the entry to still be
  returned by its provider; removing is allowed even if the provider no longer returns it.
- The host posts user input to PHP `executeAction($context, $actionId, $parameters)`.
  Prompt input is `parameters.value`.
  The provider rechecks access and validates input. It can return
  `navigationEntryIdsToAdd`, `navigationEntryIdsToRemove`, and `open`. The host applies
  those changes to provider-scoped metadata and refreshes navigation. If saving a new
  resource's navigation entry fails, the resource itself is not rolled back; the failure
  propagates instead of returning success-shaped data.
- Team navigation loads all providers in one authenticated
  `GET /ocs/v2.php/apps/circles/teams/{teamId}/apps/navigation` request.
  The response contains `providers`, keyed by provider ID, and `errors`, a list
  of failed provider IDs. Team context and saved settings are loaded once;
  one provider's failure does not hide another provider's navigation.
  Shared-resource aggregation also isolates and logs individual resource-provider
  failures, retaining resources and navigation from the remaining providers.
  Before opening an action dialog, the host revalidates navigation through the
  same batch endpoint and uses the selected provider's current action description.
  Actions use CSRF-protected `POST` to
  `/ocs/v2.php/apps/circles/teams/{teamId}/apps/{providerId}/actions/{actionId}`
  with `{ "parameters": {...} }`. The host requires team
  admin access and rechecks the currently advertised action before dispatch.
- Shared resources are listed independently of app navigation. Only the team's
  own folder is excluded from "Shared with the team"; other group folders remain visible.
- Navigation IDs use `provider-id` (for example `deck-42`). Circles' `TeamTabsController`
  migrates its old `board-*`, `page-*` and `collective` tab-order IDs on read;
  the next admin reorder persists canonical IDs. Providers do not need migration
  aliases. Embedded views use the generic `team/{teamId}/app/{providerId}/{entryId}` route.
- Saved navigation entry IDs are read server-side into each provider's context.
  Admins/owners change selections through provider actions; there are no separate
  selection read/write endpoints. IDs must be non-empty strings,
  at most 64 bytes each, with at most 100 navigation entries. These are shared team metadata,
  scoped to a registered provider, and never grant file access.
  Existing `teamsAppNavigation_*` setting keys remain in use to preserve saved navigation.

 Native Teams sections remain in Circles. There is no hard-coded
fallback for older app versions: upgrade the host and integration apps together.
Provider failures are reported separately and do not hide generic shared
resources.
