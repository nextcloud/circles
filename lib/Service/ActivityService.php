<?php

declare(strict_types=1);

/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2023
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
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Events\CircleGenericEvent;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Member;
use OCP\Activity\IEvent;
use OCP\Activity\IManager as IActivityManager;
use OCP\IUser;
use OCP\IUserManager;
use UnhandledMatchError;

class ActivityService {
	public function __construct(
		private IActivityManager $activityManager,
		private IUserManager $userManager,
		private MemberRequest $memberRequest,
		private ConfigService $configService
	) {
	}

	/**
	 * @param Circle $circle
	 */
	public function onCircleCreation(Circle $circle): void {
		if ($circle->isConfig(Circle::CFG_PERSONAL)
			|| !$this->configService->getAppValueBool(ConfigService::ACTIVITY_ON_NEW_CIRCLE)) {
			return;
		}

		$event = $this->generateEvent('circles_as_non_member');
		$event->setSubject('circle_create', ['circle' => json_encode($circle)]);

		$this->userManager->callForSeenUsers(
			function ($user) use ($event) {
				/** @var IUser $user */
				$this->publishEvent($event, [$user]);
			}
		);
	}

	/**
	 * @param Circle $circle
	 */
	public function onCircleDestruction(Circle $circle): void {
		if ($circle->isConfig(Circle::CFG_PERSONAL)) {
			return;
		}

		$event = $this->generateEvent('circles_as_member');
		$event->setSubject('circle_delete', ['circle' => json_encode($circle)]);
		$this->publishEvent(
			$event,
			$this->memberRequest->getInheritedMembers($circle->getSingleId(), false, Member::LEVEL_MEMBER)
		);
	}

	/**
	 * @param Circle $circle
	 * @param Member $member
	 * @param int $eventType
	 */
	public function onMemberNew(
		Circle $circle,
		Member $member,
		int $eventType
	): void {
		if ($circle->isConfig(Circle::CFG_PERSONAL)) {
			return;
		}

		if ($member->getLevel() === Member::LEVEL_NONE) {
			$this->onMemberAlmost($circle, $member, $eventType);
			return;
		}

		switch ($member->getUserType()) {
			case Member::TYPE_USER:
			case Member::TYPE_MAIL:
			case Member::TYPE_CONTACT:
				$this->onMemberNewAccount($circle, $member, $eventType);
				break;

			case Member::TYPE_CIRCLE:
				$this->onMemberNewCircle(
					$circle,
					$member,
					$eventType
				);
				break;
		}
	}

	/**
	 * @param Circle $circle
	 * @param Member $member
	 * @param int $eventType
	 */
	private function onMemberNewAccount(
		Circle $circle,
		Member $member,
		int $eventType
	): void {
		$event = $this->generateEvent('circles_as_member');

		try {
			$event->setSubject(
				match ($eventType) {
					CircleGenericEvent::ADDED => 'member_added',
					CircleGenericEvent::JOINED => 'member_join'
				},
				[
					'circle' => json_encode($circle),
					'member' => json_encode($member)
				]
			);
		} catch (UnhandledMatchError $e) {
			return;
		}

		$this->publishEvent(
			$event, array_merge(
				[$member],
				$this->memberRequest->getInheritedMembers(
					$circle->getSingleId(),
					false,
					Member::LEVEL_MODERATOR
				)
			)
		);
	}

	/**
	 * @param Circle $circle
	 * @param Member $member
	 * @param int $eventType
	 */
	private function onMemberNewCircle(
		Circle $circle,
		Member $member,
		int $eventType = CircleGenericEvent::JOINED
	): void {
		$event = $this->generateEvent('circles_as_member');

		try {
			$event->setSubject(
				match ($eventType) {
					CircleGenericEvent::ADDED => 'member_circle_added',
					CircleGenericEvent::JOINED => 'member_circle_joined'
				},
				[
					'circle' => json_encode($circle),
					'member' => json_encode($member)
				]
			);
		} catch (UnhandledMatchError $e) {
			return;
		}

		$this->publishEvent(
			$event, array_merge(
				$this->memberRequest->getInheritedMembers($member->getSingleId(), false, Member::LEVEL_MEMBER),
				$this->memberRequest->getInheritedMembers($circle->getSingleId(), false, Member::LEVEL_MODERATOR)
			)
		);
	}

