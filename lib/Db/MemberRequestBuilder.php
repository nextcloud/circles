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

use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Tools\Exceptions\RowNotFoundException;

/**
 * Class MemberRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class MemberRequestBuilder extends CoreRequestBuilder {
	/**
	 * @return CoreQueryBuilder
	 */
	protected function getMemberInsertSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_MEMBER)
		   ->setValue('joined', $qb->createNamedParameter($this->timezoneService->getUTCDate()));

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
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
	 * @return CoreQueryBuilder
	 * @throws RequestBuilderException
	 */
	protected function getMemberSelectSql(
		?IFederatedUser $initiator = null,
		bool $getBasedOn = true
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
	 * @return CoreQueryBuilder
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
