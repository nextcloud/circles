<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Listeners;

use Exception;
use OCA\Circles\Events\CircleMemberRemovedEvent;
use OCP\App\IAppManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\Config\IUserMountCache;
use OCP\IUserManager;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Throwable;

/** @template-implements IEventListener<CircleMemberRemovedEvent> */
class CircleMemberRemoved implements IEventListener {
	public function __construct(
		private readonly IUserManager $userManager,
		private readonly IAppManager $appManager,
		private readonly IUserMountCache $userMountCache,
		private readonly IMountProviderCollection $mountProviderCollection,
		private readonly LoggerInterface $logger,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof CircleMemberRemovedEvent)) {
			return;
		}

		$member = $event->getMember();
		if ($member === null) {
			return;
		}

		$user = $this->userManager->get($member->getUserId());
		if ($user === null) {
			return;
		}

		$circle = $event->getCircle();
		if (!$this->circleHasAssociatedGroupFolder($circle->getSingleId())) {
			return;
		}

		try {
			// refresh list of mounts for user
			$mounts = $this->mountProviderCollection->getMountsForUser($user);
			$mounts[] = $this->mountProviderCollection->getHomeMountForUser($user);
			$this->userMountCache->registerMounts($user, $mounts);
		} catch (Exception $e) {
			$this->logger->debug('Failed to refresh mounts for user ' . $user->getUID(), ['exception' => $e]);
		}
	}

	private function circleHasAssociatedGroupFolder(string $circleId): bool {
		if (!$this->appManager->isEnabledForUser('groupfolders')) {
			return false;
		}

		try {
			$folderManager = Server::get(\OCA\GroupFolders\Folder\FolderManager::class);
			return $folderManager->hasFolderForCircle($circleId);
		} catch (Throwable $e) {
			$this->logger->debug('Failed to check if circle ' . $circleId . ' has an associated team folder', ['exception' => $e]);
			return false;
		}
	}
}
