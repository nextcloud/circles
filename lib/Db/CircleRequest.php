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
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;


/**
 * Class CircleRequest
 *
 * @package OCA\Circles\Db
 */
class CircleRequest extends CircleRequestBuilder {


	/**
	 * @param Circle $circle
	 *
	 * @throws InvalidIdException
	 */
	public function save(Circle $circle): void {
		$this->confirmValidId($circle->getId());

		$qb = $this->getCircleInsertSql();
		$qb->setValue('unique_id', $qb->createNamedParameter($circle->getId()))
		   ->setValue('long_id', $qb->createNamedParameter($circle->getId()))
		   ->setValue('name', $qb->createNamedParameter($circle->getName()))
		   ->setValue('source', $qb->createNamedParameter($circle->getSource()))
		   ->setValue('display_name', $qb->createNamedParameter($circle->getDisplayName()))
		   ->setValue('description', $qb->createNamedParameter($circle->getDescription()))
		   ->setValue('contact_addressbook', $qb->createNamedParameter($circle->getContactAddressBook()))
		   ->setValue('contact_groupname', $qb->createNamedParameter($circle->getContactGroupName()))
		   ->setValue('settings', $qb->createNamedParameter(json_encode($circle->getSettings())))
		   ->setValue('type', $qb->createNamedParameter(0))
		   ->setValue('config', $qb->createNamedParameter($circle->getConfig()));

		$qb->execute();
	}


	/**
	 * @param Circle $circle
	 */
	public function update(Circle $circle) {
		$qb = $this->getCircleUpdateSql();
		$qb->set('name', $qb->createNamedParameter($circle->getName()))
		   ->set('display_name', $qb->createNamedParameter($circle->getDisplayName()))
		   ->set('description', $qb->createNamedParameter($circle->getDescription()))
		   ->set('settings', $qb->createNamedParameter(json_encode($circle->getSettings())))
		   ->set('config', $qb->createNamedParameter($circle->getConfig()));

		$qb->limitToUniqueId($circle->getId());

		$qb->execute();
	}


	/**
	 * @param Circle $circle
	 *
	 * @throws InvalidIdException
	 */
	public function insertOrUpdate(Circle $circle): void {
		try {
			$this->getCircle($circle->getId());
			$this->update($circle);
		} catch (CircleNotFoundException $e) {
			$this->save($circle);
		}
	}


	/**
	 * @param Circle $circle
	 */
	public function updateConfig(Circle $circle) {
		$qb = $this->getCircleUpdateSql();
		$qb->set('config', $qb->createNamedParameter($circle->getConfig()));

		$qb->limitToUniqueId($circle->getId());

		$qb->execute();
	}



//
//	/**
//	 * @param Member $member
//	 * @param FederatedUser|null $initiator
//	 *
//	 * @return Member
//	 * @throws MemberNotFoundException
//	 */
//	public function searchMember(Member $member, ?FederatedUser $initiator = null): Member {
//		$qb = $this->getCircleSelectSql();
//		$qb->limitToCircleId($member->getCircleId());
//		$qb->limitToUserId($member->getUserId());
//		$qb->limitToUserType($member->getUserType());
//		$qb->limitToInstance($qb->getInstance($member));
////		$qb->limitToSingleId($federatedUser->getSingleId());
//
//		if (!is_null($initiator)) {
//			$qb->leftJoinCircle($initiator);
//		}
//
//		return $this->getItemFromRequest($qb);
//	}

	/**
	 * @param Member|null $filter
	 * @param IFederatedUser|null $initiator
	 * @param RemoteInstance|null $remoteInstance
	 * @param bool $filterSystemCircles
	 *
	 * @return Circle[]
	 */
	public function getCircles(
		?Member $filter = null,
		?IFederatedUser $initiator = null,
		?RemoteInstance $remoteInstance = null,
		bool $filterSystemCircles = true
	): array {
		$qb = $this->getCircleSelectSql();
		$qb->leftJoinOwner();

		if ($filterSystemCircles) {
			$qb->filterCircles(Circle::CFG_SINGLE | Circle::CFG_HIDDEN | Circle::CFG_BACKEND);
		}
		if (!is_null($initiator)) {
			$qb->limitToInitiator($initiator);
		}
		if (!is_null($filter)) {
			$qb->limitToMembership($filter);
		}
		if (!is_null($remoteInstance)) {
			$qb->limitToRemoteInstance($remoteInstance->getInstance(), false);
		}

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param string $id
	 * @param IFederatedUser|null $initiator
	 * @param RemoteInstance|null $remoteInstance
	 * @param int $filter
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 */
	public function getCircle(
		string $id,
		?IFederatedUser $initiator = null,
		?RemoteInstance $remoteInstance = null,
		int $filter = Circle::CFG_BACKEND | Circle::CFG_SINGLE | Circle::CFG_HIDDEN
	): Circle {
		$qb = $this->getCircleSelectSql();
		$qb->limitToUniqueId($id);
		$qb->leftJoinOwner();
		$qb->filterCircles($filter);

		if (!is_null($initiator)) {
			$qb->limitToInitiator($initiator, '', false, true);
		}
		if (!is_null($remoteInstance)) {
			$qb->limitToRemoteInstance($remoteInstance->getInstance(), false);
		}

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @param string $singleId
	 *
	 * @return FederatedUser
	 * @throws CircleNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws FederatedUserNotFoundException
	 */
	public function getFederatedUserBySingleId(string $singleId): FederatedUser {
		$qb = $this->getCircleSelectSql();
		$qb->limitToUniqueId($singleId);
		$qb->leftJoinOwner();

		$circle = $this->getItemFromRequest($qb);

		$federatedUser = new FederatedUser();
		$federatedUser->setSingleId($circle->getId());

		if ($circle->isConfig(Circle::CFG_SINGLE)) {
			$owner = $circle->getOwner();
			$federatedUser->set($owner->getUserId(), $owner->getInstance(), $owner->getUserType(), $circle);
		} else {
			$federatedUser->set(
				$circle->getDisplayName(), $circle->getInstance(), Member::TYPE_CIRCLE, $circle
			);
		}

		return $federatedUser;
	}


	/**
	 * method that return the single-user Circle based on a FederatedUser.
	 *
	 * @param IFederatedUser $initiator
	 *
	 * @return Circle
	 * @throws SingleCircleNotFoundException
	 */
	public function getSingleCircle(IFederatedUser $initiator): Circle {
		$qb = $this->getCircleSelectSql();
		$qb->leftJoinOwner();

		$member = clone $initiator;
		if ($initiator instanceof FederatedUser) {
			$member = new Member();
			$member->importFromIFederatedUser($initiator);
			$member->setLevel(Member::LEVEL_OWNER);
		}

		$qb->limitToMembership($member);
		$qb->limitToConfigFlag(Circle::CFG_SINGLE);

		try {
			return $this->getItemFromRequest($qb);
		} catch (CircleNotFoundException $e) {
			throw new SingleCircleNotFoundException();
		}
	}


	/**
	 * @param Circle $circle
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 */
	public function searchCircle(Circle $circle): Circle {
		$qb = $this->getCircleSelectSql();
		$qb->leftJoinOwner();

		if ($circle->getName() !== '') {
			$qb->limitToName($circle->getName());
		}
		if ($circle->getConfig() > 0) {
			$qb->limitToConfig($circle->getConfig());
		}

		if ($circle->hasOwner()) {
			$qb->filterMembership($circle->getOwner(), 'o');
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

