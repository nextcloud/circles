<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use OCA\Circles\Api\v2\TeamSession;
use OCA\Circles\Enum\TeamEntityType;
use OCA\Circles\Enum\TeamMemberLevel;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\TeamEntityNotFoundException;
use OCA\Circles\Managers\TeamEntityManager;
use OCA\Circles\Managers\TeamMemberManager;
use OCA\Circles\Managers\TeamMembershipManager;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Membership;
use OCA\Circles\Model\TeamMembership;
use OCA\Circles\Tools\Exceptions\ItemNotFoundException;

class TeamMembershipService {
	public function __construct(
		private readonly TeamEntityService $teamEntityService,
		private readonly TeamEntityManager $teamEntityManager,
		private readonly TeamMembershipManager $teamMembershipManager,
		private readonly TeamMemberManager $teamMemberManager,
//		MembershipRequest $membershipRequest,
//		CircleRequest $circleRequest,
//		MemberRequest $memberRequest,
//		EventService $eventService,
//		ShareWrapperService $shareWrapperService,
//		OutputService $outputService,
	) {
	}

	public function syncTeamMemberships(TeamSession $teamSession, string $singleId): void {
		if ($teamSession->getEntity()->getTeamEntityType() !== TeamEntityType::SUPER_ADMIN) {
			throw new \Exception('must be ran as super admin'); // TODO: distinct exception
		}

		if ($singleId === '') {
			return;
		}

		try {
			$this->teamEntityManager->getTeamEntity($singleId);
		} catch (TeamEntityNotFoundException) {
			$this->teamMembershipManager->removeSingleId($singleId);
		}

		foreach ($this->getDescendantsSingleIds($teamSession, $singleId) as $entry) {
			$this->manageMemberships($teamSession, $entry);
		}
	}

	/**
	 * recursively obtains the list of all singleIds descendant of one singleId.
	 *
	 * @return list<string>
	 */
	private function getDescendantsSingleIds(TeamSession $session, string $singleId): array {
		$singleIds = [$singleId];
		$members = $this->teamMemberManager->getMembersFromTeam($session, $singleId);
		foreach ($members as $member) {
			if ($member->getTeamMemberLevel() === TeamMemberLevel::INVITED) {
				continue; // only real members
			}
			$singleIds[] = $member->getMemberSingleId();
			if ($member->getEntity()?->getTeamEntityType() === TeamEntityType::TEAM) {
				$this->teamMemberManager->getMembersFromTeam($session, $member->getMemberSingleId());
			}
		}

		return array_unique($singleIds);
	}

	/**
	 * @return int number of modified rows from the database
	 */
	private function manageMemberships(TeamSession $session, string $singleId): int {
		/** @var array<string, TeamMembership> $memberships */
		$memberships = [];
		$this->generateMemberships($session, $singleId, memberships: $memberships);
		return $this->updateMembershipsDatabase($singleId, $memberships);
	}

//	/**
//	 * @param string $circleId
//	 * @param string $singleId
//	 * @param bool $detailed
//	 *
//	 * @return Membership
//	 * @throws MembershipNotFoundException
//	 * @throws RequestBuilderException
//	 */
//	public function getMembership(string $circleId, string $singleId, bool $detailed = false): Membership {
//		$membership = $this->membershipRequest->getMembership($circleId, $singleId);
//		if ($detailed) {
//			$details = $this->circleRequest->getCirclesByIds($membership->getInheritancePath());
//			$membership->setInheritanceDetails($details);
//		}
//
//		return $membership;
//	}
//
//	/**
//	 * @param string $singleId
//	 * @param bool $all
//	 */
//	public function resetMemberships(string $singleId = '', bool $all = false) {
//		$this->membershipRequest->removeBySingleId($singleId, $all);
//	}

	/**
	 * @param FederatedUser $federatedUser
	 */
	public function deleteFederatedUser(FederatedUser $federatedUser) {
		$this->membershipRequest->deleteFederatedUser($federatedUser);
	}

