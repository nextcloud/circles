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


use daita\MySmallPhpTools\Model\SimpleDataStore;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
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


	/**
	 * @param Circle|null $circleFilter
	 * @param Member|null $memberFilter
	 * @param IFederatedUser|null $initiator
	 * @param RemoteInstance|null $remoteInstance
	 * @param SimpleDataStore $params
	 *
	 * @return Circle[]
	 */
	public function getCircles(
		?Circle $circleFilter,
		?Member $memberFilter,
		?IFederatedUser $initiator,
		?RemoteInstance $remoteInstance,
		SimpleDataStore $params
	): array {
		$qb = $this->getCircleSelectSql();
		$qb->leftJoinOwner(CoreRequestBuilder::CIRCLE);
		$qb->setOptions([CoreRequestBuilder::CIRCLE], ['getData' => true]);

		if (!$params->gBool('includeSystemCircles')) {
			$qb->filterCircles(
				CoreRequestBuilder::CIRCLE,
				Circle::CFG_SINGLE | Circle::CFG_HIDDEN | Circle::CFG_BACKEND
			);
		}
		if (!is_null($initiator)) {
			$qb->limitToMembership(CoreRequestBuilder::CIRCLE, $initiator);
		}
		if (!is_null($memberFilter)) {
			$qb->limitToDirectMembership(CoreRequestBuilder::CIRCLE, $memberFilter);
		}
		if (!is_null($circleFilter)) {
			$qb->filterCircle($circleFilter);
		}
		if (!is_null($remoteInstance)) {
			$qb->limitToRemoteInstance($remoteInstance->getInstance(), false);
		}

		$qb->chunk($params->gInt('offset'), $params->gInt('limit'));

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
	 * @throws RequestBuilderException
	 */
	public function getCircle(
		string $id,
		?IFederatedUser $initiator = null,
		?RemoteInstance $remoteInstance = null,
		int $filter = Circle::CFG_BACKEND | Circle::CFG_SINGLE | Circle::CFG_HIDDEN
	): Circle {
		$qb = $this->getCircleSelectSql();
		$qb->setOptions([CoreRequestBuilder::CIRCLE], ['getData' => true]);

		$qb->limitToUniqueId($id);
		$qb->filterCircles(CoreRequestBuilder::CIRCLE, $filter);
		$qb->leftJoinOwner(CoreRequestBuilder::CIRCLE);
//		$qb->setOptions(
//			[CoreRequestBuilder::CIRCLE, CoreRequestBuilder::INITIATOR], [
//																		   'mustBeMember' => false,
//																		   'canBeVisitor' => true
//																	   ]
//		);

		if (!is_null($initiator)) {
			$qb->limitToMembership(CoreRequestBuilder::CIRCLE, $initiator);
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
	 */
	public function getFederatedUserBySingleId(string $singleId): FederatedUser {
		$qb = $this->getCircleSelectSql();
		$qb->limitToUniqueId($singleId);
		$qb->leftJoinOwner(CoreRequestBuilder::CIRCLE);

		$circle = $this->getItemFromRequest($qb);

		$federatedUser = new FederatedUser();
		$federatedUser->importFromCircle($circle);

		return $federatedUser;
	}


	/**
	 * method that return the single-user Circle based on a FederatedUser.
	 *
	 * @param IFederatedUser $initiator
	 *
	 * @return Circle
	 * @throws SingleCircleNotFoundException
	 * @throws RequestBuilderException
	 */
	public function getSingleCircle(IFederatedUser $initiator): Circle {
		$qb = $this->getCircleSelectSql(CoreRequestBuilder::SINGLE);

		if ($initiator instanceof FederatedUser) {
			$member = new Member();
			$member->importFromIFederatedUser($initiator);
			$member->setLevel(Member::LEVEL_OWNER);
		} else {
			$member = clone $initiator;
		}

		$qb->limitToDirectMembership(CoreRequestBuilder::SINGLE, $member);
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
	 * @throws RequestBuilderException
	 */
	public function searchCircle(Circle $circle): Circle {
		$qb = $this->getCircleSelectSql();
		$qb->leftJoinOwner(CoreRequestBuilder::CIRCLE);

		if ($circle->getName() !== '') {
			$qb->limitToName($circle->getName());
		}
		if ($circle->getConfig() > 0) {
			$qb->limitToConfig($circle->getConfig());
		}

		if ($circle->hasOwner()) {
			$aliasOwner = $qb->generateAlias(CoreRequestBuilder::CIRCLE, CoreRequestBuilder::OWNER);
			$qb->filterDirectMembership($aliasOwner, $circle->getOwner());
		}

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @return Circle[]
	 */
	public function getFederated(): array {
		$qb = $this->getCircleSelectSql();
		$qb->filterConfig(CoreRequestBuilder::CIRCLE, Circle::CFG_FEDERATED);
		$qb->leftJoinOwner(CoreRequestBuilder::CIRCLE);

		return $this->getItemsFromRequest($qb);
	}


}

