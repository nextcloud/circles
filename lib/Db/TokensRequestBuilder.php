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


use daita\MySmallPhpTools\Traits\TArrayTools;
use OCA\Circles\Model\SharesToken;
use OCP\DB\QueryBuilder\IQueryBuilder;


/**
 * Class TokensRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class TokensRequestBuilder extends CoreRequestBuilder {


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
	 * @param int $circleId
	 * @param string $groupId
	 *
	 * @return IQueryBuilder
	 */
	protected function getTokensUpdateSql($circleId, $groupId) {
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
		$qb->select('t.user_id', 't.circle_id', 't.share_id', 't.token', 't.orig_password')
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

		$orig = $this->get('orig_password', $data);
		$data['orig_password'] = ($orig === '') ? '' : $this->origPasswordDecrypt($orig);
		$sharesToken->import($data);

		return $sharesToken;
	}


	protected function origPasswordEncrypt(string $password): string {
		$key = $this->configService->getInstanceId();

		$ivlen = openssl_cipher_iv_length($cipher = 'AES-128-CBC');
		$iv = openssl_random_pseudo_bytes($ivlen);
		$raw = openssl_encrypt($password, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
		$hmac = hash_hmac('sha256', $raw, $key, $as_binary = true);

		return base64_encode($iv . $hmac . $raw);
	}


	protected function origPasswordDecrypt($encoded): string {
		$key = $this->configService->getInstanceId();
		$c = base64_decode($encoded);
		$ivlen = openssl_cipher_iv_length($cipher = 'AES-128-CBC');
		$iv = substr($c, 0, $ivlen);
		$hmac = substr($c, $ivlen, $sha2len = 32);
		$raw = substr($c, $ivlen + $sha2len);

		$password = openssl_decrypt($raw, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);

		return $password;
	}

}