	/**
	 * Return the list of freshly generated Memberships from a singleId
	 * directly based on oc_teams_members
	 *
	 * @param list<string> $path
	 * @param array<string, TeamMembership> $memberships
	 */
	private function generateMemberships(
		TeamSession $session,
		string $singleId,
		string $teamId = '',
		array $path = [],
		array &$memberships = [],
	): void {
		$teamId = ($teamId === '') ? $singleId : $teamId;
		$path[] = $teamId;

		$members = $this->teamMemberManager->getTeamsContainingEntity($session, $teamId);
		foreach ($members as $member) {
			if ($member->getTeamMemberLevel() === TeamMemberLevel::INVITED) {
				continue; // we do not care about invited people
			}

			$membership = new TeamMembership();
			$membership->setSingleId($singleId);
			$membership->setTeamSingleId($member->getTeamSingleId());
			$membership->setLevel($member->getLevel());
			$membership->setPath($path);

			/**
			 * if we already have a memberships between entity and team, we check the depth of inheritance
			 * and the final level assigned to the membership.
			 * The idea is to:
			 *  - avoid duplicate memberships
			 *  - keep the membership with higher level
			 *  - keep the membership with the shortest inheritance path
			 */
			$known = $memberships[$member->getTeamSingleId()];
			if ($known === null
				|| $known->getLevel() < $membership->getLevel()
				|| ($known->getLevel() === $membership->getLevel()
					&& $known->getInheritanceDepth() > $membership->getInheritanceDepth())) {
				$memberships[$member->getTeamSingleId()] = $membership;
			}

			$this->generateMemberships(
				$session,
				$singleId,
				$member->getTeamSingleId(),
				$path,
				$memberships,
			);
		}
	}

	/**
	 * @param array<string, TeamMembership> $memberships
	 *
	 * @return int number of entries from the database modified during the process
	 */
	private	function updateMembershipsDatabase(string $singleId, array $memberships): int {
		$known = $this->teamMembershipManager->getMemberships($singleId);

		$indexed = [];
		foreach($known as $entry) {
			$indexed[$entry->getTeamSingleId()] = $entry;
		}

		$deprecated = $this->removeDeprecatedMemberships($memberships, $indexed);


//		if (!empty($deprecated)) {
//			$this->eventService->membershipsRemoved($deprecated);
//		}
//
		$updated = $this->updateMemberships($memberships, $indexed);
		$new = $this->createMemberships($memberships, $indexed);

		//		if (!empty($new)) {
//			$this->eventService->membershipsCreated($new);
//		}

		// TODO: implement and confirm feature
		// for each entries in the arrays ?
		// // clearing the getSharedWith() cache for singleId related to the membership
		// $this->shareWrapperService->clearCache($membership->getSingleId());

		return count($deprecated) + count($new) + count($updated);
	}

	/**
	 * @param string $id
	 * @param array $knownIds
	 *
	 * @return array
	 * @throws RequestBuilderException
	 */
	private	function getChildrenMembers(	string $id, array &$knownIds = []): array {
		$singleIds = array_map(
			function(Member $item): string {
				return $item->getSingleId();
			}, $this->memberRequest->getMembers($id)
		);

		foreach ($singleIds as $singleId) {
			if ($singleId !== $id && !in_array($singleId, $knownIds)) {
				$knownIds[] = $singleId;
				$singleIds = array_merge($singleIds, $this->getChildrenMembers($singleId, $knownIds));
			}
		}

		return array_unique($singleIds);
	}

	/**
	 * @param string $id
	 * @param array $knownIds
	 *
	 * @return array
	 */
	private
	function getChildrenMemberships(
		string $id, array &$knownIds = []
	): array {
		$singleIds = array_map(
			function(Membership $item): string {
				return $item->getSingleId();
			}, $this->membershipRequest->getInherited($id)
		);

		foreach ($singleIds as $singleId) {
			if ($singleId !== $id && !in_array($singleId, $knownIds)) {
				$knownIds[] = $singleId;
				$singleIds = array_merge($singleIds, $this->getChildrenMemberships($singleId, $knownIds));
			}
		}

		return array_unique($singleIds);
	}

