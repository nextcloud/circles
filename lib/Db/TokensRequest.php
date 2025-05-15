<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Db;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCA\Circles\Exceptions\TokenDoesNotExistException;
use OCA\Circles\Model\DeprecatedMember;
use OCA\Circles\Model\SharesToken;
use OCP\Security\IHasher;
use OCP\Server;

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
				$hasher = Server::get(IHasher::class);
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
			$hasher = Server::get(IHasher::class);
			$password = $hasher->hash($password);
		}

		$this->limitToCircleId($qb, $circleId);
		$qb->set('password', $qb->createNamedParameter($password));

		$qb->execute();
	}
}
