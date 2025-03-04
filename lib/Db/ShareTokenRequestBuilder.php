<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Db;

use OCA\Circles\Exceptions\ShareTokenNotFoundException;
use OCA\Circles\Model\ShareToken;
use OCA\Circles\Tools\Exceptions\RowNotFoundException;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class ShareTokenRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class ShareTokenRequestBuilder extends CoreRequestBuilder {
	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getTokenInsertSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_TOKEN);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getTokenUpdateSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_TOKEN);

		return $qb;
	}


	/**
	 * @param string $alias
	 *
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getTokenSelectSql(string $alias = CoreQueryBuilder::TOKEN): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(self::TABLE_TOKEN, self::$tables[self::TABLE_TOKEN], $alias);

		return $qb;
	}


	/**
	 * Base of the Sql Delete request
	 *
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getTokenDeleteSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_TOKEN);

		return $qb;
	}


	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return ShareToken
	 * @throws ShareTokenNotFoundException
	 */
	public function getItemFromRequest(CoreQueryBuilder $qb): ShareToken {
		/** @var ShareToken $shareToken */
		try {
			$shareToken = $qb->asItem(ShareToken::class);
		} catch (RowNotFoundException $e) {
			throw new ShareTokenNotFoundException();
		}

		return $shareToken;
	}


	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return ShareToken[]
	 */
	public function getItemsFromRequest(CoreQueryBuilder $qb): array {
		/** @var ShareToken[] $result */
		return $qb->asItems(ShareToken::class);
	}
}
