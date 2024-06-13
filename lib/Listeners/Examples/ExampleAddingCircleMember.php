<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Listeners\Examples;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Events\AddingCircleMemberEvent;
use OCA\Circles\Events\CircleGenericEvent;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Membership;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/** @template-implements IEventListener<AddingCircleMemberEvent|Event> */

class ExampleAddingCircleMember implements IEventListener {
	use TNCLogger;


	/** @var ConfigService */
	private $configService;

	public function __construct(ConfigService $configService) {
		$this->configService = $configService;

		$this->setup('app', Application::APP_ID);
	}

	public function handle(Event $event): void {
		if (!$event instanceof AddingCircleMemberEvent) {
			return;
		}

		if ($this->configService->getAppValue(ConfigService::EVENT_EXAMPLES) !== '1') {
			return;
		}

		$prefix = '[Example Event] (ExampleAddingCircleMember) ';

		$circle = $event->getCircle();
		$member = $event->getMember();

		$eventType = ($event->getType() === CircleGenericEvent::INVITED) ? 'invited' : 'joined';

		$info = 'A new member have been added (' . $eventType . ') to a Circle. ';

		$info .= 'userId: ' . $member->getUserId() . '; userType: ' . Member::$TYPE[$member->getUserType()]
				 . '; singleId: ' . $member->getSingleId() . '; memberId: ' . $member->getId()
				 . '; isLocal: ' . json_encode($member->isLocal()) . '; level: '
				 . Member::$DEF_LEVEL[$member->getLevel()] . '; ';

		$memberships = array_map(
			function (Membership $membership) {
				return $membership->getCircleId();
			}, $circle->getMemberships()
		);

		$listMemberships = (count($memberships) > 0) ? implode(', ', $memberships) : 'none';
		$info .= 'circleName: ' . $circle->getDisplayName() . '; circleId: ' . $circle->getSingleId()
				 . '; Circle memberships: ' . $listMemberships . '.';

		if ($member->getUserType() === Member::TYPE_CIRCLE) {
			$basedOn = $member->getBasedOn();
			$members = array_map(
				function (Member $member) {
					return $member->getUserId() . ' (' . Member::$TYPE[$member->getUserType()] . ')';
				}, $basedOn->getInheritedMembers()
			);

			$info .= ' Member is a Circle (singleId: ' . $basedOn->getSingleId()
					 . ') that contains those inherited members: ' . implode(', ', $members);
		}

		$this->log(3, $prefix . $info);
	}
}
