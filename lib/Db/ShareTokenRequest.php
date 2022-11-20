<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
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

use OCA\Circles\Exceptions\ShareTokenNotFoundException;
use OCA\Circles\Model\ShareToken;

/**
 * Class ShareTokenRequest
 *
 * @package OCA\Circles\Db
 */
class ShareTokenRequest extends ShareTokenRequestBuilder {
	/**
	 * @param ShareToken $token
	 *
	 * @return void
	 */
	public function save(ShareToken $token): void {
		$qb = $this->getTokenInsertSql();
		$qb->setValue('share_id', $qb->createNamedParameter($token->getShareId()))
		   ->setValue('circle_id', $qb->createNamedParameter($token->getCircleId()))
		   ->setValue('single_id', $qb->createNamedParameter($token->getSingleId()))
		   ->setValue('member_id', $qb->createNamedParameter($token->getMemberId()))
		   ->setValue('token', $qb->createNamedParameter($token->getToken()))
		   ->setValue('password', $qb->createNamedParameter($token->getPassword()))
		   ->setValue('accepted', $qb->createNamedParameter($token->getAccepted()));

		$qb->execute();
		$id = $qb->getLastInsertId();
		$token->setDbId($id);
	}


	/**
	 * @param ShareToken $shareToken
	 *
	 * @return ShareToken
	 * @throws ShareTokenNotFoundException
	 */
	public function search(ShareToken $shareToken): ShareToken {
		$qb = $this->getTokenSelectSql();
		$qb->limitInt('share_id', $shareToken->getshareId());
		$qb->limitToCircleId($shareToken->getCircleId());
		$qb->limitToSingleId($shareToken->getSingleId());

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @param string $token
	 *
	 * @return ShareToken
	 * @throws ShareTokenNotFoundException
	 */
	public function getByToken(string $token): ShareToken {
		$qb = $this->getTokenSelectSql();
		$qb->limitToToken($token);

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @param string $circleId
	 * @param string $hashedPassword
	 */
	public function updateSharePassword(string $circleId, string $hashedPassword) {
		$qb = $this->getTokenUpdateSql();
		$qb->limitToCircleId($circleId);

		$qb->set('password', $qb->createNamedParameter($hashedPassword));

		$qb->executeStatement();
	}


	/**
	 * @param string $singleId
	 * @param string $circleId
	 */
	public function removeTokens(string $singleId, string $circleId) {
		$qb = $this->getTokenDeleteSql();
		$qb->limitToSingleId($singleId);
		$qb->limitToCircleId($circleId);

		$qb->execute();
	}
}
