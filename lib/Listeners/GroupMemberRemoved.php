<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Listeners;

use Exception;
use OCA\Circles\Service\SyncService;
use OCA\GroupFolders\Folder\FolderManager;
use OCP\App\IAppManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\Config\IUserMountCache;
use OCP\Group\Events\UserRemovedEvent;
use OCP\Server;
use Psr\Log\LoggerInterface;

/** @template-implements IEventListener<UserRemovedEvent> */
class GroupMemberRemoved implements IEventListener {
	public function __construct(
		private readonly SyncService $syncService,
		private readonly IAppManager $appManager,
		private readonly IUserMountCache $userMountCache,
		private readonly IMountProviderCollection $mountProviderCollection,
		private readonly LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof UserRemovedEvent)) {
			return;
		}

		$user = $event->getUser();
		$group = $event->getGroup();

		try {
			$this->syncService->groupMemberRemoved($group->getGID(), $user->getUID());
		} catch (Exception) {
		}

		if (!$this->groupHasAssociatedGroupFolder($group->getGID())) {
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

	private function groupHasAssociatedGroupFolder(string $groupId): bool {
		if (!$this->appManager->isEnabledForUser('groupfolders')) {
			return false;
		}

		try {
			$folderManager = Server::get(FolderManager::class);
			return $folderManager->hasFolderForGroup($groupId);
		} catch (Exception $e) {
			$this->logger->debug('Failed to check if group ' . $groupId . ' has an associated team folder', ['exception' => $e]);
			return false;
		}
	}
}
