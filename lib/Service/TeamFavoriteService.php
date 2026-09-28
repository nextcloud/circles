<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use OCA\Circles\Db\TeamFavoriteRequest;
use OCA\Circles\Exceptions\InsufficientPermissionException;
use OCP\AppFramework\Http;
use OCP\AppFramework\OCS\OCSException;
use OCP\IUserSession;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;

class TeamFavoriteService {
	public function __construct(
		private readonly TeamFavoriteRequest $teamFavoriteRequest,
		private readonly PermissionService $permissionService,
		private readonly IUserSession $userSession,
		private readonly ILockingProvider $lockingProvider,
	) {
	}

	/**
	 * @return list<string>
	 */
	public function getFavoriteCircleIds(): array {
		return $this->teamFavoriteRequest->getCircleIds($this->getCurrentUserId(), true);
	}

	/**
	 * @return list<string>
	 */
	public function setFavorite(string $circleId, bool $isFavorite): array {
		$userId = $this->getCurrentUserId();
		$lock = $this->acquireLock($userId);
		try {
			$favoriteCircleIds = $this->pruneFavorites($userId);
			if (!$isFavorite) {
				$this->teamFavoriteRequest->remove($userId, $circleId);
			} elseif (!in_array($circleId, $favoriteCircleIds, true)) {
				try {
					$this->permissionService->userMustBeMember($userId, $circleId);
				} catch (InsufficientPermissionException $exception) {
					throw new OCSException($exception->getMessage(), Http::STATUS_FORBIDDEN);
				}
				$this->teamFavoriteRequest->add($userId, $circleId);
			}
			return $this->teamFavoriteRequest->getCircleIds($userId, true);
		} finally {
			$this->lockingProvider->releaseLock($lock, ILockingProvider::LOCK_EXCLUSIVE);
		}
	}

	/**
	 * @param list<string> $circleIds
	 * @param list<string> $expectedCircleIds
	 * @return list<string>
	 */
	public function reorder(array $circleIds, array $expectedCircleIds): array {
		foreach ([$circleIds, $expectedCircleIds] as $ids) {
			if (!array_is_list($ids) || array_filter($ids, 'is_string') !== $ids
				|| count($ids) !== count(array_unique($ids))) {
				throw new OCSException('Invalid favorite team order.', Http::STATUS_BAD_REQUEST);
			}
		}
		if (count($circleIds) !== count($expectedCircleIds) || array_diff($circleIds, $expectedCircleIds) !== []) {
			throw new OCSException('Invalid favorite team order.', Http::STATUS_BAD_REQUEST);
		}
		$userId = $this->getCurrentUserId();
		$lock = $this->acquireLock($userId);
		try {
			$currentCircleIds = $this->pruneFavorites($userId);
			if ($expectedCircleIds !== $currentCircleIds) {
				throw new OCSException('Favorite teams have changed. Reload before reordering.', Http::STATUS_CONFLICT);
			}
			if ($circleIds !== $currentCircleIds) {
				$this->teamFavoriteRequest->replaceOrder($userId, $circleIds);
			}
			return $this->teamFavoriteRequest->getCircleIds($userId, true);
		} finally {
			$this->lockingProvider->releaseLock($lock, ILockingProvider::LOCK_EXCLUSIVE);
		}
	}

	/**
	 * @return list<string>
	 */
	private function pruneFavorites(string $userId): array {
		$stored = $this->teamFavoriteRequest->getCircleIds($userId);
		$accessible = $this->teamFavoriteRequest->getCircleIds($userId, true);
		foreach (array_diff($stored, $accessible) as $circleId) {
			$this->teamFavoriteRequest->remove($userId, $circleId);
		}
		return $accessible;
	}

	private function acquireLock(string $userId): string {
		$lock = 'circles:team-favorites:' . hash('sha256', $userId);
		try {
			$this->lockingProvider->acquireLock($lock, ILockingProvider::LOCK_EXCLUSIVE);
		} catch (LockedException) {
			throw new OCSException('Favorite teams are being updated. Please retry.', Http::STATUS_CONFLICT);
		}
		return $lock;
	}

	private function getCurrentUserId(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new OCSException('No authenticated user.', Http::STATUS_UNAUTHORIZED);
		}

		return $user->getUID();
	}
}