	/**
	 * @param Circle $circle
	 * @param Member $member
	 * @param int $eventType
	 */
	private function onMemberAlmost(
		Circle $circle,
		Member $member,
		int $eventType
	): void {
		if ($member->getUserType() !== Member::TYPE_USER) {
			return; // only if almost-member is a local account
		}

		$event = $this->generateEvent('circles_as_moderator');

		try {
			$event->setSubject(
				match ($eventType) {
					CircleGenericEvent::INVITED => 'member_invited',
					CircleGenericEvent::REQUESTED => 'member_request_invitation'
				},
				[
					'circle' => json_encode($circle),
					'member' => json_encode($member)
				]
			);
		} catch (UnhandledMatchError $e) {
			return;
		}

		$this->publishEvent(
			$event,
			array_merge(
				[$member],
				$this->memberRequest->getInheritedMembers($circle->getSingleId(), false, Member::LEVEL_MODERATOR)
			)
		);
	}

	/**
	 * @param Circle $circle
	 * @param Member $member
	 * @param int $eventType
	 */
	public function onMemberRemove(Circle $circle, Member $member, int $eventType): void {
		if ($circle->isConfig(Circle::CFG_PERSONAL)) {
			return;
		}

		$event = $this->generateEvent('circles_as_member');
		try {
			$event->setSubject(
				match ($eventType) {
					CircleGenericEvent::LEFT => 'member_left',
					CircleGenericEvent::REMOVED => 'member_remove'
				},
				[
					'circle' => json_encode($circle),
					'member' => json_encode($member)
				]
			);
		} catch (UnhandledMatchError $e) {
			return;
		}

		$this->publishEvent(
			$event, array_merge(
				[$member],
				$this->memberRequest->getInheritedMembers($circle->getSingleId(), false, Member::LEVEL_MODERATOR)
			)
		);
	}

	/**
	 * @param Circle $circle
	 * @param Member $member
	 * @param int $level
	 */
	public function onMemberLevel(
		Circle $circle,
		Member $member,
		int $level
	): void {
		if ($member->getLevel() === Member::LEVEL_OWNER) {
			$this->onMemberOwner($circle, $member);

			return;
		}

		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'member_level',
			['circle' => json_encode($circle), 'member' => json_encode($member), 'level' => $level]
		);

		$this->publishEvent(
			$event, array_merge(
				[$member],
				$this->memberRequest->getInheritedMembers($circle->getSingleId(), false, Member::LEVEL_MODERATOR))
		);
	}


	/**
	 * @param Circle $circle
	 * @param Member $member
	 */
	public function onMemberOwner(Circle $circle, Member $member): void {
		$event = $this->generateEvent('circles_as_moderator');
		$event->setSubject(
			'member_owner',
			['circle' => json_encode($circle), 'member' => json_encode($member)]
		);

		$this->publishEvent(
			$event,
			$this->memberRequest->getInheritedMembers($circle->getSingleId(), false, Member::LEVEL_MEMBER)
		);
	}


	public function onShareNew(Circle $getCircle, FederatedEvent $federatedEvent): void {
	}


	/**
	 * generateEvent()
	 * Create an Activity Event with the basic settings for the app.
	 *
	 * @param string $type
	 *
	 * @return IEvent
	 */
	private function generateEvent(string $type): IEvent {
		$event = $this->activityManager->generateEvent();
		$event->setApp(Application::APP_ID)
			->setType($type);

		return $event;
	}


	/**
	 * Publish the event to the users.
	 * - if user is IUser, we get userId,
	 * - if user is Member, we ignore non-local account and returns local userId,
	 * - others models are ignored
	 * - avoid duplicate activity in case of inheritance as an account can be inherited memberships throw different path
	 *
	 * @param IEvent $event
	 * @param array<IUser|IFederatedUser> $users
	 */
	private function publishEvent(IEvent $event, array $users): void {
		$knownSingleIds = [];
		foreach ($users as $user) {
			if ($user instanceof IUser) {
				$userId = $user->getUID();
			} elseif ($user instanceof IFederatedUser) {
				$singleId = $user->getSingleId();
				if ($user->getUserType() !== Member::TYPE_USER ||
					in_array($singleId, $knownSingleIds)) {
					continue; // we ignore non-local account and already known single ids
				}

				$knownSingleIds[] = $singleId;
				$userId = $user->getUserId();
			} else {
				continue;
			}

			$event->setAffectedUser($userId);
			$this->activityManager->publish($event);
		}
	}
}
