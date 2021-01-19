<?php

declare(strict_types=1);

/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
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


use daita\MySmallPhpTools\Db\Nextcloud\nc21\NC21ExtendedQueryBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use OCA\Circles\Model\DeprecatedMember;
use OCA\Circles\Model\Member;

/**
 * Class CoreQueryBuilder
 *
 * @package OCA\Circles\Db
 */
class CoreQueryBuilder extends NC21ExtendedQueryBuilder {


	/**
	 * @param string $id
	 */
	public function limitToUniqueId(string $id): void {
		$this->limitToDBField('unique_id', $id, false);
	}

	/**
	 * @param string $host
	 */
	public function limitToInstance(string $host): void {
		$this->limitToDBField('instance', $host, false);
	}


	public function limitToViewer(Member $viewer): void {
		$this->leftJoinViewer($viewer);
		$this->limitVisibility($viewer);

//		if ($level > 0) {
//			$this->limitToLevel($qb, $level, ['u', 'g']);
//		}
//		$this->limitRegardingCircleType($qb, $userId, -1, $circleType, $name, $forceAll);
	}


	/**
	 * Left Join members table to get the owner of the circle.
	 *
	 * @param string $ownerId
	 */
	public function leftJoinOwner(string $ownerId = '') {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$expr = $this->expr();
		$pf = $this->getDefaultSelectAlias() . '.';

		$this->selectAlias('o.user_id', 'owner_user_id')
			 ->selectAlias('o.user_type', 'owner_user_type')
			 ->selectAlias('o.member_id', 'owner_member_id')
			 ->selectAlias('o.circle_id', 'owner_circle_id')
			 ->selectAlias('o.instance', 'owner_instance')
			 ->selectAlias('o.cached_name', 'owner_cached_name')
			 ->selectAlias('o.cached_update', 'owner_cached_update')
			 ->selectAlias('o.status', 'owner_status')
			 ->selectAlias('o.level', 'owner_level')
			 ->selectAlias('o.note', 'owner_note')
			 ->selectAlias('o.contact_id', 'owner_contact_id')
			 ->selectAlias('o.contact_meta', 'owner_contact_meta')
			 ->selectAlias('o.joined', 'owner_joined')
			 ->leftJoin(
				 $this->getDefaultSelectAlias(), CoreRequestBuilder::TABLE_MEMBERS, 'o',
				 $expr->andX(
					 $expr->eq('o.circle_id', $pf . 'unique_id'),
					 $expr->eq('o.level', $this->createNamedParameter(DeprecatedMember::LEVEL_OWNER)),
					 $expr->eq('o.user_type', $this->createNamedParameter(DeprecatedMember::TYPE_USER))
				 )
			 );

		if ($ownerId !== '') {
			$this->andWhere($expr->eq('o.user_id', $this->createNamedParameter($ownerId)));
		}
	}


	/**
	 * Left join members to filter userId as viewer.
	 *
	 * @param Member $viewer
	 */
	public function leftJoinViewer(Member $viewer) {
		if ($this->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$expr = $this->expr();
		$pf = $this->getDefaultSelectAlias() . '.';

		$this->selectAlias('v.user_id', 'viewer_user_id')
			 ->selectAlias('v.user_type', 'viewer_user_type')
			 ->selectAlias('v.member_id', 'viewer_member_id')
			 ->selectAlias('v.circle_id', 'viewer_circle_id')
			 ->selectAlias('v.instance', 'viewer_instance')
			 ->selectAlias('v.cached_name', 'viewer_cached_name')
			 ->selectAlias('v.cached_update', 'viewer_cached_update')
			 ->selectAlias('v.status', 'viewer_status')
			 ->selectAlias('v.level', 'viewer_level')
			 ->selectAlias('v.note', 'viewer_note')
			 ->selectAlias('v.contact_id', 'viewer_contact_id')
			 ->selectAlias('v.contact_meta', 'viewer_contact_meta')
			 ->selectAlias('v.joined', 'viewer_joined')
			 ->leftJoin(
				 $this->getDefaultSelectAlias(), CoreRequestBuilder::TABLE_MEMBERS, 'v',
				 $expr->andX(
					 $expr->eq('v.circle_id', $pf . 'unique_id'),
					 $expr->eq('v.user_id', $this->createNamedParameter($viewer->getUserId())),
					 $expr->eq('v.instance', $this->createNamedParameter($viewer->getInstance())),
					 $expr->eq('v.user_type', $this->createNamedParameter($viewer->getUserType()))
				 )
			 );
	}


	protected function limitVisibility(Member $viewer) {
//		$orTypes = $this->generateLimit($qb, $circleUniqueId, $userId, $type, $name, $forceAll);
//		if (sizeof($orTypes) === 0) {
//			throw new ConfigNoCircleAvailableException(
//				$this->l10n->t(
//					'You cannot use the Circles Application until your administrator has allowed at least one type of circles'
//				)
//			);
//		}

//		$orXTypes = $this->expr()
//						 ->orX();
//		foreach ($orTypes as $orType) {
//			$orXTypes->add($orType);
//		}
//
//		$qb->andWhere($orXTypes);
	}

}

