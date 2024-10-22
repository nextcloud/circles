<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Db;

use OCA\Circles\Exceptions\FederatedShareNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Model\Federated\FederatedShare;

/**
 * Class ShareRequest
 *
 * @package OCA\Circles\Db
 */
class ShareLockRequest extends ShareLockRequestBuilder {
	/**
	 * @param FederatedShare $share
	 *
	 * @throws InvalidIdException
	 */
	public function save(FederatedShare $share): void {
		$this->confirmValidIds([$share->getItemId()]);

		$qb = $this->getShareLockInsertSql();
		$qb->setValue('item_id', $qb->createNamedParameter($share->getItemId()))
			->setValue('circle_id', $qb->createNamedParameter($share->getCircleId()))
			->setValue('instance', $qb->createNamedParameter($qb->getInstance($share)));

		$qb->execute();
	}


	/**
	 * @param string $itemId
	 * @param string $circleId
	 *
	 * @return FederatedShare
	 * @throws FederatedShareNotFoundException
	 */
	public function getShare(string $itemId, string $circleId = ''): FederatedShare {
		$qb = $this->getShareLockSelectSql();

		$qb->limitToItemId($itemId);
		if ($circleId !== '') {
			$qb->limitToCircleId($circleId);
		}

		return $this->getItemFromRequest($qb);
	}
}
