<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * @deprecated
 * Class FileSharesRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class FileSharesRequestBuilder extends DeprecatedRequestBuilder {
	/**
	 * Base of the Sql Delete request
	 *
	 * @return IQueryBuilder
	 */
	protected function getFileSharesDeleteSql(): IQueryBuilder {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete(self::TABLE_FILE_SHARES);
		$qb->where(
			$qb->expr()
				->eq('share_type', $qb->createNamedParameter(self::SHARE_TYPE))
		);

		return $qb;
	}


	/**
	 * @return IQueryBuilder
	 */
	protected function getFileSharesSelectSql(): IQueryBuilder {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select(
			's.id',
			's.share_with',
			's.file_source',
			's.uid_owner',
			's.uid_initiator',
			's.permissions',
			's.token',
			's.password',
			's.file_target'
		)
			->from(self::TABLE_FILE_SHARES, 's');

		$this->default_select_alias = 's';

		return $qb;
	}


	/**
	 * @return IQueryBuilder
	 */
	protected function getFileSharesUpdateSql(): IQueryBuilder {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->update(self::TABLE_FILE_SHARES);

		return $qb;
	}
}
