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
use OCP\Teams\ITeamFolderPoolProvider;
use OCP\Teams\ITeamManager;
use OCP\Teams\Team;
use OCP\Teams\TeamFolderPoolQuotaException;
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

			// The circle (team) is being destroyed entirely, so remove the
			// team folder and its contents rather than unlinking it. Unlinking
			// would leave an orphaned folder that no team owns anymore and
			// that is hard for an admin to discover and clean up.
			$circle = $event->getCircle();
			$provider->removeTeamFolder($circle->getSingleId());
			if ($provider instanceof ITeamFolderPoolProvider) {
				$this->releaseTeamStoragePool($provider, $circle->getSingleId());
			}
			return;
		}

		if (!$event instanceof CreatingCircleEvent) {
			return;
		}

		$circle = $event->getCircle();
		if (!$circle->isLocal()) {
			return;
		}

		$requested = $this->wasTeamFolderRequested($event);
		if ($requested ? !$this->policy->shouldCreateTeamFolder($circle) : !$this->policy->isEligibleCircle($circle)) {
			return;
		}

		$provider = $this->teamManager->getTeamFolderProvider();
		if ($provider === null) {
			return;
		}

		try {
			if ($provider instanceof ITeamFolderPoolProvider) {
				$creator = $circle->hasInitiator() ? $circle->getInitiator() : $circle->getOwner();
				$params = $event->getFederatedEvent()->getParams();
				if ($params->hasKey(TeamFolderPolicy::PARAM_STORAGE_POOL_ID)) {
					$this->policy->assignStoragePoolForUser($provider, $circle->getSingleId(), $creator->getUserId(), $params->g(TeamFolderPolicy::PARAM_STORAGE_POOL_ID));
				} else {
					$this->policy->assignStoragePoolForUser($provider, $circle->getSingleId(), $creator->getUserId());
				}
			}
			if (!$requested) {
				return;
			}
			$provider->createTeamFolder(
				new Team(
					teamId: $circle->getSingleId(),
					displayName: $circle->getDisplayName(),
					link: null,
				),
				$this->policy->getQuotaForCircle($circle),
			);
		} catch (\Throwable $e) {
			$event->getFederatedEvent()->getResult()->sArray('teamFolderProvisioning', [
				'status' => 'failed',
				'code' => $e instanceof TeamFolderPoolQuotaException ? 'storage_pool_exceeded' : 'team_folder_creation_failed',
			]);
			$this->logger->error('Failed to auto-create team folder', [
				'teamId' => $circle->getSingleId(),
				'exception' => $e,
			]);
		}
	}

	private function releaseTeamStoragePool(ITeamFolderPoolProvider $provider, string $teamId): void {
		if (!array_key_exists($teamId, $provider->getTeamStoragePoolLimits())) {
			return;
		}
		$provider->deleteStoragePool(null, $teamId);
	}

	private function wasTeamFolderRequested(CreatingCircleEvent $event): bool {
		$params = $event->getFederatedEvent()->getParams();
		if (!$params->hasKey(TeamFolderPolicy::PARAM_CREATE_TEAM_FOLDER)) {
			return true;
		}

		return $params->gBool(TeamFolderPolicy::PARAM_CREATE_TEAM_FOLDER);
	}
}
