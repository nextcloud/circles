<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Db;

use OCA\Circles\Tools\Traits\TArrayTools;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class AccountsRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class AccountsRequestBuilder extends DeprecatedRequestBuilder {
	use TArrayTools;


	/**
	 * Base of the Sql Insert request for Accounts
	 *
	 * @return IQueryBuilder
	 */
	protected function getAccountsInsertSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert(self::NC_TABLE_ACCOUNTS);

		return $qb;
	}


	/**
	 * Base of the Sql Update request for Accounts
	 *
	 * @return IQueryBuilder
	 */
	protected function getAccountsUpdateSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->update(self::NC_TABLE_ACCOUNTS);

		return $qb;
	}


	/**
	 * @return IQueryBuilder
	 */
	protected function getAccountsSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select('a.uid', 'a.data')
			->from(self::NC_TABLE_ACCOUNTS, 'a');

		$this->default_select_alias = 'a';

		return $qb;
	}


	/**
	 * Base of the Sql Delete request
	 *
	 * @return IQueryBuilder
	 */
	protected function getAccountsDeleteSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete(self::NC_TABLE_ACCOUNTS);

		return $qb;
	}


	/**
	 * @param array $entry
	 *
	 * @return array
	 */
	protected function parseAccountsSelectSql(array $entry): array {
		$data = json_decode($entry['data'], true);
		if (!is_array($data)) {
			$data = [];
		}

		return [
			'userId' => $entry['uid'],
			'displayName' => $this->get('displayname.value', $data)
		];
	}
}
