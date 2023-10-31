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
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Model\Probes\DataProbe;

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
		$this->confirmValidId($circle->getSingleId());

		$qb = $this->getCircleInsertSql();
		$qb->setValue('unique_id', $qb->createNamedParameter($circle->getSingleId()))
		   ->setValue('name', $qb->createNamedParameter($circle->getName()))
		   ->setValue('source', $qb->createNamedParameter($circle->getSource()))
		   ->setValue('display_name', $qb->createNamedParameter($circle->getDisplayName()))
		   ->setValue('sanitized_name', $qb->createNamedParameter($circle->getSanitizedName()))
		   ->setValue('description', $qb->createNamedParameter($circle->getDescription()))
		   ->setValue('contact_addressbook', $qb->createNamedParameter($circle->getContactAddressBook()))
		   ->setValue('contact_groupname', $qb->createNamedParameter($circle->getContactGroupName()))
		   ->setValue('settings', $qb->createNamedParameter(json_encode($circle->getSettings())))
		   ->setValue('config', $qb->createNamedParameter($circle->getConfig()));

		$qb->execute();
	}


	/**
	 * @param Circle $circle
	 */
	public function edit(Circle $circle): void {
		$qb = $this->getCircleUpdateSql();
		$qb->set('name', $qb->createNamedParameter($circle->getName()))
		   ->set('display_name', $qb->createNamedParameter($circle->getDisplayName()))
		   ->set('sanitized_name', $qb->createNamedParameter($circle->getSanitizedName()))
		   ->set('description', $qb->createNamedParameter($circle->getDescription()));

		$qb->limitToUniqueId($circle->getSingleId());

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

		$qb->limitToUniqueId($circle->getSingleId());

		$qb->execute();
	}


	/**
	 * @param Circle $circle
	 *
	 * @throws InvalidIdException
	 */
	public function insertOrUpdate(Circle $circle): void {
		try {
			$this->getCircle($circle->getSingleId());
			$this->update($circle);
		} catch (CircleNotFoundException $e) {
			$this->save($circle);
		}
	}


	/**
	 * @param string $singleId
	 * @param string $displayName
	 */
	public function updateDisplayName(string $singleId, string $displayName): void {
		$qb = $this->getCircleUpdateSql();
		$qb->set('display_name', $qb->createNamedParameter($displayName));

		$qb->limitToUniqueId($singleId);

		$qb->execute();
	}


	/**
	 * @param Circle $circle
	 */
	public function updateConfig(Circle $circle) {
		$qb = $this->getCircleUpdateSql();
		$qb->set('config', $qb->createNamedParameter($circle->getConfig()));

		$qb->limitToUniqueId($circle->getSingleId());

		$qb->execute();
	}


	/**
	 * @param Circle $circle
	 */
	public function updateSettings(Circle $circle) {
		$qb = $this->getCircleUpdateSql();
		$qb->set('settings', $qb->createNamedParameter(json_encode($circle->getSettings())));
		$qb->limitToUniqueId($circle->getSingleId());

		$qb->execute();
	}


	/**
	 * @param IFederatedUser|null $initiator
	 * @param CircleProbe $probe
	 *
	 * @return Circle[]
	 * @throws RequestBuilderException
	 */
	public function getCircles(?IFederatedUser $initiator, CircleProbe $probe): array {
		$qb = $this->getCircleSelectSql();
		$qb->leftJoinOwner(CoreQueryBuilder::CIRCLE);
		$qb->setOptions(
			[CoreQueryBuilder::CIRCLE],
			array_merge(
				$probe->getAsOptions(),
				[
					'getData' => true,
					'initiatorDirectMember' => true
				]
			)
		);

		$qb->filterCircles(CoreQueryBuilder::CIRCLE, $probe);
		if (!is_null($initiator)) {
			$qb->limitToInitiator(CoreQueryBuilder::CIRCLE, $initiator);
			$qb->orderBy($qb->generateAlias(CoreQueryBuilder::CIRCLE, CoreQueryBuilder::INITIATOR) . '.level', 'desc');
			$qb->addOrderBy(CoreQueryBuilder::CIRCLE . '.display_name', 'asc');
		}
		if ($probe->hasFilterMember()) {
			$qb->limitToDirectMembership(CoreQueryBuilder::CIRCLE, $probe->getFilterMember());
		}
		if ($probe->hasFilterCircle()) {
			$qb->filterCircleDetails($probe->getFilterCircle());
		}
		if ($probe->hasFilterRemoteInstance()) {
			$qb->limitToRemoteInstance(CoreQueryBuilder::CIRCLE, $probe->getFilterRemoteInstance(), false);
		}

		$qb->chunk($probe->getItemsOffset(), $probe->getItemsLimit());

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * get data about single Circle.
	 *
	 * - CircleProbe is used to confirm the visibility of the targeted circle,
	 * - DataProbe is used to define the complexity of the data to be returned for each entry of the list
	 *
	 * @param string $singleId
	 * @param IFederatedUser|null $initiator
	 * @param CircleProbe $circleProbe
	 * @param DataProbe $dataProbe
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 * @throws RequestBuilderException
	 */
	public function probeCircle(
		string $singleId,
		?IFederatedUser $initiator,
		CircleProbe $circleProbe,
		DataProbe $dataProbe
	): Circle {
		$qb = $this->buildProbeCircle($initiator, $circleProbe, $dataProbe);
		$qb->limit('unique_id', $singleId);

		return $this->getItemFromRequest($qb);
	}

	/**
	 * get data about multiple Circles.
	 *
	 * - CircleProbe is used to define the list of circles to be returned by the method,
	 * - DataProbe is used to define the complexity of the data to be returned for each entry of the list
	 *
	 * @param IFederatedUser|null $initiator
	 * @param CircleProbe $circleProbe
	 * @param DataProbe $dataProbe
	 *
	 * @return Circle[]
	 * @throws RequestBuilderException
	 */
	public function probeCircles(
		?IFederatedUser $initiator,
		CircleProbe $circleProbe,
		DataProbe $dataProbe
	): array {
		$qb = $this->buildProbeCircle($initiator, $circleProbe, $dataProbe);
		$qb->chunk($circleProbe->getItemsOffset(), $circleProbe->getItemsLimit());

		return $this->getItemsFromRequest($qb);
	}

	/**
	 * @param IFederatedUser|null $initiator
	 * @param CircleProbe $circleProbe
	 * @param DataProbe $dataProbe
	 *
	 * @return CoreQueryBuilder
	 * @throws RequestBuilderException
	 */
	private function buildProbeCircle(
		?IFederatedUser $initiator,
		CircleProbe $circleProbe,
		DataProbe $dataProbe
	): CoreQueryBuilder {
		$qb = $this->getCircleSelectSql();
		if (!$dataProbe->has(DataProbe::MEMBERSHIPS)) {
			$dataProbe->add(DataProbe::MEMBERSHIPS);
		}

		$qb->setSqlPath(CoreQueryBuilder::CIRCLE, $dataProbe->getPath())
		   ->setOptions([CoreQueryBuilder::CIRCLE], $circleProbe->getAsOptions())
		   ->filterCircles(CoreQueryBuilder::CIRCLE, $circleProbe);

		if ($circleProbe->hasFilterCircle()) {
			$qb->filterCircleDetails($circleProbe->getFilterCircle());
		}

		$qb->leftJoinOwner(CoreQueryBuilder::CIRCLE);
		$qb->innerJoinMembership($circleProbe, CoreQueryBuilder::CIRCLE);

		$aliasMembership = $qb->generateAlias(CoreQueryBuilder::CIRCLE, CoreQueryBuilder::MEMBERSHIPS);

		$limit = $qb->expr()->orX();
		if (is_null($initiator)) {
			// to get unique result, enforce a limit on level=owner
			$limit->add($qb->exprLimitInt('level', Member::LEVEL_OWNER, $aliasMembership));
		} else {
			$limit->add(
				$qb->exprLimit(
					'single_id',
					$initiator->getSingleId(),
					$aliasMembership
				)
			);
			$qb->completeProbeWithInitiator(CoreQueryBuilder::CIRCLE, 'single_id', $aliasMembership);
		}

		$qb->andWhere($limit);
		$qb->resetSqlPath();

		return $qb;
	}


	/**
	 * @param array $circleIds
	 *
	 * @return array
	 * @throws RequestBuilderException
	 */
	public function getCirclesByIds(array $circleIds): array {
		$qb = $this->getCircleSelectSql();
		$qb->setOptions(
			[CoreQueryBuilder::CIRCLE], ['getData' => true, 'minimumLevel' => Member::LEVEL_NONE]
		);

		$qb->limitInArray('unique_id', $circleIds);
//		$qb->filterCircles(CoreQueryBuilder::CIRCLE, $filter);
		$qb->leftJoinOwner(CoreQueryBuilder::CIRCLE);

		return $this->getItemsFromRequest($qb);
	}

	/**
	 * @param string $id
	 * @param IFederatedUser|null $initiator
	 * @param CircleProbe|null $probe
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 * @throws RequestBuilderException
	 */
	public function getCircle(
		string $id,
		?IFederatedUser $initiator = null,
		?CircleProbe $probe = null
	): Circle {
		if (is_null($probe)) {
			$probe = new CircleProbe();
			$probe->includeSystemCircles()
				  ->emulateVisitor();
		}

		$qb = $this->getCircleSelectSql(CoreQueryBuilder::CIRCLE, true);
		$qb->setOptions(
			[CoreQueryBuilder::CIRCLE],
			array_merge(
				$probe->getAsOptions(),
				[
					'getData' => true,
					'initiatorDirectMember' => true
				]
			)
		);

		$qb->limitToUniqueId($id);
		$qb->filterCircles(CoreQueryBuilder::CIRCLE, $probe);
		$qb->leftJoinOwner(CoreQueryBuilder::CIRCLE);
//		$qb->setOptions(
//			[CoreRequestBuilder::CIRCLE, CoreRequestBuilder::INITIATOR], [
//																		   'mustBeMember' => false,
//																		   'viewableAsVisitor' => true
//																	   ]
//		);

		if (!is_null($initiator)) {
			$qb->limitToInitiator(CoreQueryBuilder::CIRCLE, $initiator);
		}
		if ($probe->hasFilterRemoteInstance()) {
			$qb->limitToRemoteInstance(CoreQueryBuilder::CIRCLE, $probe->getFilterRemoteInstance(), false);
		}

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @param string $singleId
	 *
	 * @return FederatedUser
	 * @throws OwnerNotFoundException
	 * @throws RequestBuilderException
	 * @throws FederatedUserNotFoundException
	 */
	public function getFederatedUserBySingleId(string $singleId): FederatedUser {
		$qb = $this->getCircleSelectSql(CoreQueryBuilder::CIRCLE, true);
		$qb->limitToUniqueId($singleId);
		$qb->leftJoinOwner(CoreQueryBuilder::CIRCLE);

		try {
			$circle = $this->getItemFromRequest($qb);
		} catch (CircleNotFoundException $e) {
			throw new FederatedUserNotFoundException('singleId not found');
		}

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
		$qb = $this->getCircleSelectSql(CoreQueryBuilder::SINGLE, true);

		if ($initiator instanceof FederatedUser) {
			$member = new Member();
			$member->importFromIFederatedUser($initiator);
			$member->setLevel(Member::LEVEL_OWNER);
		} else {
			$member = clone $initiator;
		}

		$qb->limitToDirectMembership(CoreQueryBuilder::SINGLE, $member);
		$qb->limitToConfigFlag(Circle::CFG_SINGLE);

		try {
			return $this->getItemFromRequest($qb);
		} catch (CircleNotFoundException $e) {
			throw new SingleCircleNotFoundException();
		}
	}


	/**
	 * @param Circle $circle
	 * @param IFederatedUser|null $initiator
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 * @throws RequestBuilderException
	 */
	public function searchCircle(Circle $circle, ?IFederatedUser $initiator = null): Circle {
		$qb = $this->getCircleSelectSql();
		$qb->leftJoinOwner(CoreQueryBuilder::CIRCLE);

		if ($circle->getName() !== '') {
			$qb->limitToName($circle->getName());
		}
		if ($circle->getDisplayName() !== '') {
			$qb->limitToDisplayName($circle->getDisplayName());
		}
		if ($circle->getSanitizedName() !== '') {
			$qb->limitToSanitizedName($circle->getSanitizedName());
		}
		if ($circle->getConfig() > 0) {
			$qb->limitToConfig($circle->getConfig());
		}
		if ($circle->getSource() > 0) {
			$qb->limitToSource($circle->getSource());
		}

		if ($circle->hasOwner()) {
			$aliasOwner = $qb->generateAlias(CoreQueryBuilder::CIRCLE, CoreQueryBuilder::OWNER);
			$qb->filterDirectMembership($aliasOwner, $circle->getOwner());
		}

		if (!is_null($initiator)) {
			$qb->setOptions(
				[CoreQueryBuilder::CIRCLE],
				[
					'getData' => true,
					'initiatorDirectMember' => true
				]
			);
			$qb->limitToInitiator(CoreQueryBuilder::CIRCLE, $initiator);
		}

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @return Circle[]
	 * @throws RequestBuilderException
	 */
	public function getFederated(): array {
		$qb = $this->getCircleSelectSql();
		$qb->limitToConfigFlag(Circle::CFG_FEDERATED, CoreQueryBuilder::CIRCLE);

		$qb->leftJoinOwner(CoreQueryBuilder::CIRCLE);

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param Circle $circle
	 */
	public function delete(Circle $circle): void {
		$qb = $this->getCircleDeleteSql();
		$qb->limitToUniqueId($circle->getSingleId());

		$qb->execute();
	}


	/**
	 * @param IFederatedUser $federatedUser
	 */
	public function deleteFederatedUser(IFederatedUser $federatedUser): void {
		$qb = $this->getCircleDeleteSql();
		$qb->limitToUniqueId($federatedUser->getSingleId());

		$qb->execute();
	}
}
