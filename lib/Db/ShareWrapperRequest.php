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
use OCA\Circles\Exceptions\ShareWrapperNotFoundException;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Membership;
use OCA\Circles\Model\ShareWrapper;
use OCP\Files\NotFoundException;
use OCP\Share\Exceptions\IllegalIDChangeException;
use OCP\Share\IShare;

/**
 * Class ShareWrapperRequest
 *
 * @package OCA\Circles\Db
 */
class ShareWrapperRequest extends ShareWrapperRequestBuilder {


	/**
	 * @param IShare $share
	 * @param int $parentId
	 *
	 * @return int
	 * @throws NotFoundException
	 */
	public function save(IShare $share, int $parentId = 0): int {
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

		if ($parentId > 0) {
			$qb->setValue('parent', $qb->createNamedParameter($parentId));
		}

		$qb->execute();
		$id = $qb->getLastInsertId();
		try {
			$share->setId($id);
		} catch (IllegalIDChangeException $e) {
		}

		return $id;
	}


	/**
	 * @param ShareWrapper $shareWrapper
	 */
	public function update(ShareWrapper $shareWrapper): void {
		$qb = $this->getShareUpdateSql();
		$qb->set('file_target', $qb->createNamedParameter($shareWrapper->getFileTarget()))
		   ->set('share_with', $qb->createNamedParameter($shareWrapper->getSharedWith()))
		   ->set('uid_owner', $qb->createNamedParameter($shareWrapper->getShareOwner()))
		   ->set('uid_initiator', $qb->createNamedParameter($shareWrapper->getSharedBy()))
		   ->set('accepted', $qb->createNamedParameter(IShare::STATUS_ACCEPTED))
		   ->set('permissions', $qb->createNamedParameter($shareWrapper->getPermissions()));

		$qb->limitToId((int)$shareWrapper->getId());

		$qb->execute();
	}


	/**
	 * @param Membership $membership
	 */
	public function removeByMembership(Membership $membership) {
		$qb = $this->getShareDeleteSql();
		$qb->limitToShareWith($membership->getCircleId());
		$qb->limit('uid_initiator', $membership->getSingleId());

		$qb->execute();
	}


	/**
	 * @param string $initiator
	 * @param string $shareWith
	 *
	 * @deprecated in NC30 when initiator uses FederatedUser - then, we can use removeByMembership()
	 */
	public function removeByInitiatorAndShareWith(string $initiator, string $shareWith) {
		$qb = $this->getShareDeleteSql();
		$qb->limitToShareWith($shareWith);
		$qb->limit('uid_initiator', $initiator);

		$qb->execute();
	}


