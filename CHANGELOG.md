# Changelog
All notable changes to this project will be documented in this file.



### 22.0.0 (NC22)

- Full rewrite of the app for Nextcloud 22
- Type of Circles are gone, replaced by config flags  
- first implementation of federated circles (2.0)
- first implementation of new ExtendedQueryBuilder  
- Important: Circles and Circle memberships will be migrated with cron jobs.
  However, this can take a long time with large databases. The process can
  be speed up by executing the migrations manually. This can still take a
  couple of days. To migrate the Circles and memberships, use `occ`:
  ```bash
  occ circles:sync --migration [--force]
  occ circles:memberships --all
  ```
  It may happen that the `circles:sync` command does not migrate all Circles
  in the first run. Re-run this command until all Circle are migrated. You can
  check if all Circles are migrated with the following SQL query:
  ```sql
  SELECT COUNT(unique_id) FROM nc_circle_circles WHERE unique_id NOT IN (SELECT unique_id FROM nc_circles_circle);
  ```

(changelog in progress)
  

### 0.20.6

- use https and http when in doubt
- reset test_nc_base on all failure of the test
- do not redirect when testing
- filter result on search based on a queue
- circles.force_nc_base can be set in config.php
- better detection of local instance
- force local request on local instance
- better distinction between local_instance (internal) and local_cloud_id (remote)
- fixing the displayed name of the owner in the list of Circles


### 0.20.4

- caching display name
- better maintenance of circles & shares
- fixing an issue with new members


### 0.20.3

- fixing composer lib version


### 0.20.2

- fixing a migration issue on some database setup
- fixing an issue on local instance


### 0.20.0 (NC20)

- compat nc20
- fixing a glitch when confirming a request to join a closed circle
- generate a unique id on circles generated from the Contacts app
- switching to Nextcloud IClient for local request
- log exception on local request
- disabling federated circles in Admin UI


### 0.19.5

- allow circles' owner to define a single password for shares
- fixing issue with async
- fixing some issue in GS setup
- enh: some const are now available within the Api class
- fixing an issue on displayed secret circles on empty search
- A cached name of the member is now broadcasted and displayed
 

### 0.19.4

- includes mails when searching for collaborator
- introduction of Cached Name (GS)
- introduction of Alternate Name
- improvement on the front-end API
- force local cloud id via settings
- new test on Async and GS: ./occ circles:test
 

### 0.19.3

- considering groups as members
- last part of sql optimization
- fixing some auth on dav connection
- group linking disabled on GS setup
- on new external contact, send links to shared files by mail from all instances of GS
- configure your circle to force password on external shares
- allow self-signed cert on configuration


### 0.19.2

- quick fix on some sql request and migration issues
- signing gs request


### 0.19.1

- new database structure
- dynamic route for payload delivery


### 0.19.0 (v1-beta.01)

- GlobalScale.



### 0.18.3

- fixing issue during migration.


### 0.18.0 (nc18)

- compat nc18
- circles as backend for contacts


### 0.17.10

- fixing issue with sqlite


### 0.17.9

- fixing issue during token generation
- token are now remove and not just disabled when remote user is kicked


### 0.17.8

- improvement when sharing a file to mail address: 
- each contact have his own link to the file, and password is generated if enforced.
- access to shared file is disable if the account is removed from the circle.
- when adding a contact to a circle with already existing shares, a list of the shares is sent by mail  


### 0.17.7

- lighter requests on request on Shares
- new settings to allow moderator to add member to closed circle without invitation step
- new icons


### 0.17.5

- bugfix


### 0.17.4

- prevent user enumeration
- apply limit to linked group


### 0.17.3

- fixing an issue on the front-end with linked groups


### 0.17.2

- more logging
- add multiple mails address.


### 0.17.1

- more APIs.
- Allow disable of Activity on Circle creation.
- fixing some div overlay.


### 0.17.0 (NC16)

- some new APIs.


## 0.13.4

- bugfixes.


## 0.13.0

- Feature: Circles Async is now available on every shares rendering the UX a lot smoother.
- Feature: The stability of Circles Async is testable from the Admin Interface.
- Feature: mail address can be added as a member of a Circle.
- Feature: contact can be added as a member of a Circle.
- Feature: When sharing a file to a Circle, all non-local member (Mail address or Contact) will receive a link to the shared files by mail. 
- Feature: the older Admin of a Circle becomes Owner if current Owner's account deleted. If the Circle has no Admin, the Circle is deleted.
- api: Circles::getSharesFromCircle()/ShotgunCircles::getSharesFromCircle() returns SharingFrame[]
- Fix: Unexpected behaviour when an the account of a circle owner is removed from the cloud
- Code: Automatic DI
- Code: Compatibility NC13 collaboration search
- New Command: ./occ circles:clean
- API: The app will dispatch some events (by Vinicius Cubas Brand <viniciuscb@gmail.com>)


		\OCA\Circles::onCircleCreation
		\OCA\Circles::onCircleDestruction
		\OCA\Circles::onMemberNew
		\OCA\Circles::onMemberInvited
		\OCA\Circles::onMemberRequesting
		\OCA\Circles::onMemberLeaving
		\OCA\Circles::onMemberLevel
		\OCA\Circles::onMemberOwner
		\OCA\Circles::onGroupLink
		\OCA\Circles::onGroupUnlink
		\OCA\Circles::onGroupLevel
		\OCA\Circles::onLinkRequestSent
		\OCA\Circles::onLinkRequestReceived
		\OCA\Circles::onLinkRequestRejected
		\OCA\Circles::onLinkRequestCanceled
		\OCA\Circles::onLinkRequestAccepted
		\OCA\Circles::onLinkRequestAccepting
		\OCA\Circles::onLinkUp
		\OCA\Circles::onLinkDown
		\OCA\Circles::onLinkRemove
		\OCA\Circles::onSettingsChange


## 0.12.4

- Fixing a migration bug.
- Add Type to members.


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
