# Changelog
All notable changes to this project will be documented in this file.


## 0.12.0

- Security: when leaving a circle, shared file are not accessible anymore by said circle.
- Bug: fix icons
- Bug: fix strange behaviour when the app is deleted from the disk, but not disable in the cloud
- Code design: getting rid of Mapper/Entity and using pure QueryBuilder
- Feature: Edit Name and Description of a Circle
- Feature: Activities are now send by mail.
- Feature: Mass invite Group Members to a Circle.
- Feature: Link Groups to Circle and assign level to linked group.


## 0.11.0

- Federated Circles
- Integration with Activity
- new UI
- fixes


## 0.10.0

- Introduction to Linked Circles (federated-circles)
- bug fixing few SQL request (pgsql)
- improving some SQL request
- Compat php5.6


## 0.9.6

- Shares: Take Nodes into account.
- API: returns circle name.
- misc: removing memberships when user is deleted.
- misc: bugfixes.
- misc: review all texts. 


## 0.9.5

- small rework on database
- fixing UI bug.
- API: creation of new share items
- API: listing members of a circle


## 0.9.4

- Fixed an SQL error (#51)
- Adding a way to destroy a Circle (#50)


## 0.9.3

### Added

- Initial release to Nextcloud appstore
