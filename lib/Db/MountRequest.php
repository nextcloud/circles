<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Db;

use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Mount;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class MountRequest
 *
 * @package OCA\Circles\Db
 */
class MountRequest extends MountRequestBuilder {
	use TStringTools;


	/**
	 * @param Mount $mount
	 */
	public function save(Mount $mount): void {
		$qb = $this->getMountInsertSql();
		$qb->setValue('circle_id', $qb->createNamedParameter($mount->getCircleId()))
			->setValue('mount_id', $qb->createNamedParameter($mount->getMountId()))
			->setValue('single_id', $qb->createNamedParameter($mount->getOwner()->getSingleId()))
			->setValue('token', $qb->createNamedParameter($mount->getToken()))
			->setValue('parent', $qb->createNamedParameter($mount->getParent()))
			->setValue('mountpoint', $qb->createNamedParameter($mount->getOriginalMountPoint()))
			->setValue('mountpoint_hash', $qb->createNamedParameter(md5($mount->getOriginalMountPoint())))
			->setValue('remote', $qb->createNamedParameter($mount->getRemote()))
			->setValue('remote_id', $qb->createNamedParameter($mount->getRemoteShareId(), IQueryBuilder::PARAM_INT));

		$qb->execute();
	}


	/**
	 * @param string $token
	 */
	public function delete(string $token): void {
		$qb = $this->getMountDeleteSql();
		$qb->limitToToken($token);

		$qb->execute();
	}


	/**
	 * @param IFederatedUser $federatedUser
	 *
	 * @return Mount[]
	 * @throws RequestBuilderException
	 */
	public function getForUser(IFederatedUser $federatedUser): array {
		$qb = $this->getMountSelectSql();
		$qb->setOptions([CoreQueryBuilder::MOUNT], ['getData' => true]);
		$qb->leftJoinMember(CoreQueryBuilder::MOUNT);
		$qb->leftJoinMountpoint(CoreQueryBuilder::MOUNT, $federatedUser);
		$qb->limitToInitiator(CoreQueryBuilder::MOUNT, $federatedUser, 'circle_id');

		return $this->getItemsFromRequest($qb);

		//		FederatedUser $federatedUser,
		//		int $nodeId,
		//		int $offset,
		//		int $limit,
		//		bool $getData = false
		//	): array {
		//			$qb = $this->getShareSelectSql();
		//			$qb->setOptions([CoreRequestBuilder::SHARE], ['getData' => $getData]);
		//			if ($getData) {
		//				$qb->leftJoinCircle(CoreRequestBuilder::SHARE, null, 'share_with');
		//			}
		//
		//			$qb->limitToInitiator(CoreRequestBuilder::SHARE, $federatedUser, 'share_with');
		//
		//			$qb->leftJoinFileCache(CoreRequestBuilder::SHARE);
		//			$qb->limitToDBFieldEmpty('parent', true);
		//			$qb->leftJoinShareChild(CoreRequestBuilder::SHARE);
		//
		//			if ($nodeId > 0) {
		//				$qb->limitToFileSource($nodeId);
		//			}
		//
		//			$qb->chunk($offset, $limit);
		//
		//			return $this->getItemsFromRequest($qb);

		//			$this->joinMembership($qb, $userId);
		//		$this->leftJoinMountPoint($qb, $userId);

		//		$shares = [];
		//		$cursor = $qb->execute();
		//		while ($data = $cursor->fetch()) {
		//			$shares[] = $this->parseGSSharesSelectSql($data);
		//		}
		//		$cursor->closeCursor();
		//
		//		return $shares;
	}
	//	/**
	//	 * @param string $userId
	//	 *
	//	 * @return Mount[]
	//	 */
	//	public function getForUser(string $userId): array {
	//		$qb = $this->getMountSelectSql();
	//
	//		$this->
	//		$this->joinMembership($qb, $userId);
	//		$this->leftJoinMountPoint($qb, $userId);
	//
	//		$shares = [];
	//		$cursor = $qb->execute();
	//		while ($data = $cursor->fetch()) {
	//			$shares[] = $this->parseGSSharesSelectSql($data);
	//		}
	//		$cursor->closeCursor();
	//
	//		return $shares;
	//	}

