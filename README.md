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

## Team Folder Storage Pools

Storage pools require a compatible Nextcloud server and Team Folders provider implementing `OCP\Teams\ITeamFolderPoolProvider`. Administrators configure them under **Administration settings > Teams > Default quotas**. Default folder quotas remain separate from pool limits: changing a default does not change existing folder quotas.

- A pool accounts for the sum of assigned team folder quotas, not the size of stored files. A finite pool cannot contain a folder with unlimited quota.
- Pool limits use `0` for no capacity and **Unlimited** for no limit. Folder quotas retain their existing `0` = unlimited meaning.
- Both Nextcloud groups and local teams can have a storage pool. Team rows expose an independent **Total storage** limit, just like group rows.
- A new local team uses the financing source with the largest total pool limit among the creator's actual groups and membership teams with a configured pool. Unlimited pools take precedence; equal limits are resolved by the typed pool ID. Default folder quotas do not determine the source. The creation wizard lets the creator pick another eligible source instead. If no source qualifies, the team uses the configurable default pool for everyone else. A full selected pool does not cause a fallback to another source.
- **Allocated storage** shows reserved folder quotas. **Used storage** separately shows cached file sizes of the financed folder roots; unknown cache sizes remain **Unknown**, not zero. Usage reads do not scan files, and actual file usage does not replace quota-based enforcement. The team-folder list also shows each folder's own used storage and its assigned pool's total limit.
- Assignment remains stable when membership, ownership or pool limits change. It does not grant group membership or access to files. Administrators can move a team under **Team folders**; the destination must accommodate its current folder quota.
- Existing teams are not automatically assigned during migration. Administrators explicitly assign unassigned teams; their existing folder quotas are then included in the destination pool.
- If automatic folder creation exceeds a pool limit, the team remains available and the creation wizard reports the folder failure. Creating, linking or increasing a folder quota cannot bypass the pool limit, including for administrators.
- A deleted financing group remains accounted for. Move its teams to another pool before increasing their allocation. Removing a pool from **Default quotas** or deleting its financing team moves all teams it finances to Everyone else, even if that exceeds the Everyone else limit; afterwards only quota decreases are possible there until capacity is available. Retaining a pooled folder while unlinking it is restricted to server administrators; the retained ordinary folder is outside the team pool.

# Testing
Front-end tests (Vitest for unit/component, Playwright for end-to-end) are documented in [TESTING.md](TESTING.md).

