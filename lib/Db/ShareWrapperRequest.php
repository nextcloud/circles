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


namespace OCA\Circles\Db;


use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\ShareWrapper;
use OCP\Files\NotFoundException;
use OCP\Share\Exceptions\IllegalIDChangeException;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IShare;


/**
 * Class ShareWrapperRequest
 *
 * @package OCA\Circles\Db
 */
class ShareWrapperRequest extends ShareWrapperRequestBuilder {


	/**
	 * @param IShare $share
	 *
	 * @throws NotFoundException
	 */
	public function save(IShare $share): void {
//		$hasher = \OC::$server->getHasher();
//		$password = ($share->getPassword() !== null) ? $hasher->hash($share->getPassword()) : '';
		$password = '';

		$qb = $this->getShareInsertSql();
		$qb->setValue('share_type', $qb->createNamedParameter($share->getShareType()))
		   ->setValue('item_type', $qb->createNamedParameter($share->getNodeType()))
		   ->setValue('item_source', $qb->createNamedParameter($share->getNodeId()))
		   ->setValue('file_source', $qb->createNamedParameter($share->getNodeId()))
		   ->setValue('file_target', $qb->createNamedParameter($share->getTarget()))
		   ->setValue('share_with', $qb->createNamedParameter($share->getSharedWith()))
		   ->setValue('uid_owner', $qb->createNamedParameter($share->getShareOwner()))
		   ->setValue('uid_initiator', $qb->createNamedParameter($share->getSharedBy()))
		   ->setValue('accepted', $qb->createNamedParameter(IShare::STATUS_ACCEPTED))
		   ->setValue('password', $qb->createNamedParameter($password))
		   ->setValue('permissions', $qb->createNamedParameter($share->getPermissions()))
		   ->setValue('token', $qb->createNamedParameter($share->getToken()))
		   ->setValue('stime', $qb->createFunction('UNIX_TIMESTAMP()'));

		$qb->execute();
		$id = $qb->getLastInsertId();
		try {
			$share->setId($id);
		} catch (IllegalIDChangeException $e) {
		}
	}


	/**
	 * @param Circle $circle
	 */
	public function update(Circle $circle) {
	}


	/**
	 * @return array
	 */
	public function getShares(): array {
		$qb = $this->getShareSelectSql();

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param string $shareId
	 * @param string|null $recipientId
	 *
	 * @return ShareWrapper
	 * @throws ShareNotFound
	 */
	public function getShareById(string $shareId, ?string $recipientId): ShareWrapper {
		$qb = $this->getShareSelectSql();
		$qb->limitToIdString($shareId);

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @param int $fileId
	 *
	 * @return ShareWrapper[]
	 */
	public function getSharesByFileId(int $fileId): array {
		$qb = $this->getShareSelectSql();
		$qb->limitToFileSource($fileId);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param FederatedUser $federatedUser
	 * @param int $nodeId
	 * @param int $offset
	 * @param int $limit
	 *
	 * @return ShareWrapper[]
	 * @throws RequestBuilderException
	 */
	public function getSharedWith(FederatedUser $federatedUser, int $nodeId, int $offset, int $limit): array {
		$qb = $this->getShareSelectSql();

		$qb->setOptions([CoreRequestBuilder::SHARE], ['getData' => false]);

		$qb->leftJoinFileCache(CoreRequestBuilder::SHARE);
		$qb->limitToMembership(CoreRequestBuilder::SHARE, $federatedUser, 'share_with');
//		$qb->leftJoinShareParent(CoreRequestBuilder::SHARE);

		if ($nodeId > 0) {
			$qb->limitToFileSource($nodeId);
		}

		$qb->chunk($offset, $limit);

//
//		$this->linkToMember($qb, $userId, false, 'c');
//
//		$shares = [];
//		$cursor = $qb->execute();
//		while ($data = $cursor->fetch()) {
//			self::editShareFromParentEntry($data);
//			if (self::isAccessibleResult($data)) {
//				$shares[] = $this->createShareObject($data);
//			}
//		}
//		$cursor->closeCursor();
//
//		return $shares;
//		$qb = $this->getShareSelectSql();
//		$qb->limitToDBFieldInt('file_source', $nodeId);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * returns the SQL request to get a specific share from the fileId and circleId
	 *
	 * @param string $circleId
	 * @param int $fileId
	 *
	 * @return ShareWrapper
	 * @throws ShareNotFound
	 */
	public function searchShare(string $circleId, int $fileId): ShareWrapper {
		$qb = $this->getShareSelectSql();
		$qb->limitToDBFieldEmpty('parent', true);
		$qb->limitToShareWith($circleId);
		$qb->limitToFileSource($fileId);

		return $this->getItemFromRequest($qb);
	}

}

