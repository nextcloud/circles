<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Listeners;

use Exception;
use OCA\Circles\Service\SyncService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserCreatedEvent;

/** @template-implements IEventListener<UserCreatedEvent|Event> */
class UserCreated implements IEventListener {
	/** @var SyncService */
	private $syncService;


	/**
	 * UserCreated constructor.
	 *
	 * @param SyncService $syncService
	 */
	public function __construct(SyncService $syncService) {
		$this->syncService = $syncService;
	}


	/**
	 * @param Event $event
	 */
	public function handle(Event $event): void {
		if (!($event instanceof UserCreatedEvent)) {
			return;
		}

		$user = $event->getUser();

		try {
			$this->syncService->syncNextcloudUser($user->getUID());
		} catch (Exception $e) {
		}
	}
}
