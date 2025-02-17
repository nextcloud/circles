<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Db;

use OCA\Circles\Exceptions\ShareWrapperNotFoundException;
use OCA\Circles\Model\ShareWrapper;
use OCA\Circles\Tools\Exceptions\RowNotFoundException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Share\IShare;

/**
 * Class ShareWrapperRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class ShareWrapperRequestBuilder extends CoreRequestBuilder {
	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getShareInsertSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_SHARE);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getShareUpdateSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_SHARE);

		return $qb;
	}


	/**
	 * @param string $alias
	 *
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getShareSelectSql(string $alias = CoreQueryBuilder::SHARE): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(self::TABLE_SHARE, self::$outsideTables[self::TABLE_SHARE], $alias)
			->limitToShareType(IShare::TYPE_CIRCLE);

		return $qb;
	}


	/**
	 * Base of the Sql Delete request
	 *
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getShareDeleteSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_SHARE)
			->limitToShareType(IShare::TYPE_CIRCLE);

		return $qb;
	}


	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return ShareWrapper
	 * @throws ShareWrapperNotFoundException
	 */
	public function getItemFromRequest(CoreQueryBuilder $qb): ShareWrapper {
		/** @var ShareWrapper $shareWrapper */
		try {
			$shareWrapper = $qb->asItem(ShareWrapper::class);
		} catch (RowNotFoundException $e) {
			throw new ShareWrapperNotFoundException();
		}

		return $shareWrapper;
	}


	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return ShareWrapper[]
	 */
	public function getItemsFromRequest(CoreQueryBuilder $qb): array {
		/** @var ShareWrapper[] $result */
		return $qb->asItems(ShareWrapper::class);
	}
}