	//
	//	/**
	//	 * @param DeprecatedMember $member
	//	 */
	//	public function removeGSSharesFromMember(DeprecatedMember $member) {
	//		$qb = $this->getMountDeleteSql();
	//		$this->limitToCircleId($qb, $member->getCircleId());
	//		$this->limitToInstance($qb, $member->getInstance());
	//		$this->limitToOwner($qb, $member->getUserId());
	//
	//		$qb->execute();
	//	}
	//
	//
	//	/**
	//	 * @param IQueryBuilder $qb
	//	 * @param string $userId
	//	 */
	//	private function joinMembership(IQueryBuilder $qb, string $userId) {
	//		$qb->from(DeprecatedRequestBuilder::TABLE_MEMBERS, 'm');
	//
	//		$expr = $qb->expr();
	//		$andX = $expr->andX();
	//
	//		$andX->add($expr->eq('m.user_id', $qb->createNamedParameter($userId)));
	//		$andX->add($expr->eq('m.instance', $qb->createNamedParameter('')));
	//		$andX->add($expr->gt('m.level', $qb->createNamedParameter(0)));
	//		$andX->add($expr->eq('m.user_type', $qb->createNamedParameter(DeprecatedMember::TYPE_USER)));
	//		$andX->add($expr->eq('m.circle_id', 'gsh.circle_id'));
	//
	//		$qb->andWhere($andX);
	//	}
	//
	//
	//	private function leftJoinMountPoint(IQueryBuilder $qb, string $userId) {
	//		$expr = $qb->expr();
	//		$pf = '' . $this->default_select_alias . '.';
	//
	//		$on = $expr->andX();
	//		$on->add($expr->eq('mp.user_id', $qb->createNamedParameter($userId)));
	//		$on->add($expr->eq('mp.share_id', $pf . 'id'));
	//
	//		/** @noinspection PhpMethodParametersCountMismatchInspection */
	//		$qb->selectAlias('mp.mountPoint', 'gsshares_mountpoint')
	//		   ->leftJoin($this->default_select_alias, DeprecatedRequestBuilder::TABLE_GSSHARES_MOUNTPOINT, 'mp', $on);
	//	}
	//
	//
	//	/**
	//	 * @param string $userId
	//	 * @param string $target
	//	 *
	//	 * @return GSShareMountpoint
	//	 * @throws ShareNotFound
	//	 */
	//	public function getShareMountPointByPath(string $userId, string $target): GSShareMountpoint {
	//		$qb = $this->getMountMountpointSelectSql();
	//
	//		$targetHash = md5($target);
	//		$this->limitToUserId($qb, $userId);
	//		$this->limitToMountpointHash($qb, $targetHash);
	//
	//		$shares = [];
	//		$cursor = $qb->execute();
	//		$data = $cursor->fetch();
	//
	//		if ($data === false) {
	//			throw new ShareNotFound();
	//		}
	//
	//		return $this->parseGSSharesMountpointSelectSql($data);
	//	}
	//
	//
	//	/**
	//	 * @param int $gsShareId
	//	 * @param string $userId
	//	 *
	//	 * @return GSShareMountpoint
	//	 * @throws ShareNotFound
	//	 */
	//	public function getShareMountPointById(int $gsShareId, string $userId): GSShareMountpoint {
	//		$qb = $this->getMountMountpointSelectSql();
	//
	//		$this->limitToShareId($qb, $gsShareId);
	//		$this->limitToUserId($qb, $userId);
	//
	//		$shares = [];
	//		$cursor = $qb->execute();
	//		$data = $cursor->fetch();
	//		if ($data === false) {
	//			throw new ShareNotFound();
	//		}
	//
	//		return $this->parseGSSharesMountpointSelectSql($data);
	//	}
	//
	//
	//	/**
	//	 * @param GSShareMountpoint $mountpoint
	//	 */
	//	public function generateShareMountPoint(GSShareMountpoint $mountpoint) {
	//		$qb = $this->getMountMountpointInsertSql();
	//
	//		$hash = ($mountpoint->getMountPoint() === '-') ? '' : md5($mountpoint->getMountPoint());
	//
	//		$qb->setValue('user_id', $qb->createNamedParameter($mountpoint->getUserId()))
	//		   ->setValue('share_id', $qb->createNamedParameter($mountpoint->getShareId()))
	//		   ->setValue('mountpoint', $qb->createNamedParameter($mountpoint->getMountPoint()))
	//		   ->setValue('mountpoint_hash', $qb->createNamedParameter($hash));
	//		$qb->execute();
	//	}
	//
	//
	//	/**
	//	 * @param GSShareMountpoint $mountpoint
	//	 *
	//	 * @return bool
	//	 */
	//	public function updateShareMountPoint(GSShareMountpoint $mountpoint) {
	//		$qb = $this->getMountMountpointUpdateSql();
	//
	//		$hash = ($mountpoint->getMountPoint() === '-') ? '' : md5($mountpoint->getMountPoint());
	//
	//		$qb->set('mountpoint', $qb->createNamedParameter($mountpoint->getMountPoint()))
	//		   ->set('mountpoint_hash', $qb->createNamedParameter($hash));
	//
	//		$this->limitToShareId($qb, $mountpoint->getShareId());
	//		$this->limitToUserId($qb, $mountpoint->getUserId());
	//		$nb = $qb->execute();
	//
	//		return ($nb === 1);
	//	}
	//
}
