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
- PHP context contains the authenticated user ID, team ID/name, server-checked
  admin role, team folder, shared resources, `navigationEntryIds`, and a host-owned `saveNavigationEntryIds`
  callback. It is never constructed from browser-supplied role or resource data.
- Actions are data: `id`, `label`, optional `icon`, and one optional `dialog`.
  PHP accepts a `TeamAppActionPrompt`, `TeamAppActionConfirmation`, or
  `TeamAppResourcePicker` as the `dialog` constructor argument. JSON uses
  `{type: "prompt", title, value?}`, `{type: "confirm", title, message}`, or
  `{type: "resourcePicker", title, resources, selectedIds?}`. The host refreshes the
  action description before opening its dialog. Cancel sends no mutation;
  an empty picker selection explicitly clears navigation entries. At most 100 resources
  can be selected.
- The host posts user input to PHP `executeAction($context, $actionId, $parameters)`.
  Prompt input is `parameters.value`; picker input is `parameters.ids`.
  The provider rechecks access and validates input. It can return `navigationEntryIds` to
  update provider-scoped metadata and `open` to navigate after the host refreshes.
  The PHP `saveNavigationEntryIds` callback allows creation to roll back a new file if saving
  its navigation entry fails. Failures must propagate instead of returning success-shaped data.
- Full app navigation uses authenticated
  `GET /ocs/v2.php/apps/circles/teams/{teamId}/apps/{providerId}/navigation`.
  Actions use CSRF-protected `POST` to the corresponding
  `/actions/{actionId}` with `{ "parameters": {...} }`. The host requires team
  admin access and rechecks the currently advertised action before dispatch.
- Shared resources are listed independently of app navigation. Only the team's
  own folder is excluded from "Shared with the team"; other group folders remain visible.
- Navigation IDs use `provider-id` (for example `deck-42`). Circles' `TeamTabsController`
  migrates its old `board-*`, `page-*` and `collective` tab-order IDs on read;
  the next admin reorder persists canonical IDs. Providers do not need migration
  aliases. Embedded views use the generic `team/{teamId}/app/{providerId}/{entryId}` route.
- Members can read navigation entry IDs at
  `GET /ocs/v2.php/apps/circles/teams/{teamId}/apps/{providerId}/navigation/selection`.
  Admins/owners can write `{ "navigationEntryIds": ["42"] }` with `PUT`. Responses contain
  `{ "navigationEntryIds": [...] }` inside the OCS data envelope. IDs must be non-empty strings,
  at most 64 bytes each, with at most 100 navigation entries. These are shared team metadata,
  scoped to a registered provider, and never grant file access.
  Existing `teamsAppPins_*` setting keys remain in use to preserve saved navigation.

 Native Teams sections remain in Circles. There is no hard-coded
fallback for older app versions: upgrade the host and integration apps together.
Provider failures are reported separately and do not hide generic shared
resources.
