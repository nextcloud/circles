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


use daita\MySmallPhpTools\Exceptions\RowNotFoundException;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Membership;


/**
 * Class MembershipRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class MembershipRequestBuilder extends CoreRequestBuilder {


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getMembershipInsertSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_MEMBERSHIP);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getMembershipUpdateSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_MEMBERSHIP);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getMembershipSelectSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->select(
			CoreQueryBuilder::MEMBER . '.single_id',
			CoreQueryBuilder::MEMBER . '.circle_id',
			CoreQueryBuilder::MEMBER . '.level',
			CoreQueryBuilder::MEMBER . '.inheritance_first',
			CoreQueryBuilder::MEMBER . '.inheritance_last',
			CoreQueryBuilder::MEMBER . '.inheritance_path',
			CoreQueryBuilder::MEMBER . '.inheritance_depth'
		)
		   ->from(self::TABLE_MEMBERSHIP, CoreQueryBuilder::MEMBER)
		   ->setDefaultSelectAlias(CoreQueryBuilder::MEMBER);

		return $qb;
	}


	/**
	 * Base of the Sql Delete request
	 *
	 * @return CoreQueryBuilder
	 */
	protected function getMembershipDeleteSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_MEMBERSHIP);

		return $qb;
	}


	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return Member
	 * @throws RowNotFoundException
	 */
	public function getItemFromRequest(CoreQueryBuilder $qb): Member {
		/** @var Member $member */
		$member = $qb->asItem(Membership::class);

		return $member;
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
