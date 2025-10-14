<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Db;

use OCA\Circles\Exceptions\ShareTokenNotFoundException;
use OCA\Circles\Model\ShareToken;
use OCP\DB\QueryBuilder\IQueryBuilder;

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

		$qb->executeStatement();
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

		$qb->executeStatement();
	}

	/**
	 * @param array $shareIds
	 *
	 * @return ShareToken[]
	 */
	public function getTokensFromShares(array $shareIds): array {
		$qb = $this->getTokenSelectSql();
		$qb->limitInArray('share_id', $shareIds, type: IQueryBuilder::PARAM_INT_ARRAY);

		return $this->getItemsFromRequest($qb);
	}
}
