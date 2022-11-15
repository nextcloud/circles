<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
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
use OCP\Files\NotFoundException;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\Share\IShare;
use OCP\Files\Folder;

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


	/** @var ShareWrapperRequest */
	private $shareWrapperRequest;

	private ICache $cache;


	/**
	 * ShareWrapperService constructor.
	 *
	 * @param ICacheFactory $cacheFactory
	 * @param ShareWrapperRequest $shareWrapperRequest
	 */
	public function __construct(ICacheFactory $cacheFactory, ShareWrapperRequest $shareWrapperRequest) {
		$this->cache = $cacheFactory->createDistributed(self::CACHE_SHARED_WITH);

		$this->shareWrapperRequest = $shareWrapperRequest;
	}


	/**
	 * @param string $singleId
	 * @param int $nodeId
	 *
	 * @return ShareWrapper
	 * @throws RequestBuilderException
	 * @throws ShareWrapperNotFoundException
	 */
	public function searchShare(string $singleId, int $nodeId): ShareWrapper {
		return $this->shareWrapperRequest->searchShare($singleId, $nodeId);
	}


	/**
	 * @param IShare $share
	 *
	 * @throws NotFoundException
	 */
	public function save(IShare $share): void {
		$this->cache->clear('');
		$this->shareWrapperRequest->save($share);
	}


	/**
	 * @param ShareWrapper $shareWrapper
	 */
	public function update(ShareWrapper $shareWrapper): void {
		$this->cache->clear('');
		$this->shareWrapperRequest->update($shareWrapper);
	}


	/**
	 * @param ShareWrapper $shareWrapper
	 */
	public function delete(ShareWrapper $shareWrapper): void {
		$this->cache->clear('');
		$this->shareWrapperRequest->delete((int)$shareWrapper->getId());
	}

	/**
	 * @param string $circleId
	 * @param string $userId
	 *
	 * @throws Exception
	 */
	public function deleteUserSharesToCircle(string $circleId, string $userId): void {
		if ($userId === '') {
			throw new Exception('$initiator cannot be empty');
		}

		$this->cache->clear('');
		$this->shareWrapperRequest->deleteSharesToCircle($circleId, $userId);
	}


	/**
	 * @param string $circleId
	 */
	public function deleteAllSharesToCircle(string $circleId): void {
		$this->cache->clear('');
		$this->shareWrapperRequest->deleteSharesToCircle($circleId, '');
	}


	/**
	 * @param string $circleId
	 * @param FederatedUser|null $shareRecipient
	 * @param FederatedUser|null $shareInitiator
	 * @param bool $completeDetails
	 *
	 * @return ShareWrapper[]
	 * @throws RequestBuilderException
	 */
	public function getSharesToCircle(
		string $circleId,
		?FederatedUser $shareRecipient = null,
		?FederatedUser $shareInitiator = null,
		bool $completeDetails = false
	): array {
		return $this->shareWrapperRequest->getSharesToCircle(
			$circleId,
			$shareRecipient,
			$shareInitiator,
			$completeDetails
		);
	}


	/**
	 * @param int $shareId
	 * @param FederatedUser|null $federatedUser
	 *
	 * @return ShareWrapper
	 * @throws ShareWrapperNotFoundException
	 * @throws RequestBuilderException
	 */
	public function getShareById(int $shareId, ?FederatedUser $federatedUser = null): ShareWrapper {
		return $this->shareWrapperRequest->getShareById($shareId, $federatedUser);
	}


	/**
	 * @param int $fileId
	 * @param bool $getData
	 *
	 * @return ShareWrapper[]
	 * @throws RequestBuilderException
	 */
	public function getSharesByFileId(int $fileId, bool $getData = false): array {
		return $this->shareWrapperRequest->getSharesByFileId($fileId, $getData);
	}

	/**
	 * @param string $token
	 * @param FederatedUser|null $federatedUser
	 *
	 * @return ShareWrapper
	 * @throws RequestBuilderException
	 * @throws ShareWrapperNotFoundException
	 */
	public function getShareByToken(string $token, ?FederatedUser $federatedUser = null): ShareWrapper {
		return $this->shareWrapperRequest->getShareByToken($token, $federatedUser);
	}


	/**
	 * @param FederatedUser $federatedUser
	 * @param int $nodeId
	 * @param CircleProbe|null $probe
	 *
	 * @return ShareWrapper[]
	 * @throws RequestBuilderException
	 */
	public function getSharedWith(
		FederatedUser $federatedUser,
		int $nodeId,
		?CircleProbe $probe
	): array {
		$key = $this->generateSharedWithCacheKey($federatedUser, $nodeId, $probe->getChecksum());

		$cachedData = $this->cache->get($key);
		try {
			if (!is_string($cachedData)) {
				throw new InvalidItemException();
			}

			return $this->deserializeList($cachedData, ShareWrapper::class);
		} catch (InvalidItemException $e) {
		}

		$shares = $this->shareWrapperRequest->getSharedWith($federatedUser, $nodeId, $probe);
		$this->cache->set($key, json_encode($shares), self::CACHE_SHARED_WITH_TTL);

		return $shares;
	}


	/**
	 * @param FederatedUser $federatedUser
	 * @param int $nodeId
	 * @param bool $reshares
	 * @param int $offset
	 * @param int $limit
	 * @param bool $getData
	 * @param bool $completeDetails
	 *
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
		bool $completeDetails = false
	): array {
		return $this->shareWrapperRequest->getSharesBy(
			$federatedUser, $nodeId, $reshares, $limit, $offset, $getData, $completeDetails
		);
	}


	/**
	 * @param FederatedUser $federatedUser
	 * @param Folder $node
	 * @param bool $reshares
	 * @param bool $shallow Whether the method should stop at the first level, or look into sub-folders.
	 *
	 * @return ShareWrapper[]
	 * @throws RequestBuilderException
	 */
	public function getSharesInFolder(FederatedUser $federatedUser, Folder $node, bool $reshares, bool $shallow = true): array {
		return $this->shareWrapperRequest->getSharesInFolder($federatedUser, $node, $reshares, $shallow);
	}


	/**
	 * @param FederatedUser $federatedUser
	 * @param IShare $share
	 *
	 * @return ShareWrapper
	 * @throws NotFoundException
	 * @throws ShareWrapperNotFoundException
	 * @throws RequestBuilderException
	 */
	public function getChild(IShare $share, FederatedUser $federatedUser): ShareWrapper {
		try {
			return $this->shareWrapperRequest->getChild($federatedUser, (int)$share->getId());
		} catch (ShareWrapperNotFoundException $e) {
		}

		return $this->createChild($share, $federatedUser);
	}


	public function clearCache(string $singleId): void {
		$this->cache->clear($singleId);
	}


	/**
	 * @param FederatedUser $federatedUser
	 * @param IShare $share
	 *
	 * @return ShareWrapper
	 * @throws ShareWrapperNotFoundException
	 * @throws NotFoundException
	 * @throws RequestBuilderException
	 */
	private function createChild(IShare $share, FederatedUser $federatedUser): ShareWrapper {
		$this->cache->clear('');
		$share->setSharedWith($federatedUser->getSingleId());
		$childId = $this->shareWrapperRequest->save($share, (int)$share->getId());

		return $this->getShareById($childId, $federatedUser);
	}


	/**
	 * @param FederatedUser $federatedUser
	 * @param int $nodeId
	 * @param string $probeSum
	 *
	 * @return string
	 */
	private function generateSharedWithCacheKey(
		FederatedUser $federatedUser,
		int $nodeId,
		string $probeSum
	): string {
		return $federatedUser->getSingleId() . '#'
			   . $nodeId . '#'
			   . $probeSum;
	}
}
