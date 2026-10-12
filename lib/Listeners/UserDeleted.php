<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Listeners;

use Exception;
use OCA\Circles\Repository\TeamFavoriteRepository;
use OCA\Circles\Service\SyncService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserDeletedEvent;

/** @template-implements IEventListener<UserDeletedEvent|Event> */
class UserDeleted implements IEventListener {
	/**
	 * UserDeleted constructor.
	 *
	 * @param SyncService $syncService
	 */
	public function __construct(
		private readonly SyncService $syncService,
		private readonly TeamFavoriteRepository $teamFavoriteRepository,
	) {
	}

	/**
	 * @param Event $event
	 */
	public function handle(Event $event): void {
		if (!($event instanceof UserDeletedEvent)) {
			return;
		}

		$user = $event->getUser();
		$this->teamFavoriteRepository->removeUser($user->getUID());

		try {
			$this->syncService->userDeleted($user->getUID());
		} catch (Exception) {
		}
	}
}
