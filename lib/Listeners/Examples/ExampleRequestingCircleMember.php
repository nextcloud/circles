<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Listeners\Examples;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Events\CircleGenericEvent;
use OCA\Circles\Events\RequestingCircleMemberEvent;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/** @template-implements IEventListener<RequestingCircleMemberEvent|Event> */
class ExampleRequestingCircleMember implements IEventListener {
	use TNCLogger;


	/** @var ConfigService */
	private $configService;

	public function __construct(ConfigService $configService) {
		$this->configService = $configService;

		$this->setup('app', Application::APP_ID);
	}

	public function handle(Event $event): void {
		if (!$event instanceof RequestingCircleMemberEvent) {
			return;
		}

		if ($this->configService->getAppValue(ConfigService::EVENT_EXAMPLES) !== '1') {
			return;
		}

		$prefix = '[Example Event] (ExampleRequestingCircleMember) ';

		if ($event->getType() === CircleGenericEvent::INVITED) {
			$info = 'A new member have been invited to a Circle. ';
		} else {
			$info = 'A new member is requesting to join a Circle. ';
		}

		$circle = $event->getCircle();
		$member = $event->getMember();
		$info .= 'circleId: ' . $circle->getSingleId() . '; userId: ' . $member->getUserId() . '; userType: '
				 . Member::$TYPE[$member->getUserType()] . '; singleId: ' . $member->getSingleId()
				 . '; memberId: ' . $member->getId() . '; isLocal: ' . json_encode($member->isLocal()) . '; ';

		$this->log(3, $prefix . $info);
	}
}
