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


use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;


/**
 * Class MemberRequest
 *
 * @package OCA\Circles\Db
 */
class MemberRequest extends MemberRequestBuilder {


//	/**
//	 * @param Circle $circle
//	 */
//	public function save(Circle $circle): void {
//		$qb = $this->getCircleInsertSql();
//		$qb->setValue('id', $qb->createNamedParameter($circle->getId()));
////		   ->setValue('instance', $qb->createNamedParameter($circle->getInstance()))
////		   ->setValue('href', $qb->createNamedParameter($remote->getId()))
////		   ->setValue('item', $qb->createNamedParameter(json_encode($remote->getOrigData())));
//
//		$qb->execute();
//	}
//

//	/**
//	 * @param Circle $circle
//	 */
//	public function update(Circle $circle) {
//		$qb = $this->getCircleUpdateSql();
////		$qb->set('uid', $qb->createNamedParameter($circle->getUid(true)))
////		   ->set('href', $qb->createNamedParameter($circle->getId()))
////		   ->set('item', $qb->createNamedParameter(json_encode($circle->getOrigData())));
//
//		$qb->limitToUniqueId($circle->getId());
//
//		$qb->execute();
//	}
//

	/**
	 * @param Member|null $filter
	 * @param Member|null $viewer
	 *
	 * @return Circle[]
	 */
	public function getMembers(string $circleId): array {
		$qb = $this->getMemberSelectSql();
		$qb->limitToCircleId($circleId);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param string $id
	 * @param Member|null $viewer
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 */
	public function getCircle(string $id, ?Member $viewer = null): Circle {
		$qb = $this->getCircleSelectSql();
		$qb->limitToUniqueId($id);
		$qb->leftJoinOwner();

		if (!is_null($viewer)) {
			$qb->limitToViewer($viewer);
		}

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @return Circle[]
	 */
	public function getFederated(): array {
		$qb = $this->getCircleSelectSql();
		$qb->filterConfig(Circle::CFG_FEDERATED);
		$qb->leftJoinOwner();

		return $this->getItemsFromRequest($qb);
	}

}

