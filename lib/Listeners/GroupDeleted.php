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
use OCP\Group\Events\GroupDeletedEvent;

/** @template-implements IEventListener<GroupDeletedEvent|Event> */
class GroupDeleted implements IEventListener {
	/** @var SyncService */
	private $syncService;


	/**
	 * GroupDeleted constructor.
	 *
	 * @param SyncService $syncService
	 */
	public function __construct(SyncService $syncService) {
		$this->syncService = $syncService;
	}

	public function handle(Event $event): void {
		if (!($event instanceof GroupDeletedEvent)) {
			return;
		}

		$group = $event->getGroup();
		try {
			$this->syncService->groupDeleted($group->getGID());
		} catch (Exception $e) {
		}
	}
}
