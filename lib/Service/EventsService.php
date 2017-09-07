<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
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

use OCA\Circles\Db\CirclesRequest;
use OCA\Circles\Db\MembersRequest;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedLink;
use OCA\Circles\Model\Member;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\IUser;
use OCP\IUserManager;

class EventsService {


	/** @var string */
	private $userId;

	/** @var IManager */
	private $activityManager;

	/** @var IUserManager */
	private $userManager;

	/** @var CirclesRequest */
	private $circlesRequest;

	/** @var MembersRequest */
	private $membersRequest;

	/** @var MiscService */
	private $miscService;


	/**
	 * Events constructor.
	 *
	 * @param string $userId
	 * @param IManager $activityManager
	 * @param IUserManager $userManager
	 * @param CirclesRequest $circlesRequest
	 * @param MembersRequest $membersRequest
	 * @param MiscService $miscService
	 */
	public function __construct(
		$userId, IManager $activityManager, IUserManager $userManager,
		CirclesRequest $circlesRequest, MembersRequest $membersRequest, MiscService $miscService
	) {
		$this->userId = $userId;
		$this->activityManager = $activityManager;
		$this->userManager = $userManager;
		$this->circlesRequest = $circlesRequest;
		$this->membersRequest = $membersRequest;
		$this->miscService = $miscService;
	}


	/**
	 * onCircleCreation()
	 *
	 * Called when a circle is created.
	 * Broadcast an activity to the cloud
	 * We won't do anything if the circle is not PUBLIC or CLOSED
	 *
	 * @param Circle $circle
	 */
	public function onCircleCreation(Circle $circle) {
		if ($circle->getType() !== Circle::CIRCLES_PUBLIC
			&& $circle->getType() !== Circle::CIRCLES_CLOSED
		) {
			return;
		}

		$event = $this->generateEvent('circles_as_member');
		$event->setSubject('circle_create', ['circle' => json_encode($circle)]);

		$this->userManager->callForSeenUsers(
			function($user) use ($event) {
				/** @var IUser $user */
				$this->publishEvent($event, [$user]);
			}
		);
		\OC_Hook::emit('\OCA\Circles', 'onCircleCreation', [$circle]);
	}


