<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Listeners\Notifications;

use OCA\Circles\Events\CircleGenericEvent;
use OCA\Circles\Events\RequestingCircleMemberEvent;
use OCA\Circles\Service\NotificationService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/** @template-implements IEventListener<RequestingCircleMemberEvent|Event> */
class RequestingMember implements IEventListener {
	public function __construct(
		private NotificationService $notificationService,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof RequestingCircleMemberEvent) {
			return;
		}

		$member = $event->getMember();
		if ($event->getType() === CircleGenericEvent::REQUESTED) {
			$this->notificationService->notificationRequested($member);
		} else {
			$this->notificationService->notificationInvited($member);
		}
	}
}
