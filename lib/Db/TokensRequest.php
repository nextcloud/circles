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


use daita\MySmallPhpTools\Traits\TStringTools;
use Exception;
use OCA\Circles\Exceptions\TokenDoesNotExistException;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SharesToken;


/**
 * Class TokensRequest
 *
 * @package OCA\Circles\Db
 */
class TokensRequest extends TokensRequestBuilder {


	use TStringTools;


	/**
	 * remove shares from a member to a circle
	 *
	 * @param string $token
	 *
	 * @return SharesToken
	 * @throws TokenDoesNotExistException
	 */
	public function getByToken(string $token) {
		$qb = $this->getTokensSelectSql();
		$this->limitToToken($qb, $token);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();
		if ($data === false) {
			throw new TokenDoesNotExistException('Unknown share token');
		}

		return $this->parseTokensSelectSql($data);
	}


	/**
	 * @param string $shareId
	 * @param string $circleId
	 * @param string $email
	 *
	 * @return SharesToken
	 * @throws TokenDoesNotExistException
	 */
	public function getTokenFromMember(string $shareId, string $circleId, string $email) {
		$qb = $this->getTokensSelectSql();
		$this->limitToShareId($qb, $shareId);
		$this->limitToUserId($qb, $email);
		$this->limitToCircleId($qb, $circleId);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();
		if ($data === false) {
			throw new TokenDoesNotExistException('Unknown share token');
		}

		return $this->parseTokensSelectSql($data);
	}


	/**
	 * @param Member $member
	 * @param int $shareId
	 *
	 * @return mixed
	 */
	public function generateTokenForMember(Member $member, int $shareId) {
		$token = $this->uuid(13);
		try {
			$qb = $this->getTokensInsertSql();
			$qb->setValue('circle_id', $qb->createNamedParameter($member->getCircleId()))
			   ->setValue('user_id', $qb->createNamedParameter($member->getUserId()))
			   ->setValue('share_id', $qb->createNamedParameter($shareId))
			   ->setValue('token', $qb->createNamedParameter($token));

			$qb->execute();

			return $token;
		} catch (Exception $e) {
			return '';
		}
	}

	/**
	 * @param Member[] $members
	 * @param int $shareId
	 */
	public function generateTokenForMembers(array $members, int $shareId) {
		foreach ($members as $member) {
			$this->generateTokenForMember($member, $shareId);
		}
	}


}
