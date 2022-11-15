<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @author: Vinicius Cubas Brand <viniciuscb@gmail.com>
 * @copyright 2017
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Circles\Service;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\DeprecatedCirclesRequest;
use OCA\Circles\Db\DeprecatedMembersRequest;
use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Model\FederatedLink;
use OCA\Circles\Model\DeprecatedMember;
use OCP\Activity\IEvent;
use OCP\Activity\IManager as IActivityManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @deprecated
 *
 * Class EventsService
 *
 * @package OCA\Circles\Service
 */
class EventsService {
	/** @var string */
	private $userId;

	/** @var ITimeFactory */
	private $time;

	/** @var IActivityManager */
	private $activityManager;

	/** @var INotificationManager */
	private $notificationManager;

	/** @var IUserManager */
	private $userManager;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var EventDispatcher */
	private $eventDispatcher;

	/** @var DeprecatedCirclesRequest */
	private $circlesRequest;

	/** @var DeprecatedMembersRequest */
	private $membersRequest;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * Events constructor.
	 *
	 * @param string $userId
	 * @param ITimeFactory $time
	 * @param IActivityManager $activityManager
	 * @param INotificationManager $notificationManager
	 * @param IUserManager $userManager
	 * @param IURLGenerator $urlGenerator
	 * @param EventDispatcher $eventDispatcher
	 * @param DeprecatedCirclesRequest $circlesRequest
	 * @param DeprecatedMembersRequest $membersRequest
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		$userId, ITimeFactory $time, IActivityManager $activityManager,
		INotificationManager $notificationManager, IUserManager $userManager, IURLGenerator $urlGenerator,
		EventDispatcher $eventDispatcher, DeprecatedCirclesRequest $circlesRequest, DeprecatedMembersRequest $membersRequest,
		ConfigService $configService, MiscService $miscService
	) {
		$this->userId = $userId;
		$this->time = $time;
		$this->activityManager = $activityManager;
		$this->notificationManager = $notificationManager;
		$this->userManager = $userManager;
		$this->urlGenerator = $urlGenerator;
		$this->eventDispatcher = $eventDispatcher;
		$this->circlesRequest = $circlesRequest;
		$this->membersRequest = $membersRequest;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * onCircleCreation()
	 *
	 * Called when a circle is created.
	 * Broadcast an activity to the cloud
	 * We won't do anything if the circle is not PUBLIC or CLOSED
	 *
	 * @param DeprecatedCircle $circle
	 */
	public function onCircleCreation(DeprecatedCircle $circle) {
		if ($circle->getType() !== DeprecatedCircle::CIRCLES_PUBLIC
			&& $circle->getType() !== DeprecatedCircle::CIRCLES_CLOSED) {
			return;
		}

		if ($this->configService->getAppValue(ConfigService::ACTIVITY_ON_NEW_CIRCLE) === '1') {
			$event = $this->generateEvent('circles_as_non_member');
			$event->setSubject('circle_create', ['circle' => json_encode($circle)]);

			$this->userManager->callForSeenUsers(
				function ($user) use ($event) {
					/** @var IUser $user */
					$this->publishEvent($event, [$user]);
				}
			);
		}

		$this->dispatch('\OCA\Circles::onCircleCreation', ['circle' => $circle]);
	}


	/**
	 * onCircleDestruction()
	 *
	 * Called when a circle is destroyed.
	 * Broadcast an activity on its members.
	 * We won't do anything if the circle is PERSONAL
	 *
	 * @param DeprecatedCircle $circle
	 */
	public function onCircleDestruction(DeprecatedCircle $circle) {
		if ($circle->getType() === DeprecatedCircle::CIRCLES_PERSONAL) {
			return;
		}

		$event = $this->generateEvent('circles_as_member');
		$event->setSubject('circle_delete', ['circle' => json_encode($circle)]);
		$this->publishEvent(
			$event,
			$this->membersRequest->forceGetMembers($circle->getUniqueId(), DeprecatedMember::LEVEL_MEMBER, 0, true)
		);

		$this->dispatch('\OCA\Circles::onCircleDestruction', ['circle' => $circle]);
	}


	/**
	 * onMemberNew()
	 *
	 * Called when a member is added to a circle.
	 * Broadcast an activity to the new member and to the moderators of the circle.
	 * We won't do anything if the circle is PERSONAL
	 * If the level is still 0, we will redirect to onMemberAlmost and manage the
	 * invitation/request from there
	 * If the level is Owner, we ignore the event.
	 *
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 */
	public function onMemberNew(DeprecatedCircle $circle, DeprecatedMember $member) {
		if ($member->getLevel() === DeprecatedMember::LEVEL_OWNER
			|| $circle->getType() === DeprecatedCircle::CIRCLES_PERSONAL
		) {
			return;
		}

		if ($member->getLevel() === DeprecatedMember::LEVEL_NONE) {
			$this->onMemberAlmost($circle, $member);

			return;
		}

		$event = $this->generateEvent('circles_as_member');
		$event->setSubject(
			($this->userId === $member->getUserId()) ? 'member_join' : 'member_add',
			['circle' => json_encode($circle), 'member' => json_encode($member)]
		);

		$this->publishEvent(
			$event, array_merge(
				[$member],
				$this->membersRequest->forceGetMembers(
					$circle->getUniqueId(), DeprecatedMember::LEVEL_MODERATOR, 0, true
				)
			)
		);

		$this->dispatch('\OCA\Circles::onMemberNew', ['circle' => $circle, 'member' => $member]);

		$this->notificationOnMemberNew($circle, $member);
	}


	/**
	 * onMemberAlmost()
	 *
	 * Called when a member is added to a circle with level=0
	 * Trigger onMemberInvitation() or onMemberInvitationRequest() based on Member Status
	 *
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 */
	private function onMemberAlmost(DeprecatedCircle $circle, DeprecatedMember $member) {
		switch ($member->getStatus()) {
			case DeprecatedMember::STATUS_INVITED:
				$this->onMemberInvited($circle, $member);

				return;

			case DeprecatedMember::STATUS_REQUEST:
				$this->onMemberRequesting($circle, $member);

				return;
		}
	}


	/**
	 * onMemberInvited()
	 *
	 * Called when a member is invited to a circle.
	 * Broadcast an activity to the invited member and to the moderators of the circle.
	 *
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 */
	private function onMemberInvited(DeprecatedCircle $circle, DeprecatedMember $member) {
		if ($circle->getType() !== DeprecatedCircle::CIRCLES_CLOSED) {
			return;
		}

		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'member_invited', ['circle' => json_encode($circle), 'member' => json_encode($member)]
		);

		$this->publishEvent(
			$event, array_merge(
				[$member],
				$this->membersRequest->forceGetMembers(
					$circle->getUniqueId(), DeprecatedMember::LEVEL_MODERATOR, 0, true
				)
			)
		);
		$this->dispatch('\OCA\Circles::onMemberInvited', ['circle' => $circle, 'member' => $member]);

		$this->notificationOnInvitation($circle, $member);
	}


	/**
	 * onMemberRequesting()
	 *
	 * Called when a member request an invitation to a private circle.
	 * Broadcast an activity to the requester and to the moderators of the circle.
	 *
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 */
	private function onMemberRequesting(DeprecatedCircle $circle, DeprecatedMember $member) {
		if ($circle->getType() !== DeprecatedCircle::CIRCLES_CLOSED) {
			return;
		}

		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'member_request_invitation',
			['circle' => json_encode($circle), 'member' => json_encode($member)]
		);

		$this->publishEvent(
			$event, array_merge(
				[$member],
				$this->membersRequest->forceGetMembers(
					$circle->getUniqueId(), DeprecatedMember::LEVEL_MODERATOR, 0, true
				)
			)
		);
		$this->dispatch('\OCA\Circles::onMemberRequesting', ['circle' => $circle, 'member' => $member]);

		$this->notificationOnRequest($circle, $member);
	}


	/**
	 * onMemberLeaving()
	 *
	 * Called when a member is removed from a circle.
	 * Broadcast an activity to the leaving member and to the moderators of the circle.
	 * We won't do anything if the circle is PERSONAL
	 *
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 */
	public function onMemberLeaving(DeprecatedCircle $circle, DeprecatedMember $member) {
		if ($circle->getType() === DeprecatedCircle::CIRCLES_PERSONAL) {
			return;
		}

		$event = $this->generateEvent('circles_as_member');
		$event->setSubject(
			($this->userId === $member->getUserId()) ? 'member_left' : 'member_remove',
			['circle' => json_encode($circle), 'member' => json_encode($member)]
		);

		$this->publishEvent(
			$event, array_merge(
				[$member],
				$this->membersRequest->forceGetMembers(
					$circle->getUniqueId(), DeprecatedMember::LEVEL_MODERATOR, 0, true
				)
			)
		);

		$this->dispatch('\OCA\Circles::onMemberLeaving', ['circle' => $circle, 'member' => $member]);

		$this->deleteNotification('membership', $member->getMemberId());
		$this->deleteNotification('membership_request', $member->getMemberId());
	}


	/**
	 * onMemberLevel()
	 *
	 * Called when a member have his level changed.
	 * Broadcast an activity to all moderator of the circle, and the member if he is demoted.
	 * If the level is Owner, we identify the event as a Coup d'Etat and we broadcast all members.
	 *
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 */
	public function onMemberLevel(DeprecatedCircle $circle, DeprecatedMember $member) {
		if ($member->getLevel() === DeprecatedMember::LEVEL_OWNER) {
			$this->onMemberOwner($circle, $member);

			return;
		}

		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'member_level',
			['circle' => json_encode($circle), 'member' => json_encode($member)]
		);

		$mods =
			$this->membersRequest->forceGetMembers($circle->getUniqueId(), DeprecatedMember::LEVEL_MODERATOR, 0, true);
		$this->membersRequest->avoidDuplicateMembers($mods, [$member]);

		$this->publishEvent($event, $mods);
		$this->dispatch('\OCA\Circles::onMemberLevel', ['circle' => $circle, 'member' => $member]);
	}


	/**
	 * onMemberOwner()
	 *
	 * Called when the owner rights of a circle have be given to another member.
	 *
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 */
	public function onMemberOwner(DeprecatedCircle $circle, DeprecatedMember $member) {
		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'member_owner',
			['circle' => json_encode($circle), 'member' => json_encode($member)]
		);

		$this->publishEvent(
			$event,
			$this->membersRequest->forceGetMembers($circle->getUniqueId(), DeprecatedMember::LEVEL_MEMBER, 0, true)
		);

		$this->dispatch('\OCA\Circles::onMemberOwner', ['circle' => $circle, 'member' => $member]);
	}


	/**
	 * onGroupLink()
	 *
	 * Called when a group is linked to a circle.
	 * Broadcast an activity to the member of the linked group and to the moderators of the circle.
	 * We won't do anything if the circle is PERSONAL
	 *
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $group
	 */
	public function onGroupLink(DeprecatedCircle $circle, DeprecatedMember $group) {
		if ($circle->getType() === DeprecatedCircle::CIRCLES_PERSONAL) {
			return;
		}

		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'group_link',
			['circle' => json_encode($circle), 'group' => json_encode($group)]
		);

		$mods =
			$this->membersRequest->forceGetMembers($circle->getUniqueId(), DeprecatedMember::LEVEL_MODERATOR, 0, true);
		$this->membersRequest->avoidDuplicateMembers(
			$mods, $this->membersRequest->getGroupMemberMembers($group)
		);

		$this->publishEvent($event, $mods);
		$this->dispatch('\OCA\Circles::onGroupLink', ['circle' => $circle, 'group' => $group]);
	}


	/**
	 * onGroupUnlink()
	 *
	 * Called when a group is unlinked from a circle.
	 * Broadcast an activity to the member of the unlinked group and to the moderators of the
	 * circle. We won't do anything if the circle is PERSONAL
	 *
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $group
	 */
	public function onGroupUnlink(DeprecatedCircle $circle, DeprecatedMember $group) {
		if ($circle->getType() === DeprecatedCircle::CIRCLES_PERSONAL) {
			return;
		}

		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'group_unlink',
			['circle' => json_encode($circle), 'group' => json_encode($group)]
		);

		$mods =
			$this->membersRequest->forceGetMembers($circle->getUniqueId(), DeprecatedMember::LEVEL_MODERATOR, 0, true);
		$this->membersRequest->avoidDuplicateMembers(
			$mods, $this->membersRequest->getGroupMemberMembers($group)
		);

		$this->publishEvent($event, $mods);
		$this->dispatch('\OCA\Circles::onGroupUnlink', ['circle' => $circle, 'group' => $group]);
	}


	/**
	 * onGroupLevel()
	 *
	 * Called when a linked group have his level changed.
	 * Broadcast an activity to all moderator of the circle, and the group members in case of
	 * demotion.
	 *
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $group
	 */
	public function onGroupLevel(DeprecatedCircle $circle, DeprecatedMember $group) {
		if ($circle->getType() === DeprecatedCircle::CIRCLES_PERSONAL) {
			return;
		}

		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'group_level',
			['circle' => json_encode($circle), 'group' => json_encode($group)]
		);

		$mods =
			$this->membersRequest->forceGetMembers($circle->getUniqueId(), DeprecatedMember::LEVEL_MODERATOR, 0, true);
		$this->membersRequest->avoidDuplicateMembers(
			$mods, $this->membersRequest->getGroupMemberMembers($group)
		);

		$this->publishEvent($event, $mods);
		$this->dispatch('\OCA\Circles::onGroupLevel', ['circle' => $circle, 'group' => $group]);
	}


	/**
	 * onLinkRequestSent()
	 *
	 * Called when a request to generate a link with a remote circle is sent.
	 * Broadcast an activity to the moderators of the circle.
	 *
	 * @param DeprecatedCircle $circle
	 * @param FederatedLink $link
	 */
	public function onLinkRequestSent(DeprecatedCircle $circle, FederatedLink $link) {
		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'link_request_sent',
			['circle' => $circle->getJson(false, true), 'link' => $link->getJson()]
		);

		$this->publishEvent(
			$event,
			$this->membersRequest->forceGetMembers($link->getCircleId(), DeprecatedMember::LEVEL_MODERATOR, 0, true)
		);
		$this->dispatch('\OCA\Circles::onLinkRequestSent', ['circle' => $circle, 'link' => $link]);
	}


	/**
	 * onLinkRequestReceived()
	 *
	 * Called when a request to generate a link from a remote host is received.
	 * Broadcast an activity to the moderators of the circle.
	 *
	 * @param DeprecatedCircle $circle
	 * @param FederatedLink $link
	 */
	public function onLinkRequestReceived(DeprecatedCircle $circle, FederatedLink $link) {
		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'link_request_received',
			['circle' => $circle->getJson(false, true), 'link' => $link->getJson()]
		);

		$this->publishEvent(
			$event,
			$this->membersRequest->forceGetMembers($link->getCircleId(), DeprecatedMember::LEVEL_MODERATOR, 0, true)
		);
		$this->dispatch('\OCA\Circles::onLinkRequestReceived', ['circle' => $circle, 'link' => $link]);
	}


	/**
	 * onLinkRequestRejected()
	 *
	 * Called when a request to generate a link from a remote host is dismissed.
	 * Broadcast an activity to the moderators of the circle.
	 *
	 * @param DeprecatedCircle $circle
	 * @param FederatedLink $link
	 */
	public function onLinkRequestRejected(DeprecatedCircle $circle, FederatedLink $link) {
		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'link_request_rejected',
			['circle' => $circle->getJson(false, true), 'link' => $link->getJson()]
		);

		$this->publishEvent(
			$event,
			$this->membersRequest->forceGetMembers($link->getCircleId(), DeprecatedMember::LEVEL_MODERATOR, 0, true)
		);
		$this->dispatch('\OCA\Circles::onLinkRequestRejected', ['circle' => $circle, 'link' => $link]);
	}


	/**
	 * onLinkRequestCanceled()
	 *
	 * Called when a request to generate a link from a remote host is dismissed.
	 * Broadcast an activity to the moderators of the circle.
	 *
	 * @param DeprecatedCircle $circle
	 * @param FederatedLink $link
	 */
	public function onLinkRequestCanceled(DeprecatedCircle $circle, FederatedLink $link) {
		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'link_request_canceled',
			['circle' => $circle->getJson(false, true), 'link' => $link->getJson()]
		);

		$this->publishEvent(
			$event,
			$this->membersRequest->forceGetMembers($link->getCircleId(), DeprecatedMember::LEVEL_MODERATOR, 0, true)
		);
		$this->dispatch('\OCA\Circles::onLinkRequestCanceled', ['circle' => $circle, 'link' => $link]);
	}


	/**
	 * onLinkRequestAccepted()
	 *
	 * Called when a request to generate a link from a remote host is accepted.
	 * Broadcast an activity to the moderators of the circle.
	 *
	 * @param DeprecatedCircle $circle
	 * @param FederatedLink $link
	 */
	public function onLinkRequestAccepted(DeprecatedCircle $circle, FederatedLink $link) {
		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'link_request_accepted',
			['circle' => $circle->getJson(false, true), 'link' => $link->getJson()]
		);

		$this->publishEvent(
			$event,
			$this->membersRequest->forceGetMembers($link->getCircleId(), DeprecatedMember::LEVEL_MODERATOR, 0, true)
		);
		$this->dispatch('\OCA\Circles::onLinkRequestAccepted', ['circle' => $circle, 'link' => $link]);
	}


	/**
	 * onLinkRequestAccepting()
	 *
	 * Called when a link is Up and Running.
	 * Broadcast an activity to the moderators of the circle.
	 *
	 * @param DeprecatedCircle $circle
	 * @param FederatedLink $link
	 */
	public function onLinkRequestAccepting(DeprecatedCircle $circle, FederatedLink $link) {
		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'link_request_accepting',
			['circle' => $circle->getJson(false, true), 'link' => $link->getJson()]
		);

		$this->publishEvent(
			$event,
			$this->membersRequest->forceGetMembers($link->getCircleId(), DeprecatedMember::LEVEL_MODERATOR, 0, true)
		);
		$this->dispatch('\OCA\Circles::onLinkRequestAccepting', ['circle' => $circle, 'link' => $link]);
	}


	/**
	 * onLinkUp()
	 *
	 * Called when a link is Up and Running.
	 * Broadcast an activity to the moderators of the circle.
	 *
	 * @param DeprecatedCircle $circle
	 * @param FederatedLink $link
	 */
	public function onLinkUp(DeprecatedCircle $circle, FederatedLink $link) {
		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'link_up',
			['circle' => $circle->getJson(false, true), 'link' => $link->getJson()]
		);

		$this->publishEvent(
			$event,
			$this->membersRequest->forceGetMembers($link->getCircleId(), DeprecatedMember::LEVEL_MODERATOR, 0, true)
		);
		$this->dispatch('\OCA\Circles::onLinkUp', ['circle' => $circle, 'link' => $link]);
	}


	/**
	 * onLinkDown()
	 *
	 * Called when a link is closed (usually by remote).
	 * Broadcast an activity to the moderators of the circle.
	 *
	 * @param DeprecatedCircle $circle
	 * @param FederatedLink $link
	 */
	public function onLinkDown(DeprecatedCircle $circle, FederatedLink $link) {
		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'link_down',
			['circle' => $circle->getJson(false, true), 'link' => $link->getJson()]
		);

		$this->publishEvent(
			$event,
			$this->membersRequest->forceGetMembers($link->getCircleId(), DeprecatedMember::LEVEL_MODERATOR, 0, true)
		);
		$this->dispatch('\OCA\Circles::onLinkDown', ['circle' => $circle, 'link' => $link]);
	}


	/**
	 * onLinkRemove()
	 *
	 * Called when a link is removed.
	 * Subject is based on the current status of the Link.
	 * Broadcast an activity to the moderators of the circle.
	 *
	 * @param DeprecatedCircle $circle
	 * @param FederatedLink $link
	 */
	public function onLinkRemove(DeprecatedCircle $circle, FederatedLink $link) {
		$event = $this->generateEvent('circles_as_moderator');

		if ($link->getStatus() === FederatedLink::STATUS_LINK_DOWN) {
			return;
		}

		$subject = 'link_remove';
		if ($link->getStatus() === FederatedLink::STATUS_LINK_REQUESTED) {
			$subject = 'link_request_removed';
		} elseif ($link->getStatus() === FederatedLink::STATUS_REQUEST_SENT) {
			$subject = 'link_request_canceling';
		}

		$event->setSubject(
			$subject, ['circle' => $circle->getJson(false, true), 'link' => $link->getJson()]
		);

		$this->publishEvent(
			$event,
			$this->membersRequest->forceGetMembers($link->getCircleId(), DeprecatedMember::LEVEL_MODERATOR, 0, true)
		);
		$this->dispatch('\OCA\Circles::onLinkRemove', ['circle' => $circle, 'link' => $link]);
	}

	/**
	 * onSettingsChange()
	 *
	 * Called when the circle's settings are changed
	 *
	 * @param DeprecatedCircle $circle
	 * @param array $oldSettings
	 */
	public function onSettingsChange(DeprecatedCircle $circle, array $oldSettings = []) {
		$this->dispatch(
			'\OCA\Circles::onSettingsChange', ['circle' => $circle, 'oldSettings' => $oldSettings]
		);
	}


	/**
	 * generateEvent()
	 * Create an Activity Event with the basic settings for the app.
	 *
	 * @param $type
	 *
	 * @return \OCP\Activity\IEvent
	 */
	private function generateEvent($type) {
		$event = $this->activityManager->generateEvent();
		$event->setApp(Application::APP_ID)
			  ->setType($type);

		//	if ($this->userId === null) {
		//	$event->setAuthor($this->userId);
		//	}

		return $event;
	}


	/**
	 * Publish the event to the users.
	 *
	 * @param IEvent $event
	 * @param array $users
	 */
	private function publishEvent(IEvent $event, array $users) {
		foreach ($users as $user) {
			if ($user instanceof IUser) {
				$userId = $user->getUID();
			} elseif ($user instanceof DeprecatedMember) {
				$userId = $user->getUserId();
			} else {
				continue;
			}

			$event->setAffectedUser($userId);
			$this->activityManager->publish($event);
		}
	}


	/**
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 */
	private function notificationOnInvitation(DeprecatedCircle $circle, DeprecatedMember $member) {
		$this->deleteNotification('membership_request', $member->getMemberId());
		if ($member->getType() !== DeprecatedMember::TYPE_USER) {
			return;
		}

		$notification = $this->createNotification(
			$circle, $circle->getViewer(), $member->getUserId(), 'invitation', 'membership',
			$member->getMemberId()
		);

		$declineAction = $notification->createAction();
		$declineUrl =
			$this->urlGenerator->linkToRoute('circles.Circles.leave', ['uniqueId' => $circle->getUniqueId()]);

		$declineAction->setLabel('refuse')
					  ->setLink($this->urlGenerator->getAbsoluteURL($declineUrl), 'GET');
		$notification->addAction($declineAction);

		$acceptAction = $notification->createAction();
		$acceptUrl =
			$this->urlGenerator->linkToRoute('circles.Circles.join', ['uniqueId' => $circle->getUniqueId()]);

		$acceptAction->setLabel('accept')
					 ->setLink($this->urlGenerator->getAbsoluteURL($acceptUrl), 'GET');
		$notification->addAction($acceptAction);

		$this->notificationManager->notify($notification);
	}

	/**
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $author
	 */
	private function notificationOnRequest(DeprecatedCircle $circle, DeprecatedMember $author) {
		$members = $this->membersRequest->forceGetMembers($circle->getUniqueId(), DeprecatedMember::LEVEL_MODERATOR);
		foreach ($members as $member) {
			$notification = $this->createNotification(
				$circle, $author, $member->getUserId(), 'request_new', 'membership_request',
				$author->getMemberId()
			);

			$declineAction = $notification->createAction();
			$declineUrl = $this->urlGenerator->linkToRoute(
				'circles.Members.removeMemberById', ['memberId' => $author->getMemberId()]
			);

			$declineAction->setLabel('refuse')
						  ->setLink($this->urlGenerator->getAbsoluteURL($declineUrl), 'DELETE');
			$notification->addAction($declineAction);

			$acceptAction = $notification->createAction();
			$acceptUrl = $this->urlGenerator->linkToRoute(
				'circles.Members.addMemberById', ['memberId' => $author->getMemberId()]
			);
			$acceptAction->setLabel('accept')
						 ->setLink($this->urlGenerator->getAbsoluteURL($acceptUrl), 'PUT');
			$notification->addAction($acceptAction);

			$this->notificationManager->notify($notification);
		}
	}


	/**
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 */
	private function notificationOnMemberNew(DeprecatedCircle $circle, DeprecatedMember $member) {
		$this->deleteNotification('membership_request', $member->getMemberId());
		$this->deleteNotification('membership', $member->getMemberId());
		if ($this->userId === $member->getUserId()) {
			return;
		}

		$notification =
			$this->createNotification(
				$circle, $circle->getViewer(), $member->getUserId(), 'member_new', 'membership',
				$member->getMemberId()
			);


		$leave = $notification->createAction();
		$leaveUrl =
			$this->urlGenerator->linkToRoute('circles.Circles.leave', ['uniqueId' => $circle->getUniqueId()]);
		$leave->setLabel('leave')
			  ->setLink($this->urlGenerator->getAbsoluteURL($leaveUrl), 'GET');

		$notification->addAction($leave);

		$this->notificationManager->notify($notification);
	}


	/**
	 * @param string $object
	 * @param string $objectId
	 */
	public function deleteNotification(string $object, string $objectId) {
		if ($objectId === '') {
			return;
		}

		$notification = $this->notificationManager->createNotification();
		$notification->setApp('circles')
					 ->setObject($object, $objectId);

		$this->notificationManager->markProcessed($notification);
	}


	/**
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $author
	 * @param string $userId
	 * @param string $subject
	 * @param string $object
	 * @param string $objectId
	 *
	 * @return INotification
	 */
	private function createNotification(
		DeprecatedCircle $circle, DeprecatedMember $author, string $userId, string $subject, string $object, string $objectId
	) {
		$authorName = $author->getCachedName();
		if ($authorName === '') {
			$authorName = $author->getUserId();
		}

		$now = $this->time->getDateTime();
		$notification = $this->notificationManager->createNotification();
		$notification->setApp('circles')
					 ->setDateTime($now)
					 ->setUser($userId)
					 ->setObject($object, $objectId)
					 ->setSubject(
					 	$subject, [$authorName, $circle->getName(), json_encode($circle)]
					 );

		return $notification;
	}


	/**
	 * @param string $context
	 * @param array $arguments
	 */
	private function dispatch(string $context, $arguments) {
		$this->eventDispatcher->dispatch($context, new GenericEvent(null, $arguments));
	}
}
