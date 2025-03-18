<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Db;

use OCA\Circles\Model\DeprecatedMember;

/**
 * @deprecated
 * Class SharesRequest
 *
 * @package OCA\Circles\Db
 */
class FileSharesRequest extends FileSharesRequestBuilder {
	/**
	 * remove shares from a member to a circle
	 *
	 * @param DeprecatedMember $member
	 */
	public function removeSharesFromMember(DeprecatedMember $member): void {
		$qb = $this->getFileSharesDeleteSql();
		$expr = $qb->expr();

		$qb->andWhere($expr->andX(
			$expr->eq('share_type', $qb->createNamedParameter(self::SHARE_TYPE)),
			$expr->eq('share_with', $qb->createNamedParameter($member->getCircleId())),
			$expr->eq('uid_initiator', $qb->createNamedParameter($member->getUserId())),
		));

		$qb->execute();
	}


	/**
	 * @param string $circleId
	 */
	public function removeSharesToCircleId(string $circleId): void {
		$qb = $this->getFileSharesDeleteSql();
		$expr = $qb->expr();

		$qb->andWhere($expr->andX(
			$expr->eq('share_type', $qb->createNamedParameter(self::SHARE_TYPE)),
			$expr->eq('share_with', $qb->createNamedParameter($circleId)),
		));

		$qb->execute();
	}


	/**
	 * @param string $circleId
	 *
	 * @return array
	 */
	public function getSharesForCircle(string $circleId): array {
		$qb = $this->getFileSharesSelectSql();

		$this->limitToShareWith($qb, $circleId);
		$this->limitToShareType($qb, self::SHARE_TYPE);

		$shares = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$shares[] = $data;
		}
		$cursor->closeCursor();

		return $shares;
	}


	/**
	 * @return array
	 */
	public function getShares(): array {
		$qb = $this->getFileSharesSelectSql();

		$expr = $qb->expr();

		$this->limitToShareType($qb, self::SHARE_TYPE);
		$qb->andWhere($expr->isNull($this->default_select_alias . '.parent'));

		$shares = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$shares[] = $data;
		}
		$cursor->closeCursor();

		return $shares;
	}
}
