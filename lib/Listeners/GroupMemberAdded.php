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
use OCP\Group\Events\UserAddedEvent;

/** @template-implements IEventListener<UserAddedEvent|Event> */
class GroupMemberAdded implements IEventListener {
	/** @var SyncService */
	private $syncService;

	public function __construct(SyncService $syncService) {
		$this->syncService = $syncService;
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserAddedEvent)) {
			return;
		}

		$group = $event->getGroup();
		$user = $event->getUser();
		try {
			$this->syncService->groupMemberAdded($group->getGID(), $user->getUID());
		} catch (Exception $e) {
			throw $e;
		}
	}
}
