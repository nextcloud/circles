<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Listeners;

use Exception;
use OCA\Circles\Service\SyncService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\GroupCreatedEvent;

/** @template-implements IEventListener<GroupCreatedEvent|Event> */
class GroupCreated implements IEventListener {
	/** @var SyncService */
	private $syncService;

	public function __construct(SyncService $syncService) {
		$this->syncService = $syncService;
	}

	public function handle(Event $event): void {
		if (!($event instanceof GroupCreatedEvent)) {
			return;
		}

		$group = $event->getGroup();
		try {
			$this->syncService->syncNextcloudGroup($group->getGID());
		} catch (Exception $e) {
		}
	}
}
