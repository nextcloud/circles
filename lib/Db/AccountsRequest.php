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

use OCA\Circles\Tools\Traits\TStringTools;
use OCA\Circles\Exceptions\MemberDoesNotExistException;

class AccountsRequest extends AccountsRequestBuilder {
	use TStringTools;


	/**
	 * @param string $userId
	 *
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
