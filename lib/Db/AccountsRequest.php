<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Db;

use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Tools\Traits\TStringTools;

class AccountsRequest extends AccountsRequestBuilder {
	use TStringTools;



	public function getAccountData(string $userId): array {
		$qb = $this->getAccountsSelectSql();

		$this->limitToDBField($qb, 'uid', $userId);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
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

		$cursor = $qb->execute();
		$data = $cursor->fetch();
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
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$account = $this->parseAccountsSelectSql($data);
			$accounts[$account['userId']] = $account;
		}
		$cursor->closeCursor();

		return $accounts;
	}
}
