# Changelog
All notable changes to this project will be documented in this file.


## 0.12.2

- Fixing a migration bug.


## 0.12.0

- Security: SQL incremented ID is not used anymore; Every request on a Circle will require a 14 chars version of its Unique ID. (API v0.10.0).
- Security: When leaving a circle, shared files are not accessible by said circle anymore.
- Bug: Fix icons.
- Bug: Fix strange behaviour when the app is deleted from disk, but not disabled in the cloud.
- Code design: Getting rid of Mapper/Entity and using pure QueryBuilder.
- Feature: Edit Name and Description of a circle.
- Feature: Activities are now sent by email.
- Feature: Mass invite group members to a circle.
- Feature: Link groups to circle and assign level to linked group.
- UI: fixing some glitches. 
- Global: Private circle are now named Closed circle.
- Global: Hidden circle are now named Secret circle.


## 0.11.0

- Federated circles
- Integration with activity
- New UI
- Bugfixes


## 0.10.0

- Introduction to linked circles (federated-circles)
- Bugfixes to a few SQL requests (pgsql)
- Improvement of some SQL requests
- Compatability with PHP 5.6


## 0.9.6

- Shares: Take Nodes into account.
- API: Returns circle name.
- Misc: Removal of memberships when user is deleted.
- Misc: Bugfixes.
- Misc: All texts reviewed. 


## 0.9.5

- Small database rework
- UI bug fixed.
- API: Creation of new share items
- API: Listing members of a circle


## 0.9.4

- Fixed an SQL error (#51)
- Adding a way to destroy a circle (#50)


## 0.9.3

### Added

- Initial release to Nextcloud appstore
