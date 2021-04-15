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
class MembershipRequestBuilder extends CoreQueryBuilder {


	/**
	 * @return CoreRequestBuilder
	 */
	protected function getMembershipInsertSql(): CoreRequestBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_MEMBERSHIP);

		return $qb;
	}


	/**
	 * @return CoreRequestBuilder
	 */
	protected function getMembershipUpdateSql(): CoreRequestBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_MEMBERSHIP);

		return $qb;
	}


	/**
	 * @return CoreRequestBuilder
	 */
	protected function getMembershipSelectSql(): CoreRequestBuilder {
		$qb = $this->getQueryBuilder();
		$qb->select(
			CoreRequestBuilder::MEMBER . '.single_id',
			CoreRequestBuilder::MEMBER . '.circle_id',
			CoreRequestBuilder::MEMBER . '.level',
			CoreRequestBuilder::MEMBER . '.inheritance_first',
			CoreRequestBuilder::MEMBER . '.inheritance_last',
			CoreRequestBuilder::MEMBER . '.inheritance_path',
			CoreRequestBuilder::MEMBER . '.inheritance_depth'
		)
		   ->from(self::TABLE_MEMBERSHIP, CoreRequestBuilder::MEMBER)
		   ->setDefaultSelectAlias(CoreRequestBuilder::MEMBER);

		return $qb;
	}


	/**
	 * Base of the Sql Delete request
	 *
	 * @return CoreRequestBuilder
	 */
	protected function getMembershipDeleteSql(): CoreRequestBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_MEMBERSHIP);

		return $qb;
	}


	/**
	 * @param CoreRequestBuilder $qb
	 *
	 * @return Member
	 * @throws RowNotFoundException
	 */
	public function getItemFromRequest(CoreRequestBuilder $qb): Member {
		/** @var Member $member */
		$member = $qb->asItem(Membership::class);

		return $member;
	}

	/**
	 * @param CoreRequestBuilder $qb
	 *
	 * @return Membership[]
	 */
	public function getItemsFromRequest(CoreRequestBuilder $qb): array {
		/** @var Membership[] $result */
		return $qb->asItems(Membership::class);
	}

}