	/**
	 * onCircleDestruction()
	 *
	 * Called when a circle is destroyed.
	 * Broadcast an activity on its members.
	 * We won't do anything if the circle is PERSONAL
	 *
	 * @param Circle $circle
	 */
	public function onCircleDestruction(Circle $circle) {
		if ($circle->getType() === Circle::CIRCLES_PERSONAL) {
			return;
		}

		$event = $this->generateEvent('circles_as_member');
		$event->setSubject('circle_delete', ['circle' => json_encode($circle)]);
		$this->publishEvent(
			$event,
			$this->membersRequest->forceGetMembers(
				$circle->getUniqueId(), Member::LEVEL_MEMBER, true
			)
		);
		\OC_Hook::emit('\OCA\Circles', 'onCircleDestruction', [$circle]);
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
	 * @param Circle $circle
	 * @param Member $member
	 */
	public function onMemberNew(Circle $circle, Member $member) {
		if ($member->getLevel() === Member::LEVEL_OWNER
			|| $circle->getType() === Circle::CIRCLES_PERSONAL
		) {
			return;
		}

		if ($member->getLevel() === Member::LEVEL_NONE) {
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
						  $circle->getUniqueId(), Member::LEVEL_MODERATOR, true
					  )
				  )
		);
		\OC_Hook::emit('\OCA\Circles', 'onMemberNew', [$circle, $member]);
	}


	/**
	 * onMemberAlmost()
	 *
	 * Called when a member is added to a circle with level=0
	 * Trigger onMemberInvitation() or onMemberInvitationRequest() based on Member Status
	 *
	 * @param Circle $circle
	 * @param Member $member
	 */
	private function onMemberAlmost(Circle $circle, Member $member) {

		switch ($member->getStatus()) {
			case Member::STATUS_INVITED:
				$this->onMemberInvited($circle, $member);

				return;

			case Member::STATUS_REQUEST:
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
	 * @param Circle $circle
	 * @param Member $member
	 */
	private function onMemberInvited(Circle $circle, Member $member) {
		if ($circle->getType() !== Circle::CIRCLES_CLOSED) {
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
						  $circle->getUniqueId(), Member::LEVEL_MODERATOR, true
					  )
				  )
		);
		\OC_Hook::emit('\OCA\Circles', 'onMemberInvited', [$circle, $member]);
	}


	/**
	 * onMemberRequesting()
	 *
	 * Called when a member request an invitation to a private circle.
	 * Broadcast an activity to the requester and to the moderators of the circle.
	 *
	 * @param Circle $circle
	 * @param Member $member
	 */
	private function onMemberRequesting(Circle $circle, Member $member) {
		if ($circle->getType() !== Circle::CIRCLES_CLOSED) {
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
						  $circle->getUniqueId(), Member::LEVEL_MODERATOR, true
					  )
				  )
		);
		\OC_Hook::emit('\OCA\Circles', 'onMemberRequesting', [$circle, $member]);
	}


	/**
	 * onMemberLeaving()
	 *
	 * Called when a member is removed from a circle.
	 * Broadcast an activity to the leaving member and to the moderators of the circle.
	 * We won't do anything if the circle is PERSONAL
	 *
	 * @param Circle $circle
	 * @param Member $member
	 */
	public function onMemberLeaving(Circle $circle, Member $member) {
		if ($circle->getType() === Circle::CIRCLES_PERSONAL) {
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
						  $circle->getUniqueId(), Member::LEVEL_MODERATOR, true
					  )
				  )
		);
		\OC_Hook::emit('\OCA\Circles', 'onMemberLeaving', [$circle, $member]);
	}


	/**
	 * onMemberLevel()
	 *
	 * Called when a member have his level changed.
	 * Broadcast an activity to all moderator of the circle, and the member if he is demoted.
	 * If the level is Owner, we identify the event as a Coup d'Etat and we broadcast all members.
	 *
	 * @param Circle $circle
	 * @param Member $member
	 */
	public function onMemberLevel(Circle $circle, Member $member) {
		if ($member->getLevel() === Member::LEVEL_OWNER) {
			$this->onMemberOwner($circle, $member);

			return;
		}

		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'member_level',
			['circle' => json_encode($circle), 'member' => json_encode($member)]
		);

		$mods = $this->membersRequest->forceGetMembers(
			$circle->getUniqueId(), Member::LEVEL_MODERATOR, true
		);
		$this->membersRequest->avoidDuplicateMembers($mods, [$member]);

		$this->publishEvent($event, $mods);
		\OC_Hook::emit('\OCA\Circles', 'onMemberLevel', [$circle, $member]);
	}


	/**
	 * onMemberOwner()
	 *
	 * Called when the owner rights of a circle have be given to another member.
	 *
	 * @param Circle $circle
	 * @param Member $member
	 */
	private function onMemberOwner(Circle $circle, Member $member) {
		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'member_owner',
			['circle' => json_encode($circle), 'member' => json_encode($member)]
		);

		$this->publishEvent(
			$event,
			$this->membersRequest->forceGetMembers(
				$circle->getUniqueId(), Member::LEVEL_MEMBER, true
			)
		);
		\OC_Hook::emit('\OCA\Circles', 'onMemberOwner', [$circle, $member]);
	}


	/**
	 * onGroupLink()
	 *
	 * Called when a group is linked to a circle.
	 * Broadcast an activity to the member of the linked group and to the moderators of the circle.
	 * We won't do anything if the circle is PERSONAL
	 *
	 * @param Circle $circle
	 * @param Member $group
	 */
	public function onGroupLink(Circle $circle, Member $group) {
		if ($circle->getType() === Circle::CIRCLES_PERSONAL) {
			return;
		}

		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'group_link',
			['circle' => json_encode($circle), 'group' => json_encode($group)]
		);

		$mods = $this->membersRequest->forceGetMembers(
			$circle->getUniqueId(), Member::LEVEL_MODERATOR, true
		);
		$this->membersRequest->avoidDuplicateMembers(
			$mods, $this->membersRequest->getGroupMemberMembers($group)
		);

		$this->publishEvent($event, $mods);
		\OC_Hook::emit('\OCA\Circles', 'onGroupLink', [$circle, $group]);
	}


	/**
	 * onGroupUnlink()
	 *
	 * Called when a group is unlinked from a circle.
	 * Broadcast an activity to the member of the unlinked group and to the moderators of the
	 * circle. We won't do anything if the circle is PERSONAL
	 *
	 * @param Circle $circle
	 * @param Member $group
	 */
	public function onGroupUnlink(Circle $circle, Member $group) {
		if ($circle->getType() === Circle::CIRCLES_PERSONAL) {
			return;
		}

		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'group_unlink',
			['circle' => json_encode($circle), 'group' => json_encode($group)]
		);

		$mods = $this->membersRequest->forceGetMembers(
			$circle->getUniqueId(), Member::LEVEL_MODERATOR, true
		);
		$this->membersRequest->avoidDuplicateMembers(
			$mods, $this->membersRequest->getGroupMemberMembers($group)
		);

		$this->publishEvent($event, $mods);
		\OC_Hook::emit('\OCA\Circles', 'onGroupUnlink', [$circle, $group]);
	}


	/**
	 * onGroupLevel()
	 *
	 * Called when a linked group have his level changed.
	 * Broadcast an activity to all moderator of the circle, and the group members in case of
	 * demotion.
	 *
	 * @param Circle $circle
	 * @param Member $group
	 */
	public function onGroupLevel(Circle $circle, Member $group) {
		if ($circle->getType() === Circle::CIRCLES_PERSONAL) {
			return;
		}

		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'group_level',
			['circle' => json_encode($circle), 'group' => json_encode($group)]
		);

		$mods = $this->membersRequest->forceGetMembers(
			$circle->getUniqueId(), Member::LEVEL_MODERATOR, true
		);
		$this->membersRequest->avoidDuplicateMembers(
			$mods, $this->membersRequest->getGroupMemberMembers($group)
		);

		$this->publishEvent($event, $mods);
		\OC_Hook::emit('\OCA\Circles', 'onGroupLevel', [$circle, $group]);
	}


	/**
	 * onLinkRequestSent()
	 *
	 * Called when a request to generate a link with a remote circle is sent.
	 * Broadcast an activity to the moderators of the circle.
	 *
	 * @param Circle $circle
	 * @param FederatedLink $link
	 */
	public function onLinkRequestSent(Circle $circle, FederatedLink $link) {
		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'link_request_sent',
			['circle' => $circle->getJson(false, true), 'link' => $link->getJson()]
		);

		$this->publishEvent(
			$event, $this->membersRequest->forceGetMembers(
			$link->getCircleId(), Member::LEVEL_MODERATOR, true
		)
		);
		\OC_Hook::emit('\OCA\Circles', 'onLinkRequestSent', [$circle, $link]);
	}


	/**
	 * onLinkRequestReceived()
	 *
	 * Called when a request to generate a link from a remote host is received.
	 * Broadcast an activity to the moderators of the circle.
	 *
	 * @param Circle $circle
	 * @param FederatedLink $link
	 */
	public function onLinkRequestReceived(Circle $circle, FederatedLink $link) {
		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'link_request_received',
			['circle' => $circle->getJson(false, true), 'link' => $link->getJson()]
		);

		$this->publishEvent(
			$event, $this->membersRequest->forceGetMembers(
			$link->getCircleId(), Member::LEVEL_MODERATOR, true
		)
		);
		\OC_Hook::emit('\OCA\Circles', 'onLinkRequestReceived', [$circle, $link]);
	}


	/**
	 * onLinkRequestRejected()
	 *
	 * Called when a request to generate a link from a remote host is dismissed.
	 * Broadcast an activity to the moderators of the circle.
	 *
	 * @param Circle $circle
	 * @param FederatedLink $link
	 */
	public function onLinkRequestRejected(Circle $circle, FederatedLink $link) {
		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'link_request_rejected',
			['circle' => $circle->getJson(false, true), 'link' => $link->getJson()]
		);

		$this->publishEvent(
			$event, $this->membersRequest->forceGetMembers(
			$link->getCircleId(), Member::LEVEL_MODERATOR, true
		)
		);
		\OC_Hook::emit('\OCA\Circles', 'onLinkRequestRejected', [$circle, $link]);
	}


	/**
	 * onLinkRequestCanceled()
	 *
	 * Called when a request to generate a link from a remote host is dismissed.
	 * Broadcast an activity to the moderators of the circle.
	 *
	 * @param Circle $circle
	 * @param FederatedLink $link
	 */
	public function onLinkRequestCanceled(Circle $circle, FederatedLink $link) {
		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'link_request_canceled',
			['circle' => $circle->getJson(false, true), 'link' => $link->getJson()]
		);

		$this->publishEvent(
			$event, $this->membersRequest->forceGetMembers(
			$link->getCircleId(), Member::LEVEL_MODERATOR, true
		)
		);
		\OC_Hook::emit('\OCA\Circles', 'onLinkRequestCanceled', [$circle, $link]);
	}


	/**
	 * onLinkRequestAccepted()
	 *
	 * Called when a request to generate a link from a remote host is accepted.
	 * Broadcast an activity to the moderators of the circle.
	 *
	 * @param Circle $circle
	 * @param FederatedLink $link
	 */
	public function onLinkRequestAccepted(Circle $circle, FederatedLink $link) {
		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'link_request_accepted',
			['circle' => $circle->getJson(false, true), 'link' => $link->getJson()]
		);

		$this->publishEvent(
			$event, $this->membersRequest->forceGetMembers(
			$link->getCircleId(), Member::LEVEL_MODERATOR, true
		)
		);
		\OC_Hook::emit('\OCA\Circles', 'onLinkRequestAccepted', [$circle, $link]);
	}


	/**
	 * onLinkRequestAccepting()
	 *
	 * Called when a link is Up and Running.
	 * Broadcast an activity to the moderators of the circle.
	 *
	 * @param Circle $circle
	 * @param FederatedLink $link
	 */
	public function onLinkRequestAccepting(Circle $circle, FederatedLink $link) {
		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'link_request_accepting',
			['circle' => $circle->getJson(false, true), 'link' => $link->getJson()]
		);

		$this->publishEvent(
			$event, $this->membersRequest->forceGetMembers(
			$link->getCircleId(), Member::LEVEL_MODERATOR, true
		)
		);
		\OC_Hook::emit('\OCA\Circles', 'onLinkRequestAccepting', [$circle, $link]);
	}


	/**
	 * onLinkUp()
	 *
	 * Called when a link is Up and Running.
	 * Broadcast an activity to the moderators of the circle.
	 *
	 * @param Circle $circle
	 * @param FederatedLink $link
	 */
	public function onLinkUp(Circle $circle, FederatedLink $link) {
		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'link_up',
			['circle' => $circle->getJson(false, true), 'link' => $link->getJson()]
		);

		$this->publishEvent(
			$event, $this->membersRequest->forceGetMembers(
			$link->getCircleId(), Member::LEVEL_MODERATOR, true
		)
		);
		\OC_Hook::emit('\OCA\Circles', 'onLinkUp', [$circle, $link]);
	}


	/**
	 * onLinkDown()
	 *
	 * Called when a link is closed (usually by remote).
	 * Broadcast an activity to the moderators of the circle.
	 *
	 * @param Circle $circle
	 * @param FederatedLink $link
	 */
	public function onLinkDown(Circle $circle, FederatedLink $link) {
		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'link_down',
			['circle' => $circle->getJson(false, true), 'link' => $link->getJson()]
		);

		$this->publishEvent(
			$event, $this->membersRequest->forceGetMembers(
			$link->getCircleId(), Member::LEVEL_MODERATOR, true
		)
		);
		\OC_Hook::emit('\OCA\Circles', 'onLinkDown', [$circle, $link]);
	}


	/**
	 * onLinkRemove()
	 *
	 * Called when a link is removed.
	 * Subject is based on the current status of the Link.
	 * Broadcast an activity to the moderators of the circle.
	 *
	 * @param Circle $circle
	 * @param FederatedLink $link
	 */
	public function onLinkRemove(Circle $circle, FederatedLink $link) {
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
			$event, $this->membersRequest->forceGetMembers(
			$link->getCircleId(), Member::LEVEL_MODERATOR, true
		)
		);
		\OC_Hook::emit('\OCA\Circles', 'onLinkRemove', [$circle, $link]);
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
		$event->setApp('circles')
			  ->setType($type)
			  ->setAuthor($this->userId);

		return $event;
	}


	/**
	 * Publish the event to the users.
	 *
	 * @param IEvent $event
	 * @param array $users
	 */
	private function publishEvent(IEvent $event, array $users) {
		foreach ($users AS $user) {
			if ($user instanceof IUser) {
				$userId = $user->getUID();
			} else if ($user instanceof Member) {
				$userId = $user->getUserId();
			} else {
				continue;
			}

			$event->setAffectedUser($userId);
			$this->activityManager->publish($event);
		}
	}


}