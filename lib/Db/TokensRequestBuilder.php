<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Db;

use OCA\Circles\Model\SharesToken;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * @deprecated
 * Class TokensRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class TokensRequestBuilder extends DeprecatedRequestBuilder {
	use TArrayTools;


	/**
	 * Base of the Sql Insert request for Shares
	 *
	 * @return IQueryBuilder
	 */
	protected function getTokensInsertSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert(self::TABLE_TOKENS);

		return $qb;
	}


	/**
	 * Base of the Sql Update request for Groups
	 *
	 * @return IQueryBuilder
	 */
	protected function getTokensUpdateSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->update(self::TABLE_TOKENS);

		return $qb;
	}


	/**
	 * @return IQueryBuilder
	 */
	protected function getTokensSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select('t.user_id', 't.circle_id', 't.member_id', 't.share_id', 't.token', 't.accepted')
			->from(self::TABLE_TOKENS, 't');

		$this->default_select_alias = 't';

		return $qb;
	}


	/**
	 * Base of the Sql Delete request
	 *
	 * @return IQueryBuilder
	 */
	protected function getTokensDeleteSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete(self::TABLE_TOKENS);

		return $qb;
	}


	/**
	 * @param array $data
	 *
	 * @return SharesToken
	 */
	protected function parseTokensSelectSql($data) {
		$sharesToken = new SharesToken();
		$sharesToken->import($data);

		return $sharesToken;
	}
}
