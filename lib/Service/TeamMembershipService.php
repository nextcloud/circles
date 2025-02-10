<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use OCA\Circles\Api\v2\TeamSession;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Db\MembershipRequest;
use OCA\Circles\Enum\TeamEntityType;
use OCA\Circles\Enum\TeamMemberLevel;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\MembershipNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
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
		private readonly TeamSession $teamSession,
//		MembershipRequest $membershipRequest,
//		CircleRequest $circleRequest,
//		MemberRequest $memberRequest,
//		EventService $eventService,
//		ShareWrapperService $shareWrapperService,
//		OutputService $outputService,
	) {
	}

	public function syncTeamMemberships(string $singleId): void {
		if ($singleId === '') {
			return;
		}

		try {
			$this->teamEntityManager->getTeamEntity($singleId);
		} catch (TeamEntityNotFoundException) {
			$this->teamMembershipManager->removeSingleId($singleId);
		}

		$session = $this->teamSession->sessionAsSuperAdmin();
		$children = $this->getDescendantsSingleIds($session, $singleId);
		$children[] = $singleId;

		foreach ($children as $entry) {
			$this->manageMemberships($entry);
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
		foreach($members as $member) {
			if ($member->getTeamMemberLevel() < TeamMemberLevel::MEMBER) {
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
	 * @param string $singleId
	 *
	 * @return int
	 * @throws RequestBuilderException
	 */
	public function manageMemberships(string $singleId): int {
		$memberships = $this->generateMemberships($singleId);

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
	 * @return TeamMembership[]
	 */
	private function generateMemberships(
		string $singleId,
		string $circleId = '',
		array &$memberships = [],
		array $knownIds = [],
		array $path = [],
	): array {
		$circleId = ($circleId === '') ? $singleId : $circleId;
		$path[] = $circleId;
		$knownIds[] = $circleId;

		//$members = $this->teamMemberManager->getMembersFromTeam($session, $singleId);

		$members = $this->memberRequest->getMembersBySingleId($circleId);
		foreach ($members as $member) {
			if (!$member->hasCircle() || $member->getLevel() < Member::LEVEL_MEMBER) {
				continue;
			}

			if ($member->getCircle()->isConfig(Circle::CFG_NO_OWNER)
				&& $member->getLevel() === Member::LEVEL_OWNER) {
				continue;
			}

			$membership = new Membership($singleId, count($path) > 1 ? $path[1] : '', $member);
			$membership->setInheritancePath(array_reverse($path))
					   ->setInheritanceDepth(sizeof($path));
			$this->fillMemberships($membership, $memberships);
			if (!in_array($member->getCircleId(), $knownIds)) {
				$this->generateMemberships(
					$singleId,
					$member->getCircleId(),
					$memberships,
					$knownIds,
					$path
				);
			}
		}

		return $memberships;
	}
//	private function generateMemberships(
//		string $singleId,
//		string $circleId = '',
//		array &$memberships = [],
//		array $knownIds = [],
//		array $path = [],
//	): array {
//		$circleId = ($circleId === '') ? $singleId : $circleId;
//		$path[] = $circleId;
//		$knownIds[] = $circleId;
//
//		$members = $this->memberRequest->getMembersBySingleId($circleId);
//		foreach ($members as $member) {
//			if (!$member->hasCircle() || $member->getLevel() < Member::LEVEL_MEMBER) {
//				continue;
//			}
//
//			if ($member->getCircle()->isConfig(Circle::CFG_NO_OWNER)
//				&& $member->getLevel() === Member::LEVEL_OWNER) {
//				continue;
//			}
//
//			$membership = new Membership($singleId, count($path) > 1 ? $path[1] : '', $member);
//			$membership->setInheritancePath(array_reverse($path))
//					   ->setInheritanceDepth(sizeof($path));
//			$this->fillMemberships($membership, $memberships);
//			if (!in_array($member->getCircleId(), $knownIds)) {
//				$this->generateMemberships(
//					$singleId,
//					$member->getCircleId(),
//					$memberships,
//					$knownIds,
//					$path
//				);
//			}
//		}
//
//		return $memberships;
//	}

	/**
	 * @param string $singleId
	 * @param Membership[] $memberships
	 *
	 * @return int
	 */
	private function updateMembershipsDatabase(string $singleId, array $memberships): int {
		$known = $this->membershipRequest->getMemberships($singleId);

		$deprecated = $this->removeDeprecatedMemberships($memberships, $known);
		if (!empty($deprecated)) {
			$this->eventService->membershipsRemoved($deprecated);
		}

		$new = $this->createNewMemberships($memberships, $known);
		if (!empty($new)) {
			$this->eventService->membershipsCreated($new);
		}

		return count($deprecated) + count($new);
	}

	/**
	 * @param string $id
	 * @param array $knownIds
	 *
	 * @return array
	 * @throws RequestBuilderException
	 */
	private function getChildrenMembers(string $id, array &$knownIds = []): array {
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
	private function getChildrenMemberships(string $id, array &$knownIds = []): array {
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
	private function fillMemberships(Membership $membership, array &$memberships) {
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
	 * @param Membership[] $memberships
	 * @param Membership[] $known
	 *
	 * @return Membership[]
	 */
	private function removeDeprecatedMemberships(array $memberships, array $known): array {
		$circleIds = array_map(
			function(Membership $membership): string {
				return $membership->getCircleId();
			}, $memberships
		);

		$deprecated = [];
		foreach ($known as $item) {
			if (!in_array($item->getCircleId(), $circleIds)) {
				$deprecated[] = $item;
				$this->membershipRequest->delete($item);

				// clearing the getSharedWith() cache for singleId related to the membership
				$this->shareWrapperService->clearCache($item->getSingleId());
			}
		}

		return $deprecated;
	}

	/**
	 * @param Membership[] $memberships
	 * @param Membership[] $known
	 *
	 * @return Membership[]
	 */
	private function createNewMemberships(array $memberships, array $known): array {
		$new = [];
		foreach ($memberships as $membership) {
			try {
				$item = $this->getMembershipsFromList($known, $membership->getCircleId());
				if ($item->getLevel() !== $membership->getLevel()
					|| $item->getInheritanceDepth() !== $membership->getInheritanceDepth()) {
					$this->membershipRequest->update($membership);
					$new[] = $item;
				} elseif ($item->getInheritancePath() !== $membership->getInheritancePath()) {
					$this->membershipRequest->update($membership);
				}
			} catch (ItemNotFoundException $e) {
				$this->membershipRequest->insert($membership);
				$new[] = $membership;
			}

			// clearing the getSharedWith() cache for singleId related to the membership
			$this->shareWrapperService->clearCache($membership->getSingleId());
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
	private function getMembershipsFromList(array $list, string $circleId): Membership {
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
	public function updatePopulation(Circle $circle): void {
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