	/**
	 * Add the new membership if unknown or Update known membership if:
	 *  - new membership comes with more power
	 *  - level is the same, but inheritance is shorter
	 *
	 * @param Membership $membership
	 * @param Membership[] $memberships
	 */
	private function fillMemberships(
		Membership $membership, array &$memberships
	) {
		foreach ($memberships as $known) {
			if ($known->getCircleId() === $membership->getCircleId()) {
				if ($known->getLevel() < $membership->getLevel()
					|| ($known->getLevel() === $membership->getLevel()
						&& $known->getInheritanceDepth() > $membership->getInheritanceDepth())
				) {
					$known->setLevel($membership->getLevel());
					$known->setInheritanceFirst($membership->getInheritanceFirst());
					$known->setInheritanceLast($membership->getInheritanceLast());
					$known->setInheritanceDepth($membership->getInheritanceDepth());
					$known->setInheritancePath($membership->getInheritancePath());
				}

				return;
			}
		}

		$memberships[$membership->getCircleId()] = $membership;
	}

	/**
	 * @param list<string, TeamMembership> $memberships
	 * @param list<string, TeamMembership> $known
	 *
	 * @return TeamMembership[]
	 */
	private function removeDeprecatedMemberships(array $memberships, array $known): array {
		$teamSingleIds = array_keys($memberships);

		$deprecated = [];
		foreach ($known as $singleId => $item) {
			if (!in_array($singleId, $teamSingleIds, true)) {
				$deprecated[] = $item;
				$this->teamMembershipManager->removeMembership($item);
			}
		}

		return $deprecated;
	}

	/**
	 * @param list<string, TeamMembership> $memberships
	 * @param list<string, TeamMembership> $known
	 *
	 * @return TeamMembership[]
	 */
	private function updateMemberships(array $memberships, array $known): array {
		$updated = [];

		foreach ($memberships as $singleId => $membership) {
			if (!array_key_exists($singleId, $known)) {
				continue;
			}

			$item = $known[$singleId];
			if ($item->getLevel() !== $membership->getLevel()
				|| $item->getInheritanceDepth() !== $membership->getInheritanceDepth()) {
				$this->teamMembershipManager->overwriteMembership($item->getId(), $membership);
				$updated[] = $membership;
			} elseif ($item->getInheritancePath() !== $membership->getInheritancePath()) {
				$this->teamMembershipManager->overwriteMembership($item->getId(), $membership);
			}
		}

		return $updated;
	}

	/**
	 * @param list<string, TeamMembership> $memberships
	 * @param list<string, TeamMembership> $known
	 *
	 * @return TeamMembership[]
	 */
	private function createMemberships(array $memberships, array $known): array {
		$new = [];

		foreach ($memberships as $singleId => $membership) {
			if (array_key_exists($singleId, $known)) {
				continue;
			}
			$this->teamMembershipManager->insertMembership($membership);
			$new[] = $membership;
		}

		return $new;
	}

	/**
	 * @param Membership[] $list
	 * @param string $circleId
	 *
	 * @return Membership
	 * @throws ItemNotFoundException
	 */
	private
	function getMembershipsFromList(
		array $list, string $circleId
	): Membership {
		foreach ($list as $item) {
			if ($item->getCircleId() === $circleId) {
				return $item;
			}
		}

		throw new ItemNotFoundException();
	}

	/**
	 * @param Circle $circle
	 */
	public
	function updatePopulation(
		Circle $circle
	): void {
		$local = $inherited = 0;
		$memberships = $this->membershipRequest->getInherited($circle->getSingleId(), Member::LEVEL_MEMBER);
		foreach ($memberships as $membership) {
			$inherited++;
			if ($membership->getCircleId() === $circle->getSingleId()) {
				$local++;
			}
		}

		$settings = $circle->getSettings();
		$settings['population'] = $local;
		$settings['populationInherited'] = $inherited;
		$this->circleRequest->updateSettings($circle->setSettings($settings));
	}
}
