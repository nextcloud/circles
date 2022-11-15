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

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCA\Circles\Exceptions\TokenDoesNotExistException;
use OCA\Circles\Model\DeprecatedMember;
use OCA\Circles\Model\SharesToken;

/**
 * @deprecated
 * Class TokensRequest
 *
 * @package OCA\Circles\Db
 */
class TokensRequest extends TokensRequestBuilder {
	/**
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
	 * @param DeprecatedMember $member
	 *
	 * @return SharesToken[]
	 */
	public function getTokensFromMember(DeprecatedMember $member) {
		$qb = $this->getTokensSelectSql();
		$this->limitToUserId($qb, $member->getUserId());
		$this->limitToCircleId($qb, $member->getCircleId());

		$shares = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$shares[] = $this->parseTokensSelectSql($data);
		}
		$cursor->closeCursor();

		return $shares;
	}


	/**
	 * @param DeprecatedMember $member
	 * @param int $shareId
	 * @param string $password
	 *
	 * @return SharesToken
	 * @throws TokenDoesNotExistException
	 */
	public function generateTokenForMember(DeprecatedMember $member, int $shareId, string $password = ''): SharesToken {
		try {
			$token = $this->miscService->token(15);

			if ($password !== '') {
				$hasher = \OC::$server->getHasher();
				$password = $hasher->hash($password);
			}

			$qb = $this->getTokensInsertSql();
			$qb->setValue('circle_id', $qb->createNamedParameter($member->getCircleId()))
			   ->setValue('user_id', $qb->createNamedParameter($member->getUserId()))
			   ->setValue('share_id', $qb->createNamedParameter($shareId))
			   ->setValue('member_id', $qb->createNamedParameter($member->getMemberId()))
			   ->setValue('token', $qb->createNamedParameter($token))
			   ->setValue('password', $qb->createNamedParameter($password));

			$qb->execute();
		} catch (UniqueConstraintViolationException $e) {
		}

		return $this->getTokenFromMember($shareId, $member->getCircleId(), $member->getUserId());
	}


	/**
	 * @param int $shareId
	 */
	public function removeTokenByShareId(int $shareId) {
		$qb = $this->getTokensDeleteSql();
		$this->limitToShareId($qb, $shareId);

		$qb->execute();
	}


	/**
	 * @param DeprecatedMember $member
	 */
	public function removeTokensFromMember(DeprecatedMember $member) {
		$qb = $this->getTokensDeleteSql();
		$this->limitToCircleId($qb, $member->getCircleId());
		$this->limitToUserId($qb, $member->getUserId());

		$qb->execute();
	}


	public function updateSinglePassword(string $circleId, string $password) {
		$qb = $this->getTokensUpdateSql();

		if ($password !== '') {
			$hasher = \OC::$server->getHasher();
			$password = $hasher->hash($password);
		}

		$this->limitToCircleId($qb, $circleId);
		$qb->set('password', $qb->createNamedParameter($password));

		$qb->execute();
	}
}
