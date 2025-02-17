<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Db;

use OCA\Circles\Exceptions\FederatedShareNotFoundException;
use OCA\Circles\Model\Federated\FederatedShare;
use OCA\Circles\Tools\Exceptions\RowNotFoundException;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class ShareRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class ShareLockRequestBuilder extends CoreRequestBuilder {
	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getShareLockInsertSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_SHARE_LOCK);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getShareLockSelectSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();

		$qb->select('s.id', 's.item_id', 's.circle_id', 's.instance')
			->from(self::TABLE_SHARE_LOCK, 's')
			->setDefaultSelectAlias('s');

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getShareLockUpdateSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_SHARE_LOCK);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getShareDeleteSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_SHARE_LOCK);

		return $qb;
	}


	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return FederatedShare
	 * @throws FederatedShareNotFoundException
	 */
	public function getItemFromRequest(CoreQueryBuilder $qb): FederatedShare {
		/** @var FederatedShare $circle */
		try {
			$circle = $qb->asItem(FederatedShare::class);
		} catch (RowNotFoundException $e) {
			throw new FederatedShareNotFoundException();
		}

		return $circle;
	}

	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return FederatedShare[]
	 */
	public function getItemsFromRequest(CoreQueryBuilder $qb): array {
		/** @var FederatedShare[] $result */
		return $qb->asItems(FederatedShare::class);
	}
}
