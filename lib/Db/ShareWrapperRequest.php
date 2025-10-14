<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Db;

use JsonException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\ShareWrapperNotFoundException;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Membership;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Model\ShareWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\Share\Exceptions\IllegalIDChangeException;
use OCP\Share\IAttributes;
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
		$qb = $this->getShareInsertSql();
		$qb->setValue('attributes', $qb->createNamedParameter($this->formatShareAttributes($share->getAttributes())))
			->setValue('share_type', $qb->createNamedParameter($share->getShareType()))
			->setValue('mail_send', $qb->createNamedParameter($share->getMailSend()))
			->setValue('note', $qb->createNamedParameter($share->getNote()))
			->setValue('item_type', $qb->createNamedParameter($share->getNodeType()))
			->setValue('item_source', $qb->createNamedParameter($share->getNodeId()))
			->setValue('file_source', $qb->createNamedParameter($share->getNodeId()))
			->setValue('file_target', $qb->createNamedParameter($share->getTarget()))
			->setValue('share_with', $qb->createNamedParameter($share->getSharedWith()))
			->setValue('uid_owner', $qb->createNamedParameter($share->getShareOwner()))
			->setValue('uid_initiator', $qb->createNamedParameter($share->getSharedBy()))
			->setValue('expiration', $qb->createNamedParameter($share->getExpirationDate(), IQueryBuilder::PARAM_DATE))
			->setValue('accepted', $qb->createNamedParameter(IShare::STATUS_ACCEPTED))
			->setValue('password', $qb->createNamedParameter(''))
			->setValue('permissions', $qb->createNamedParameter($share->getPermissions()))
			->setValue('token', $qb->createNamedParameter($share->getToken()))
			->setValue('stime', (string)$qb->createFunction('UNIX_TIMESTAMP()'));

		if ($parentId > 0) {
			$qb->setValue('parent', $qb->createNamedParameter($parentId));
		}

		$qb->execute();
		$id = $qb->getLastInsertId();
		try {
			$share->setId((string)$id);
		} catch (IllegalIDChangeException $e) {
		}

		return $id;
	}


	/**
	 * @param ShareWrapper $shareWrapper
	 */
	public function update(ShareWrapper $shareWrapper): void {
		$qb = $this->getShareUpdateSql();
		$shareAttributes = $this->formatShareAttributes($shareWrapper->getAttributes());

		$qb->set('file_target', $qb->createNamedParameter($shareWrapper->getFileTarget()))
			->set('share_with', $qb->createNamedParameter($shareWrapper->getSharedWith()))
			->set('uid_owner', $qb->createNamedParameter($shareWrapper->getShareOwner()))
			->set('uid_initiator', $qb->createNamedParameter($shareWrapper->getSharedBy()))
			->set('accepted', $qb->createNamedParameter(IShare::STATUS_ACCEPTED))
			->set('permissions', $qb->createNamedParameter($shareWrapper->getPermissions()))
			->set('note', $qb->createNamedParameter($shareWrapper->getShareNote()))
			->set('expiration', $qb->createNamedParameter($shareWrapper->getExpirationDate(), IQueryBuilder::PARAM_DATE))
			->set('attributes', $qb->createNamedParameter($shareAttributes));

		$qb->limitToId((int)$shareWrapper->getId());

		$qb->execute();
	}

	/**
	 * update permissions and attributes from child
	 */
	public function updateChildPermissions(ShareWrapper $shareWrapper): void {
		$qb = $this->getShareUpdateSql();
		$shareAttributes = $this->formatShareAttributes($shareWrapper->getAttributes());

		$qb->set('permissions', $qb->createNamedParameter($shareWrapper->getPermissions()))
			->set('attributes', $qb->createNamedParameter($shareAttributes));

		$qb->limitToShareParent((int)$shareWrapper->getId());
		$qb->gt('permissions', 0);

		$qb->execute();
	}


	/**
	 * @param Membership $membership
	 */
	public function deleteByMembership(Membership $membership) {
		$qb = $this->getShareDeleteSql();
		$qb->limitToShareWith($membership->getCircleId());
		$qb->limit('uid_initiator', $membership->getSingleId());

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
		bool $completeDetails = false,
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
		}

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
	public function getSharesToCircles(array $circleIds): array {
		$qb = $this->getShareSelectSql();
		$qb->limitNull('parent', false);
		$qb->expr()->in('share_with', $qb->createNamedParameter($circleIds, IQueryBuilder::PARAM_STR_ARRAY));
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
		}

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * returns all share, related to a list of fileids.
	 * if $getData is true, will return details about the recipient circle.
	 *
	 * @param array $fileIds
	 * @param bool $getData
	 *
	 * @return ShareWrapper[]
	 * @throws RequestBuilderException
	 */
	public function getSharesByFileIds(array $fileIds, bool $getData = false, bool $getChild = false): array {
		$qb = $this->getShareSelectSql();
		$qb->limitToFileSourceArray($fileIds);

		if ($getData) {
			$qb->setOptions([CoreQueryBuilder::SHARE], ['getData' => $getData]);
			$qb->leftJoinCircle(CoreQueryBuilder::SHARE, null, 'share_with');
		}

		if ($getChild) {
			$qb->orderBy('parent', 'asc');
		} else {
			$qb->limitNull('parent', false);
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
		CircleProbe $probe,
	): array {
		$qb = $this->getShareSelectSql();
		$qb->setOptions(
			[CoreQueryBuilder::SHARE],
			array_merge(
				$probe->getAsOptions(),
				['getData' => true]
			)
		);

		$qb->leftJoinCircle(CoreQueryBuilder::SHARE, null, 'share_with');

		$aliasCircle = $qb->generateAlias(CoreQueryBuilder::SHARE, CoreQueryBuilder::CIRCLE);
		$qb->limitToFederatedUserMemberships(CoreQueryBuilder::SHARE, $aliasCircle, $federatedUser);

		$qb->leftJoinFileCache(CoreQueryBuilder::SHARE);
		$qb->limitNull('parent', false);
		$qb->leftJoinShareChild(CoreQueryBuilder::SHARE);

		if ($nodeId > 0) {
			$qb->limitToFileSource($nodeId);
		}

		$qb->chunk($probe->getItemsOffset(), $probe->getItemsLimit());

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
		int $limit,
		int $offset,
		bool $getData = false,
		bool $completeDetails = false,
	): array {
		$qb = $this->getShareSelectSql();
		$qb->setOptions([CoreQueryBuilder::SHARE], ['getData' => $getData]);
		$qb->leftJoinCircle(CoreQueryBuilder::SHARE, null, 'share_with');

		$qb->limitToShareOwner(CoreQueryBuilder::SHARE, $federatedUser, $reshares, $nodeId);
		$qb->limitNull('parent', false);

		if ($nodeId > 0) {
			$qb->limitToFileSource($nodeId);
		}

		if ($completeDetails) {
			$aliasMembership = $qb->generateAlias(CoreQueryBuilder::SHARE, CoreQueryBuilder::MEMBERSHIPS);
			$qb->leftJoinInheritedMembers(CoreQueryBuilder::SHARE, 'share_with');
			$qb->leftJoinFileCache(CoreQueryBuilder::SHARE);
			$qb->leftJoinShareChild(CoreQueryBuilder::SHARE, $aliasMembership);
		}

		$qb->chunk($offset, $limit);

		return $this->getItemsFromRequest($qb);
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
	public function getSharesInFolder(
		FederatedUser $federatedUser,
		Folder $node,
		bool $reshares,
		bool $shallow = true,
	): array {
		$qb = $this->getShareSelectSql();

		$qb->leftJoinCircle(CoreQueryBuilder::SHARE, null, 'share_with');
		$qb->limitToShareOwner(CoreQueryBuilder::SHARE, $federatedUser, $reshares);
		$qb->leftJoinFileCache(CoreQueryBuilder::SHARE);

		$aliasFileCache = $qb->generateAlias(CoreQueryBuilder::SHARE, CoreQueryBuilder::FILE_CACHE);
		if ($shallow) {
			$qb->limitInt('parent', $node->getId(), $aliasFileCache);
		} else {
			$qb->like('path', $node->getInternalPath() . '/%', $aliasFileCache);
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

	/**
	 * @param string $circleId
	 * @param string $initiator
	 */
	public function deleteSharesToCircle(string $circleId, string $initiator = ''): void {
		$qb = $this->getShareSelectSql();
		$qb->limit('share_with', $circleId);
		if ($initiator !== '') {
			$qb->limit('uid_initiator', $initiator);
		}

		$ids = array_map(
			function (ShareWrapper $share): string {
				return $share->getId();
			},
			$this->getItemsFromRequest($qb)
		);

		$this->deleteSharesAndChild($ids);
	}


	public function removeOrphanShares(): void {
		$qb = $this->getShareSelectSql();
		$expr = $qb->expr();
		$qb->leftJoin(
			CoreQueryBuilder::SHARE, CoreRequestBuilder::TABLE_SHARE, 'p',
			$expr->andX($expr->eq('p.id', CoreQueryBuilder::SHARE . '.parent'))
		);

		$qb->filterNull('parent');
		$qb->limitNull('id', false, 'p');

		$ids = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$ids[] = $data['id'];
		}
		$cursor->closeCursor();

		$this->deleteSharesAndChild($ids);
	}


	/**
	 * @param array $ids
	 */
	private function deleteSharesAndChild(array $ids): void {
		$qb = $this->getShareDeleteSql();
		$qb->andWhere(
			$qb->expr()->orX(
				$qb->exprLimitInArray('id', $ids),
				$qb->exprLimitInArray('parent', $ids)
			)
		);

		$qb->execute();
	}


	/**
	 * Format IAttributes to database format (JSON string)
	 * based on OC\Share20\DefaultShareProvider::formatShareAttributes();
	 */
	private function formatShareAttributes(?IAttributes $attributes): ?string {
		if (empty($attributes?->toArray())) {
			return null;
		}

		$compressedAttributes = [];
		foreach ($attributes->toArray() as $attribute) {
			$compressedAttributes[] = [
				$attribute['scope'],
				$attribute['key'],
				$attribute['value']
			];
		}

		try {
			return json_encode($compressedAttributes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
		} catch (JsonException $e) {
			return null;
		}
	}
}
