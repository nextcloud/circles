<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Listeners;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\ConfigLexicon;
use OCA\Circles\Events\DestroyingCircleEvent;
use OCA\Circles\Events\MembershipsRemovedEvent;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\FederatedUserService;
use OCP\Config\IUserConfig;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/**
 * Drops the favorite resources a user keeps for a team once they can no
 * longer see the team, or the team is gone.
 *
 * @template-implements IEventListener<DestroyingCircleEvent|MembershipsRemovedEvent>
 */
class TeamResourceFavoritesCleanup implements IEventListener {
	public function __construct(
		private readonly IUserConfig $userConfig,
		private readonly FederatedUserService $federatedUserService,
		private readonly LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if ($event instanceof DestroyingCircleEvent) {
			$this->userConfig->deleteKey(Application::APP_ID, ConfigLexicon::USER_RESOURCE_FAVORITES_PREFIX . $event->getCircle()->getSingleId());
			return;
		}

		if (!$event instanceof MembershipsRemovedEvent) {
			return;
		}

		foreach ($event->getMemberships() as $membership) {
			try {
				$federatedUser = $this->federatedUserService->getFederatedUser($membership->getSingleId());
			} catch (\Exception $e) {
				$this->logger->debug('Could not resolve the member to clean up its favorite resources', ['exception' => $e]);
				continue;
			}
			if ($federatedUser->getUserType() !== Member::TYPE_USER || !$federatedUser->isLocal()) {
				continue;
			}
			$this->userConfig->deleteUserConfig(
				$federatedUser->getUserId(),
				Application::APP_ID,
				ConfigLexicon::USER_RESOURCE_FAVORITES_PREFIX . $membership->getCircleId(),
			);
		}
	}
}
