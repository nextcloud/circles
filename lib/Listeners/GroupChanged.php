<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Listeners;

use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\FederatedUserService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\GroupChangedEvent;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/** @template-implements IEventListener<GroupChangedEvent|Event> */
class GroupChanged implements IEventListener {
	public function __construct(
		private readonly IUserSession $userSession,
		private readonly FederatedUserService $federatedUserService,
		private readonly CircleService $circleService,
		private readonly LoggerInterface $logger,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof GroupChangedEvent)) {
			return;
		}

		if ($event->getFeature() !== 'displayName') {
			return;
		}

		$user = $this->userSession->getUser();
		$this->federatedUserService->setLocalCurrentUser($user);

		$groupId = $event->getGroup()->getGID();
		try {
			$this->circleService->updateName("group:$groupId", $event->getValue());
		} catch (CircleNotFoundException $e) {
			// Silently ignore (there is no circle for the group yet)
		} catch (\Exception $e) {
			$this->logger->warning("Failed to update display name of circle of group $groupId: " . $e->getMessage(), [
				'exception' => $e,
				'groupId' => $groupId,
			]);
		}
	}
}
