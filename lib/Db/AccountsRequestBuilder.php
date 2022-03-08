<?php

/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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
