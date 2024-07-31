<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Listeners\Notifications;

use OCA\Circles\Events\AddingCircleMemberEvent;
use OCA\Circles\Events\CircleGenericEvent;
use OCA\Circles\Events\RequestingCircleMemberEvent;
use OCA\Circles\Model\Circle;
use OCA\Circles\Service\NotificationService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/** @template-implements IEventListener<RequestingCircleMemberEvent|AddingCircleMemberEvent|Event> */
class RequestingMember implements IEventListener {
	public function __construct(
		private NotificationService $notificationService,
	) {
	}

	public function handle(Event $event): void {
		if ($event instanceof RequestingCircleMemberEvent) {
			$this->handleRequestingCircleMemberEvent($event);
		} elseif ($event instanceof AddingCircleMemberEvent) {
			$this->handleAddingCircleMemberEvent($event);
		}
	}

	public function handleRequestingCircleMemberEvent(RequestingCircleMemberEvent $event): void {
		$member = $event->getMember();
		if ($event->getType() === CircleGenericEvent::REQUESTED) {
			$this->notificationService->notificationRequested($member);
		} else {
			$this->notificationService->notificationInvited($member);
		}
	}

	public function handleAddingCircleMemberEvent(AddingCircleMemberEvent $event): void {
		if ($event->getType() === CircleGenericEvent::JOINED && $event->getCircle()->isConfig(Circle::CFG_INVITE)) {
			$member = $event->getMember();
			$this->notificationService->markInvitationAsProcessed($member);
		}
	}
}
