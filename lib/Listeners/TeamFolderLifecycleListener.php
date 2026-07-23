<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Listeners;

use OCA\Circles\Events\CreatingCircleEvent;
use OCA\Circles\Events\DestroyingCircleEvent;
use OCA\Circles\Service\TeamFolderPolicy;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Teams\ITeamManager;
use OCP\Teams\Team;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<CreatingCircleEvent|DestroyingCircleEvent>
 */
class TeamFolderLifecycleListener implements IEventListener {
	public function __construct(
		private readonly ITeamManager $teamManager,
		private readonly TeamFolderPolicy $policy,
		private readonly LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if ($event instanceof DestroyingCircleEvent) {
			$provider = $this->teamManager->getTeamFolderProvider();
			if ($provider === null) {
				return;
			}

			$circle = $event->getCircle();
			$provider->unlinkTeamFolder($circle->getSingleId());
			return;
		}

		if (!$event instanceof CreatingCircleEvent) {
			return;
		}

		$circle = $event->getCircle();
		if (!$this->policy->shouldCreateTeamFolder($circle)) {
			return;
		}

		$provider = $this->teamManager->getTeamFolderProvider();
		if ($provider === null) {
			return;
		}

		try {
			$provider->createTeamFolder(
				new Team(
					teamId: $circle->getSingleId(),
					displayName: $circle->getDisplayName(),
					link: null,
				),
				$this->policy->getDefaultQuota(),
			);
		} catch (\Throwable $e) {
			$this->logger->error('Failed to auto-create team folder', [
				'teamId' => $circle->getSingleId(),
				'exception' => $e,
			]);
		}
	}
}
