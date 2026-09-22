<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Db;

use Doctrine\DBAL\Query\QueryBuilder;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\DB\QueryBuilder\IQueryBuilder;

class AccountsRequest extends AccountsRequestBuilder {
	use TStringTools;

	public function getAccountData(string $userId): array {
		$qb = $this->getAccountsSelectSql();

		$this->limitToDBField($qb, 'uid', $userId);

		$cursor = $qb->executeQuery();
		$data = $cursor->fetchAssociative();
		$cursor->closeCursor();

		if ($data === false) {
			return [];
		}

		return $this->parseAccountsSelectSql($data);
	}

	/**
	 * @param string $userId
	 *
	 * @deprecated
	 * @return array
	 * @throws MemberDoesNotExistException
	 */
	public function getFromUserId(string $userId): array {
		$qb = $this->getAccountsSelectSql();

		$this->limitToDBField($qb, 'uid', $userId);

		$cursor = $qb->executeQuery();
		$data = $cursor->fetchAssociative();
		$cursor->closeCursor();

		if ($data === false) {
			throw new MemberDoesNotExistException();
		}

		return $this->parseAccountsSelectSql($data);
	}

	/**
	 * @deprecated
	 * @return array
	 */
	public function getAll(): array {
		$qb = $this->getAccountsSelectSql();

		$accounts = [];
		$cursor = $qb->executeQuery();
		while ($data = $cursor->fetchAssociative()) {
			$account = $this->parseAccountsSelectSql($data);
			$accounts[$account['userId']] = $account;
		}
		$cursor->closeCursor();

		return $accounts;
	}

	/**
	 * @param IQueryBuilder $qb
	 * @param string $field
	 * @param string|integer $value
	 */
	private function limitToDBField(IQueryBuilder $qb, $field, $value): void {
		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? $this->default_select_alias . '.' : '';
		$qb->andWhere($expr->eq($pf . $field, $qb->createNamedParameter($value)));
	}
}
