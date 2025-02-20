<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Db;

use OCA\Circles\Exceptions\MembershipNotFoundException;
use OCA\Circles\Model\Membership;
use OCA\Circles\Tools\Exceptions\RowNotFoundException;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class MembershipRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class MembershipRequestBuilder extends CoreRequestBuilder {
	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getMembershipInsertSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_MEMBERSHIP);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getMembershipUpdateSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_MEMBERSHIP);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getMembershipSelectSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(
			self::TABLE_MEMBERSHIP,
			self::$tables[self::TABLE_MEMBERSHIP],
			CoreQueryBuilder::MEMBERSHIPS
		);

		return $qb;
	}


	/**
	 * Base of the Sql Delete request
	 *
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getMembershipDeleteSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_MEMBERSHIP);

		return $qb;
	}


	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return Membership
	 * @throws MembershipNotFoundException
	 */
	public function getItemFromRequest(CoreQueryBuilder $qb): Membership {
		/** @var Membership $membership */
		try {
			$membership = $qb->asItem(Membership::class);
		} catch (RowNotFoundException $e) {
			throw new MembershipNotFoundException();
		}

		return $membership;
	}

	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return Membership[]
	 */
	public function getItemsFromRequest(CoreQueryBuilder $qb): array {
		/** @var Membership[] $result */
		return $qb->asItems(Membership::class);
	}
}
