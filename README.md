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

# Embedded team resources

Teams uses Nextcloud's existing Reference/Smart Picker widget API to display
shared resources inside the team layout. Apps register reference providers and
widgets; Teams resolves the selected resource URL and renders its registered
widget in interactive mode, without a Deck-specific frontend integration.

The page dispatches `OCP\Collaboration\Reference\RenderReferenceEvent` to load
enabled apps' widget registrations. Deck's existing `deck-board` widget can
render an interactive board; the separate Deck web component is not required.
Other apps can participate through the same API without a new Teams adapter.

Resource routes use
`/apps/circles/teams/team/{teamId}/resource/{providerId}/{resourceId}`. Teams only
resolves resources confirmed in the current team's shared resources. Apps still
enforce their own permissions. The team navigation stays visible, highlighting
the pinned board or "Shared with the team" for other shared resources.

When no accessible widget is available, Teams shows an explicit unavailable
state with an optional "Open in app" link in a new tab. It never switches apps
automatically. Widgets are responsible for their own content and interactions;
some only provide previews, even when interactive mode is requested.

The existing widget API does not define navigation-state updates, readiness or
asynchronous error/cancellation callbacks. Consequently, card-level URLs and
browser-history synchronization from the earlier Deck-specific integration are
not supported. Teams cancels pending reference requests and calls the widget's
cleanup callback with its original mount element when leaving a resource.
Providers remain responsible for cancelling their own asynchronous mounting and
disposing their app state.
