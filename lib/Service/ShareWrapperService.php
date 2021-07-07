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

use ArtificialOwl\MySmallPhpTools\Traits\TStringTools;
use OCA\Circles\Db\ShareWrapperRequest;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\ShareWrapperNotFoundException;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\ShareWrapper;
use OCP\Files\NotFoundException;
use OCP\Share\IShare;

/**
 * Class ShareWrapperService
 *
 * @package OCA\Circles\Service
 */
class ShareWrapperService {
	use TStringTools;


	/** @var ShareWrapperRequest */
	private $shareWrapperRequest;


	/**
	 * ShareWrapperService constructor.
	 *
	 * @param ShareWrapperRequest $shareWrapperRequest
	 */
	public function __construct(ShareWrapperRequest $shareWrapperRequest) {
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
		$this->shareWrapperRequest->save($share);
	}


	/**
	 * @param ShareWrapper $shareWrapper
	 */
	public function update(ShareWrapper $shareWrapper): void {
		$this->shareWrapperRequest->update($shareWrapper);
	}


	/**
	 * @param ShareWrapper $shareWrapper
	 */
	public function delete(ShareWrapper $shareWrapper): void {
		$this->shareWrapperRequest->delete((int)$shareWrapper->getId());
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
	 * @param int $offset
	 * @param int $limit
	 * @param bool $getData
	 *
	 * @return ShareWrapper[]
	 * @throws RequestBuilderException
	 */
	public function getSharedWith(
		FederatedUser $federatedUser,
		int $nodeId,
		int $offset,
		int $limit,
		bool $getData = false
	): array {
		return $this->shareWrapperRequest->getSharedWith($federatedUser, $nodeId, $offset, $limit, $getData);
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
		int $offset,
		int $limit,
		bool $getData = false,
		bool $completeDetails = false
	): array {
		return $this->shareWrapperRequest->getSharesBy(
			$federatedUser, $nodeId, $reshares, $offset, $limit, $getData, $completeDetails
		);
	}


	/**
	 * @param FederatedUser $federatedUser
	 * @param int $nodeId
	 * @param bool $reshares
	 *
	 * @return ShareWrapper[]
	 * @throws RequestBuilderException
	 */
	public function getSharesInFolder(FederatedUser $federatedUser, int $nodeId, bool $reshares): array {
		return $this->shareWrapperRequest->getSharesInFolder($federatedUser, $nodeId, $reshares);
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
		$share->setSharedWith($federatedUser->getSingleId());
		$childId = $this->shareWrapperRequest->save($share, (int)$share->getId());

		return $this->getShareById($childId, $federatedUser);
	}
}
