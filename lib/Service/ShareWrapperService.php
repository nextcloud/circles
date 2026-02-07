<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use Exception;
use OCA\Circles\Db\ShareWrapperRequest;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\ShareWrapperNotFoundException;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Model\ShareWrapper;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\Share\IShare;

/**
 * Class ShareWrapperService
 *
 * @package OCA\Circles\Service
 */
class ShareWrapperService {
	use TStringTools;
	use TDeserialize;

	public const CACHE_SHARED_WITH = 'circles/getSharedWith';
	public const CACHE_SHARED_WITH_TTL = 900;

	private ICache $cache;

	public function __construct(
		ICacheFactory $cacheFactory,
		private ShareWrapperRequest $shareWrapperRequest,
	) {
		$this->cache = $cacheFactory->createDistributed(self::CACHE_SHARED_WITH);
	}

	/**
	 * @throws RequestBuilderException
	 * @throws ShareWrapperNotFoundException
	 */
	public function searchShare(string $singleId, int $nodeId): ShareWrapper {
		return $this->shareWrapperRequest->searchShare($singleId, $nodeId);
	}

	/**
	 * @throws NotFoundException
	 */
	public function save(IShare $share): void {
		$this->clearCache($share->getSharedWith());
		$this->shareWrapperRequest->save($share);
	}

	public function update(ShareWrapper $shareWrapper): void {
		$this->clearCache($shareWrapper->getSharedWith());
		$this->shareWrapperRequest->update($shareWrapper);
	}

	public function updateChildPermissions(ShareWrapper $shareWrapper): void {
		$this->clearCache($shareWrapper->getSharedWith());
		$this->shareWrapperRequest->updateChildPermissions($shareWrapper);
	}

	public function delete(ShareWrapper $shareWrapper): void {
		$this->clearCache($shareWrapper->getSharedWith());
		$this->shareWrapperRequest->delete((int)$shareWrapper->getId());
	}

	/**
	 * @throws Exception
	 */
	public function deleteUserSharesToCircle(string $circleId, string $userId): void {
		if ($userId === '') {
			throw new Exception('$initiator cannot be empty');
		}

		// Clear cache for the entire circle as we don't know all affected users
		$this->clearCacheForCircle($circleId);
		$this->shareWrapperRequest->deleteSharesToCircle($circleId, $userId);
	}

	public function deleteAllSharesToCircle(string $circleId): void {
		$this->clearCacheForCircle($circleId);
		$this->shareWrapperRequest->deleteSharesToCircle($circleId, '');
	}

	/**
	 * @return ShareWrapper[]
	 * @throws RequestBuilderException
	 */
	public function getSharesToCircle(
		string $circleId,
		?FederatedUser $shareRecipient = null,
		?FederatedUser $shareInitiator = null,
		bool $completeDetails = false,
	): array {
		return $this->shareWrapperRequest->getSharesToCircle(
			$circleId,
			$shareRecipient,
			$shareInitiator,
			$completeDetails
		);
	}

	/**
	 * @return ShareWrapper[]
	 */
	public function getSharesToCircles(array $circleIds): array {
		return $this->shareWrapperRequest->getSharesToCircles($circleIds);
	}

	/**
	 * @throws ShareWrapperNotFoundException
	 * @throws RequestBuilderException
	 */
	public function getShareById(int $shareId, ?FederatedUser $federatedUser = null): ShareWrapper {
		return $this->shareWrapperRequest->getShareById($shareId, $federatedUser);
	}

	/**
	 * @return ShareWrapper[]
	 * @throws RequestBuilderException
	 */
	public function getSharesByFileId(int $fileId, bool $getData = false): array {
		return $this->shareWrapperRequest->getSharesByFileId($fileId, $getData);
	}

	/**
	 * @return ShareWrapper[]
	 * @throws RequestBuilderException
	 */
	public function getSharesByFileIds(array $fileIds, bool $getData = false, bool $getChild = false): array {
		return $fileIds === [] ? [] : $this->shareWrapperRequest->getSharesByFileIds($fileIds, $getData, $getChild);
	}

	/**
	 * @throws RequestBuilderException
	 * @throws ShareWrapperNotFoundException
	 */
	public function getShareByToken(string $token, ?FederatedUser $federatedUser = null): ShareWrapper {
		return $this->shareWrapperRequest->getShareByToken($token, $federatedUser);
	}

	/**
	 * @return ShareWrapper[]
	 * @throws RequestBuilderException
	 */
	public function getSharedWith(
		FederatedUser $federatedUser,
		int $nodeId,
		?CircleProbe $probe,
	): array {
		$key = $this->generateSharedWithCacheKey($federatedUser, $nodeId, $probe->getChecksum());

		$cachedData = $this->cache->get($key);
		try {
			if (!is_string($cachedData)) {
				throw new InvalidItemException();
			}

			return $this->deserializeList($cachedData, ShareWrapper::class);
		} catch (InvalidItemException) {
			// Cache miss, continue to fetch from database
		}

		$shares = $this->shareWrapperRequest->getSharedWith($federatedUser, $nodeId, $probe);
		$this->cache->set($key, json_encode($shares), self::CACHE_SHARED_WITH_TTL);

		return $shares;
	}

