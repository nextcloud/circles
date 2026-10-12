<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\CircleRequest;
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
	private const SETTING_TEAM_TAB_ORDER = 'teamsTabOrder';
	private const SETTING_PASSWORD_SINGLE = 'password_single';
	private const SETTING_PASSWORD_SINGLE_ENABLED = 'password_single_enabled';
	private const ACTIVITY_CONFIGS = [
		Circle::CFG_OPEN => 'open',
		Circle::CFG_INVITE => 'invite',
		Circle::CFG_REQUEST => 'request',
		Circle::CFG_FRIEND => 'friend',
		Circle::CFG_ROOT => 'root',
		Circle::CFG_FEDERATED => 'federated',
		Circle::CFG_VISIBLE => 'visible',
	];
	private const ACTIVITY_CONFIG_SETTINGS = [
		'config_open_enabled',
		'config_open_disabled',
		'config_invite_enabled',
		'config_invite_disabled',
		'config_request_enabled',
		'config_request_disabled',
		'config_friend_enabled',
		'config_friend_disabled',
		'config_root_enabled',
		'config_root_disabled',
		'config_federated_enabled',
		'config_federated_disabled',
		'config_visible_enabled',
		'config_visible_disabled',
	];

	public function __construct(
		private readonly IActivityManager $activityManager,
		private readonly IUserManager $userManager,
		private readonly MemberRequest $memberRequest,
		private readonly CircleRequest $circleRequest,
		private readonly ConfigService $configService,
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

		$event = $this->generateEvent('circles_as_non_member', $circle);
		$event->setSubject(
			'circle_create',
			[
				'ver' => 2,
				'circle' => $this->shortenCircleData($circle),
				'initiator' => ($circle->hasInitiator() ? $this->shortenMemberData($circle->getInitiator()) : null),
			]
		);

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

		$event = $this->generateEvent('circles_as_member', $circle);
		$event->setSubject(
			'circle_delete',
			[
				'ver' => 2,
				'circle' => $this->shortenCircleData($circle),
				'initiator' => ($circle->hasInitiator() ? $this->shortenMemberData($circle->getInitiator()) : null),
			]
		);
		$this->publishEvent(
			$event,
			$this->memberRequest->getInheritedMembers($circle->getSingleId(), false, Member::LEVEL_MEMBER)
		);
	}

	public function onCircleEdited(Circle $circle, string $change): void {
		if ($circle->isConfig(Circle::CFG_PERSONAL)) {
			return;
		}

		$event = $this->generateEvent('circles_as_member', $circle);
		$event->setSubject(
			'circle_edit',
			[
				'ver' => 2,
				'circle' => $this->shortenCircleData($circle),
				'initiator' => ($circle->hasInitiator() ? $this->shortenMemberData($circle->getInitiator()) : null),
				'change' => $change,
				'title' => match ($change) {
					'name' => $circle->getName(),
					'displayName' => $circle->getDisplayName(),
					default => null,
				},
			]
		);

		$this->publishEvent(
			$event,
			$this->memberRequest->getInheritedMembers($circle->getSingleId(), false, Member::LEVEL_MEMBER)
		);
	}

	public function onCircleSetting(Circle $circle, string $setting): void {
		if ($circle->isConfig(Circle::CFG_PERSONAL)) {
			return;
		}

		$event = $this->generateEvent('circles_as_member', $circle);
		$event->setSubject(
			'circle_setting_changed',
			[
				'ver' => 2,
				'circle' => $this->shortenCircleData($circle),
				'initiator' => ($circle->hasInitiator() ? $this->shortenMemberData($circle->getInitiator()) : null),
				'setting' => $this->getActivitySetting($setting),
			]
		);

		$this->publishEvent(
			$event,
			$this->memberRequest->getInheritedMembers($circle->getSingleId(), false, Member::LEVEL_MEMBER)
		);
	}

	public function onCircleConfigChanged(Circle $circle, int $previousConfig, int $newConfig): void {
		$changedConfig = $previousConfig ^ $newConfig;
		foreach (self::ACTIVITY_CONFIGS as $config => $setting) {
			if (($changedConfig & $config) === 0) {
				continue;
			}

			$this->onCircleSetting(
				$circle,
				'config_' . $setting . (($newConfig & $config) === 0 ? '_disabled' : '_enabled'),
			);
		}
	}

	/**
	 * @param Circle $circle
	 * @param Member $member
	 * @param int $eventType
	 */
	public function onMemberNew(
		Circle $circle,
		Member $member,
		int $eventType,
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
		int $eventType,
	): void {
		$event = $this->generateEvent('circles_as_member', $circle);

		try {
			$event->setSubject(
				match ($eventType) {
					CircleGenericEvent::ADDED => 'member_added',
					CircleGenericEvent::JOINED => 'member_join'
				},
				[
					'ver' => 2,
					'circle' => $this->shortenCircleData($circle),
					'initiator' => ($circle->hasInitiator() ? $this->shortenMemberData($circle->getInitiator()) : null),
					'member' => $this->shortenMemberData($member),
				]
			);
		} catch (UnhandledMatchError) {
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
		int $eventType = CircleGenericEvent::JOINED,
	): void {
		$event = $this->generateEvent('circles_as_member', $circle);

		try {
			$event->setSubject(
				match ($eventType) {
					CircleGenericEvent::ADDED => 'member_circle_added',
					CircleGenericEvent::JOINED => 'member_circle_joined'
				},
				[
					'ver' => 2,
					'circle' => $this->shortenCircleData($circle),
					'initiator' => ($circle->hasInitiator() ? $this->shortenMemberData($circle->getInitiator()) : null),
					'member' => $this->shortenMemberData($member),
				]
			);
		} catch (UnhandledMatchError) {
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
		int $eventType,
	): void {
		if ($member->getUserType() !== Member::TYPE_USER) {
			return; // only if almost-member is a local account
		}

		$event = $this->generateEvent('circles_as_moderator', $circle);

		try {
			$event->setSubject(
				match ($eventType) {
					CircleGenericEvent::INVITED => 'member_invited',
					CircleGenericEvent::REQUESTED => 'member_request_invitation'
				},
				[
					'ver' => 2,
					'circle' => $this->shortenCircleData($circle),
					'initiator' => ($circle->hasInitiator() ? $this->shortenMemberData($circle->getInitiator()) : null),
					'member' => $this->shortenMemberData($member),
				]
			);
		} catch (UnhandledMatchError) {
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

		switch ($member->getUserType()) {
			case Member::TYPE_USER:
			case Member::TYPE_MAIL:
			case Member::TYPE_CONTACT:
				$this->onMemberRemoveAccount($circle, $member, $eventType);
				break;

			case Member::TYPE_CIRCLE:
				$this->onMemberRemoveCircle(
					$circle,
					$member,
					$eventType
				);
				break;
		}
	}

	private function onMemberRemoveAccount(
		Circle $circle,
		Member $member,
		int $eventType,
	): void {
		$event = $this->generateEvent('circles_as_member', $circle);

		try {
			$event->setSubject(
				match ($eventType) {
					CircleGenericEvent::LEFT => 'member_left',
					CircleGenericEvent::REMOVED => 'member_remove'
				},
				[
					'ver' => 2,
					'circle' => $this->shortenCircleData($circle),
					'initiator' => ($circle->hasInitiator() ? $this->shortenMemberData($circle->getInitiator()) : null),
					'member' => $this->shortenMemberData($member),
				]
			);
		} catch (UnhandledMatchError) {
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

	private function onMemberRemoveCircle(
		Circle $circle,
		Member $member,
		int $eventType = CircleGenericEvent::JOINED,
	): void {
		$event = $this->generateEvent('circles_as_member', $circle);

		try {
			$event->setSubject(
				match ($eventType) {
					CircleGenericEvent::LEFT => 'member_circle_left',
					CircleGenericEvent::REMOVED => 'member_circle_removed'
				},
				[
					'ver' => 2,
					'circle' => $this->shortenCircleData($circle),
					'initiator' => ($circle->hasInitiator() ? $this->shortenMemberData($circle->getInitiator()) : null),
					'member' => $this->shortenMemberData($member),
				]
			);
		} catch (UnhandledMatchError) {
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
	 * @param int $level
	 */
	public function onMemberLevel(
		Circle $circle,
		Member $member,
		int $level,
	): void {
		if ($level === Member::LEVEL_OWNER) {
			$this->onMemberOwner($circle, $member);

			return;
		}

		$event = $this->generateEvent('circles_as_moderator', $circle);
		$event->setSubject(
			'member_level',
			[
				'ver' => 2,
				'circle' => $this->shortenCircleData($circle),
				'initiator' => ($circle->hasInitiator() ? $this->shortenMemberData($circle->getInitiator()) : null),
				'member' => $this->shortenMemberData($member),
				'level' => $level
			]
		);

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
	 */
	public function onMemberOwner(Circle $circle, Member $member): void {
		$event = $this->generateEvent('circles_as_moderator', $circle);
		$event->setSubject(
			'member_owner',
			[
				'ver' => 2,
				'circle' => $this->shortenCircleData($circle),
				'initiator' => ($circle->hasInitiator() ? $this->shortenMemberData($circle->getInitiator()) : null),
				'member' => $this->shortenMemberData($member),
			]
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
	 * @param Circle|null $circle
	 *
	 * @return IEvent
	 */
	private function generateEvent(string $type, ?Circle $circle = null): IEvent {
		$event = $this->activityManager->generateEvent();
		$event->setApp(Application::APP_ID)
			->setType($type);
		if ($circle !== null) {
			$event->setObject(Application::APP_ID, $this->circleRequest->getActivityObjectId($circle->getSingleId()), $circle->getName());
			if ($circle->hasInitiator() && $circle->getInitiator()->getUserType() === Member::TYPE_USER) {
				$event->setAuthor($circle->getInitiator()->getUserId());
			}
		}

		return $event;
	}

	private function getActivitySetting(string $setting): string {
		return in_array(
			$setting,
			[
				Circle::SETTING_TEAM_FOLDER_QUOTA,
				self::SETTING_TEAM_TAB_ORDER,
				ConfigService::MEMBERS_LIMIT,
				ConfigService::ENFORCE_PASSWORD,
				self::SETTING_PASSWORD_SINGLE,
				self::SETTING_PASSWORD_SINGLE_ENABLED,
				...self::ACTIVITY_CONFIG_SETTINGS,
			],
			true,
		) ? $setting : 'generic';
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
				if ($user->getUserType() !== Member::TYPE_USER
					|| in_array($singleId, $knownSingleIds)) {
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

	private function shortenCircleData(Circle $circle): array {
		return [
			'singleId' => $circle->getSingleId(),
			'name' => $circle->getName(),
			'config' => $circle->getConfig(),
			'url' => $circle->getUrl(),
		];
	}

	private function shortenMemberData(Member $member): array {
		return [
			'userId' => $member->getUserId(),
			'displayName' => $member->getDisplayName(),
			'type' => $member->getUserType(),
			'level' => $member->getLevel(),
			'status' => $member->getStatus(),
		];
	}
}
