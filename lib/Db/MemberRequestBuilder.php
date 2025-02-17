<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Db;

use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Tools\Exceptions\RowNotFoundException;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class MemberRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class MemberRequestBuilder extends CoreRequestBuilder {
	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getMemberInsertSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_MEMBER)
			->setValue('joined', $qb->createNamedParameter($this->timezoneService->getUTCDate()));

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getMemberUpdateSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_MEMBER);

		return $qb;
	}


	/**
	 * @param IFederatedUser|null $initiator
	 * @param bool $getBasedOn
	 *
	 * @return CoreQueryBuilder&IQueryBuilder
	 * @throws RequestBuilderException
	 */
	protected function getMemberSelectSql(
		?IFederatedUser $initiator = null,
		bool $getBasedOn = true,
	): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(
			self::TABLE_MEMBER,
			self::$tables[self::TABLE_MEMBER],
			CoreQueryBuilder::MEMBER
		)
			->orderBy(CoreQueryBuilder::MEMBER . '.joined');

		if ($getBasedOn) {
			$qb->leftJoinBasedOn(CoreQueryBuilder::MEMBER, $initiator);
		}

		return $qb;
	}


	/**
	 * Base of the Sql Delete request
	 *
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getMemberDeleteSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_MEMBER);

		return $qb;
	}


	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return Member
	 * @throws MemberNotFoundException
	 */
	public function getItemFromRequest(CoreQueryBuilder $qb): Member {
		/** @var Member $member */
		try {
			$member = $qb->asItem(Member::class);
		} catch (RowNotFoundException $e) {
			throw new MemberNotFoundException();
		}

		return $member;
	}

	/**
	 * @param CoreQueryBuilder $qb
	 * @param bool $asFederatedUser
	 *
	 * @return Member[]|FederatedUser[]
	 */
	public function getItemsFromRequest(CoreQueryBuilder $qb, bool $asFederatedUser = false): array {
		$object = Member::class;
		if ($asFederatedUser) {
			$object = FederatedUser::class;
		}

		/** @var Member|FederatedUser[] $result */
		return $qb->asItems($object);
	}
}
