<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2020
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Circles\Listeners;

use Exception;
use OCA\Circles\Service\SyncService;
use OCP\App\IAppManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\Config\IUserMountCache;
use OCP\Group\Events\UserRemovedEvent;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Throwable;

/** @template-implements IEventListener<UserRemovedEvent> */
class GroupMemberRemoved implements IEventListener {
	/** @var SyncService */
	private $syncService;

	/** @var IAppManager */
	private $appManager;

	/** @var IUserMountCache */
	private $userMountCache;

	/** @var IMountProviderCollection */
	private $mountProviderCollection;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(
		SyncService $syncService,
		IAppManager $appManager,
		IUserMountCache $userMountCache,
		IMountProviderCollection $mountProviderCollection,
		LoggerInterface $logger,
	) {
		$this->syncService = $syncService;
		$this->appManager = $appManager;
		$this->userMountCache = $userMountCache;
		$this->mountProviderCollection = $mountProviderCollection;
		$this->logger = $logger;
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserRemovedEvent)) {
			return;
		}

		$user = $event->getUser();
		$group = $event->getGroup();

		try {
			$this->syncService->groupMemberRemoved($group->getGID(), $user->getUID());
		} catch (Exception $e) {
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
			$folderManager = Server::get(\OCA\GroupFolders\Folder\FolderManager::class);
			return $folderManager->hasFolderForGroup($groupId);
		} catch (Throwable $e) {
			$this->logger->debug('Failed to check if group ' . $groupId . ' has an associated team folder', ['exception' => $e]);
			return false;
		}
	}
}