	/**
	 * @return ShareWrapper[]
	 */
	public function getSharedWithByPath(
		FederatedUser $federatedUser,
		string $path,
		bool $forChildren,
		?CircleProbe $probe,
	): array {
		$key = $this->generateSharedWithByPathCacheKey($federatedUser, $path, $forChildren, $probe?->getChecksum());

		$cachedData = $this->cache->get($key);
		try {
			if (!is_string($cachedData)) {
				throw new InvalidItemException();
			}

			return $this->deserializeList($cachedData, ShareWrapper::class);
		} catch (InvalidItemException) {
			// Cache miss, continue to fetch from database
		}

		$shares = $this->shareWrapperRequest->getSharedWithByPath($federatedUser, $path, $forChildren, $probe);
		$this->cache->set($key, json_encode($shares), self::CACHE_SHARED_WITH_TTL);

		return $shares;
	}

	/**
	 * @return ShareWrapper[]
	 * @throws RequestBuilderException
	 */
	public function getSharesBy(
		FederatedUser $federatedUser,
		int $nodeId,
		bool $reshares,
		int $limit,
		int $offset,
		bool $getData = false,
		bool $completeDetails = false,
	): array {
		return $this->shareWrapperRequest->getSharesBy(
			$federatedUser,
			$nodeId,
			$reshares,
			$limit,
			$offset,
			$getData,
			$completeDetails
		);
	}

	/**
	 * @return ShareWrapper[]
	 * @throws RequestBuilderException
	 */
	public function getSharesInFolder(
		FederatedUser $federatedUser,
		Folder $node,
		bool $reshares,
		bool $shallow = true,
	): array {
		return $this->shareWrapperRequest->getSharesInFolder($federatedUser, $node, $reshares, $shallow);
	}

	/**
	 * @throws NotFoundException
	 * @throws ShareWrapperNotFoundException
	 * @throws RequestBuilderException
	 */
	public function getChild(IShare $share, FederatedUser $federatedUser): ShareWrapper {
		try {
			return $this->shareWrapperRequest->getChild($federatedUser, (int)$share->getId());
		} catch (ShareWrapperNotFoundException) {
			// Child doesn't exist, create it
		}

		return $this->createChild($share, $federatedUser);
	}

	/**
	 * Clear cache entries for a specific singleId
	 * If singleId is empty, clears the entire cache
	 */
	public function clearCache(string $singleId): void {
		if ($singleId === '') {
			$this->cache->clear('');
			return;
		}

		// Clear all cache entries for this singleId by using it as a prefix
		// Cache keys format: singleId#nodeId#probeSum or singleId#pathHash#forChildren#probeSum
		$this->cache->clear($singleId . '#');
	}

	/**
	 * Clear cache for all members of a circle
	 * This is a fallback when we can't determine specific affected users
	 */
	public function clearCacheForCircle(string $circleId): void {
		// Since we can't easily determine all singleIds affected by a circle,
		// we clear the entire cache. This is inefficient but ensures consistency.
		// A better approach would be to iterate over all circle members,
		// but that would require circular dependency with MembershipService.
		$this->cache->clear('');
	}

	/**
	 * Clear cache for a specific node across all users
	 * Should be called when a file/folder is modified, deleted, or renamed
	 */
	public function clearCacheForNode(int $nodeId): void {
		// Since cache keys include nodeId but we can't use it as a prefix,
		// we need to clear the entire cache.
		// TODO: Implement a reverse index (nodeId -> singleIds) for efficient invalidation
		$this->cache->clear('');
	}

	/**
	 * @throws ShareWrapperNotFoundException
	 * @throws NotFoundException
	 * @throws RequestBuilderException
	 */
	private function createChild(IShare $share, FederatedUser $federatedUser): ShareWrapper {
		$this->clearCache($federatedUser->getSingleId());
		$share->setSharedWith($federatedUser->getSingleId());
		$childId = $this->shareWrapperRequest->save($share, (int)$share->getId());

		return $this->getShareById($childId, $federatedUser);
	}

	private function generateSharedWithByPathCacheKey(
		FederatedUser $federatedUser,
		string $path,
		bool $forChildren,
		?string $probeSum,
	): string {
		$pathHash = md5($path);
		return $federatedUser->getSingleId() . '#'
			. $pathHash . '#'
			. ($forChildren ? '1' : '0') . '#'
			. ($probeSum ?? '');
	}

	private function generateSharedWithCacheKey(
		FederatedUser $federatedUser,
		int $nodeId,
		string $probeSum,
	): string {
		return $federatedUser->getSingleId() . '#'
			. $nodeId . '#'
			. $probeSum;
	}
}
