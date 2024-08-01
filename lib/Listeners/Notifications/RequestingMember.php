<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
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


namespace OCA\Circles\Listeners\Notifications;

use OCA\Circles\Events\AddingCircleMemberEvent;
use OCA\Circles\Model\Circle;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Events\CircleGenericEvent;
use OCA\Circles\Events\RequestingCircleMemberEvent;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Service\NotificationService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Class RequestingMember
 *
 * @package OCA\Circles\Listeners\Notifications
 *
 * @template-implements IEventListener<RequestingCircleMemberEvent|AddingCircleMemberEvent|Event>
 */
class RequestingMember implements IEventListener {
	use TNCLogger;


	/** @var NotificationService */
	private $notificationService;


	/**
	 * RequestingMember constructor.
	 */
	public function __construct(NotificationService $notificationService) {
		$this->notificationService = $notificationService;

		$this->setup('app', Application::APP_ID);
	}


	/**
	 * @param Event $event
	 *
	 * @throws RequestBuilderException
	 */
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