	/**
	 * @return array
	 */
	public function getShares(): array {
		$qb = $this->getShareSelectSql();

		return $this->getItemsFromRequest($qb);
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
		$qb = $this->getShareSelectSql();
		$qb->limitNull('parent', false);
		$qb->setOptions([CoreQueryBuilder::SHARE], ['getData' => true]);

		$qb->leftJoinCircle(CoreQueryBuilder::SHARE, null, 'share_with');

		// TODO: filter direct-shares ?
		$aliasUpstreamMembership =
			$qb->generateAlias(CoreQueryBuilder::SHARE, CoreQueryBuilder::UPSTREAM_MEMBERSHIPS);
		$qb->limitToInheritedMemberships(CoreQueryBuilder::SHARE, $circleId, 'share_with');

//		if (!is_null($shareRecipient)) {
//			$qb->limitToInitiator(CoreRequestBuilder::SHARE, $shareRecipient, 'share_with');
//		}

		// TODO: add shareInitiator and shareRecipient to filter the request
		if (!is_null($shareRecipient) || $completeDetails) {
			$qb->leftJoinInheritedMembers(
				$aliasUpstreamMembership,
				'circle_id',
				$qb->generateAlias(CoreQueryBuilder::SHARE, CoreQueryBuilder::INHERITED_BY)
			);

			$aliasMembership = $qb->generateAlias($aliasUpstreamMembership, CoreQueryBuilder::MEMBERSHIPS);
			$qb->leftJoinFileCache(CoreQueryBuilder::SHARE);
			$qb->leftJoinShareChild(CoreQueryBuilder::SHARE, $aliasMembership);

			$qb->generateGroupBy(
				self::$tables[self::TABLE_MEMBERSHIP],
				$aliasMembership,
				true
			);
		}

		return $this->getItemsFromRequest($qb);
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
		$qb = $this->getShareSelectSql();

		$qb->setOptions([CoreQueryBuilder::SHARE], ['getData' => true]);
		$qb->leftJoinCircle(CoreQueryBuilder::SHARE, null, 'share_with');
		$qb->limitToId($shareId);

		if (!is_null($federatedUser)) {
			$qb->limitToInitiator(CoreQueryBuilder::SHARE, $federatedUser, 'share_with');
			$qb->leftJoinShareChild(CoreQueryBuilder::SHARE);
		}

		return $this->getItemFromRequest($qb);
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
		$qb = $this->getShareSelectSql();

		$qb->setOptions([CoreQueryBuilder::SHARE], ['getData' => true]);
		$qb->leftJoinCircle(CoreQueryBuilder::SHARE, null, 'share_with');
		$qb->limitToShareToken(CoreQueryBuilder::SHARE, $token);

		if (!is_null($federatedUser)) {
			$qb->limitToInitiator(CoreQueryBuilder::SHARE, $federatedUser, 'share_with');
			$qb->leftJoinShareChild(CoreQueryBuilder::SHARE);
		}

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @param FederatedUser $federatedUser
	 * @param int $shareId
	 *
	 * @return ShareWrapper
	 * @throws ShareWrapperNotFoundException
	 */
	public function getChild(FederatedUser $federatedUser, int $shareId): ShareWrapper {
		$qb = $this->getShareSelectSql();
		$qb->limitToShareParent($shareId);
		$qb->limitToShareWith($federatedUser->getSingleId());

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @param int $fileId
	 * @param bool $getData
	 *
	 * @return ShareWrapper[]
	 * @throws RequestBuilderException
	 */
	public function getSharesByFileId(int $fileId, bool $getData = false): array {
		$qb = $this->getShareSelectSql();
		$qb->limitToFileSource($fileId);

		if ($getData) {
			$qb->setOptions([CoreQueryBuilder::SHARE], ['getData' => $getData]);
			$qb->leftJoinCircle(CoreQueryBuilder::SHARE, null, 'share_with');
//			$qb->leftJoinFileCache(CoreRequestBuilder::SHARE);
			$qb->limitNull('parent', false);

			$aliasMembership = $qb->generateAlias(CoreQueryBuilder::SHARE, CoreQueryBuilder::MEMBERSHIPS);
			$qb->leftJoinInheritedMembers(CoreQueryBuilder::SHARE, 'share_with');
			$qb->leftJoinShareChild(CoreQueryBuilder::SHARE);

			$qb->generateGroupBy(
				self::$tables[self::TABLE_MEMBERSHIP],
				$aliasMembership,
				true
			);
		}

		return $this->getItemsFromRequest($qb);
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
		$qb = $this->getShareSelectSql();
		$qb->setOptions([CoreQueryBuilder::SHARE], ['getData' => $getData, 'filterPersonalCircle' => false]);
		if ($getData) {
			$qb->leftJoinCircle(CoreQueryBuilder::SHARE, null, 'share_with');
		}

		$qb->limitToInitiator(CoreQueryBuilder::SHARE, $federatedUser, 'share_with');

		$qb->leftJoinFileCache(CoreQueryBuilder::SHARE);
		$qb->limitNull('parent', false);
		$qb->leftJoinShareChild(CoreQueryBuilder::SHARE);

		if ($nodeId > 0) {
			$qb->limitToFileSource($nodeId);
		}

		$qb->chunk($offset, $limit);

		return $this->getItemsFromRequest($qb);
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
		$qb = $this->getShareSelectSql();
		$qb->setOptions([CoreQueryBuilder::SHARE], ['getData' => $getData]);
		$qb->leftJoinCircle(CoreQueryBuilder::SHARE, null, 'share_with');

		$qb->limitToShareOwner(CoreQueryBuilder::SHARE, $federatedUser, $reshares);
		$qb->limitNull('parent', false);

		if ($nodeId > 0) {
			$qb->limitToFileSource($nodeId);
		}

		if ($completeDetails) {
			$aliasMembership = $qb->generateAlias(CoreQueryBuilder::SHARE, CoreQueryBuilder::MEMBERSHIPS);
			$qb->leftJoinInheritedMembers(CoreQueryBuilder::SHARE, 'share_with');
			$qb->leftJoinFileCache(CoreQueryBuilder::SHARE);
			$qb->leftJoinShareChild(CoreQueryBuilder::SHARE, $aliasMembership);
			$qb->generateGroupBy(
				self::$tables[self::TABLE_MEMBERSHIP],
				$aliasMembership,
				true
			);
		}

		$qb->chunk($offset, $limit);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param FederatedUser $federatedUser
	 * @param int $nodeId
	 * @param bool $reshares
	 *
	 * @return ShareWrapper[]
	 * @throws RequestBuilderException
	 */
	public function getSharesInFolder(
		FederatedUser $federatedUser,
		int $nodeId,
		bool $reshares
	): array {
		$qb = $this->getShareSelectSql();

		$qb->leftJoinCircle(CoreQueryBuilder::SHARE, null, 'share_with');
		$qb->limitToShareOwner(CoreQueryBuilder::SHARE, $federatedUser, $reshares);
		$qb->leftJoinFileCache(CoreQueryBuilder::SHARE);
		if ($nodeId > 0) {
			$aliasFileCache = $qb->generateAlias(CoreQueryBuilder::SHARE, CoreQueryBuilder::FILE_CACHE);
			$qb->limitInt('parent', $nodeId, $aliasFileCache);
		}
		$qb->limitNull('parent', false);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * returns the SQL request to get a specific share from the fileId and circleId
	 *
	 * @param string $singleId
	 * @param int $fileId
	 *
	 * @return ShareWrapper
	 * @throws ShareWrapperNotFoundException
	 * @throws RequestBuilderException
	 */
	public function searchShare(string $singleId, int $fileId): ShareWrapper {
		$qb = $this->getShareSelectSql();

		$qb->setOptions([CoreQueryBuilder::SHARE], ['getData' => true]);
		$qb->leftJoinCircle(CoreQueryBuilder::SHARE, null, 'share_with');

		$qb->limitNull('parent', false);
		$qb->limitToShareWith($singleId);
		$qb->limitToFileSource($fileId);

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @param int $shareId
	 */
	public function delete(int $shareId): void {
		$qb = $this->getShareDeleteSql();

		$qb->andWhere(
			$qb->expr()->orX(
				$qb->exprLimitInt('id', $shareId),
				$qb->exprLimitInt('parent', $shareId),
			)
		);

		$qb->execute();
	}
}
