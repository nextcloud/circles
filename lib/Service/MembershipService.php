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


namespace OCA\Circles\Service;

use ArtificialOwl\MySmallPhpTools\Exceptions\ItemNotFoundException;
use ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc22\TNC22Logger;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Db\MembershipRequest;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\MembershipNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Membership;
use OCA\Circles\Model\Probes\CircleProbe;

/**
 * Class MembershipService
 *
 * @package OCA\Circles\Service
 */
class MembershipService {
	use TNC22Logger;


	/** @var MembershipRequest */
	private $membershipRequest;

	/** @var CircleRequest */
	private $circleRequest;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var EventService */
	private $eventService;

	/** @var OutputService */
	private $outputService;


	/**
	 * MembershipService constructor.
	 *
	 * @param MembershipRequest $membershipRequest
	 * @param CircleRequest $circleRequest
	 * @param MemberRequest $memberRequest
	 * @param EventService $eventService
	 * @param OutputService $outputService
	 */
	public function __construct(
		MembershipRequest $membershipRequest,
		CircleRequest $circleRequest,
		MemberRequest $memberRequest,
		EventService $eventService,
		OutputService $outputService
	) {
		$this->membershipRequest = $membershipRequest;
		$this->circleRequest = $circleRequest;
		$this->memberRequest = $memberRequest;
		$this->eventService = $eventService;
		$this->outputService = $outputService;
	}


	/**
	 * @param string $singleId
	 *
	 * @throws RequestBuilderException
	 */
	public function onUpdate(string $singleId): void {
		if ($singleId === '') {
			return;
		}

		try {
			$this->circleRequest->getFederatedUserBySingleId($singleId);
		} catch (FederatedUserNotFoundException | OwnerNotFoundException $e) {
			$this->membershipRequest->removeBySingleId($singleId);
		}

		$children = array_unique(
			array_merge(
				[$singleId],
				$this->getChildrenMembers($singleId),
				$this->getChildrenMemberships($singleId)
			)
		);

		foreach ($children as $singleId) {
			$this->manageMemberships($singleId);
		}
	}


	/**
	 *
	 */
	public function manageAll(): void {
		$probe = new CircleProbe();
		$probe->includeSystemCircles();
		$circles = $this->circleRequest->getCircles(null, $probe);

		$this->outputService->startMigrationProgress(sizeof($circles));

		foreach ($circles as $circle) {
			$this->outputService->output(
				'Caching memberships for \'' . $circle->getDisplayName() . '\'',
				true
			);
			$this->manageMemberships($circle->getSingleId());
		}

		$this->outputService->finishMigrationProgress();
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


	/**
	 * @param string $circleId
	 * @param string $singleId
	 *
	 * @return Membership
	 * @throws MembershipNotFoundException
	 * @throws RequestBuilderException
	 */
	public function getMembership(string $circleId, string $singleId): Membership {
		$membership = $this->membershipRequest->getMembership($circleId, $singleId);
		$details = $this->circleRequest->getCirclesByIds($membership->getInheritancePath());
		$membership->setInheritanceDetails($details);

		return $membership;
	}


	/**
	 * @param string $singleId
	 * @param bool $all
	 */
	public function resetMemberships(string $singleId = '', bool $all = false) {
		$this->membershipRequest->removeBySingleId($singleId, $all);
	}


	/**
	 * @param FederatedUser $federatedUser
	 */
	public function deleteFederatedUser(FederatedUser $federatedUser) {
		$this->membershipRequest->deleteFederatedUser($federatedUser);
	}


	/**
	 * @param string $singleId
	 * @param string $circleId
	 * @param array $memberships
	 * @param array $knownIds
	 * @param array $path
	 *
	 * @return array
	 * @throws RequestBuilderException
	 */
	private function generateMemberships(
		string $singleId,
		string $circleId = '',
		array &$memberships = [],
		array $knownIds = [],
		array $path = []
	): array {
		$circleId = ($circleId === '') ? $singleId : $circleId;
		$path[] = $circleId;
		$knownIds[] = $circleId;

		$members = $this->memberRequest->getMembersBySingleId($circleId);
		foreach ($members as $member) {
			if (!$member->hasCircle() || $member->getLevel() < Member::LEVEL_MEMBER) {
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
			function (Member $item): string {
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
			function (Membership $item): string {
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
	 * @param Membership $membership
	 * @param Membership[] $memberships
	 */
	private function fillMemberships(Membership $membership, array &$memberships) {
		foreach ($memberships as $known) {
			if ($known->getCircleId() === $membership->getCircleId()) {
				if ($known->getLevel() < $membership->getLevel()) {
					$known->setLevel($membership->getLevel());
//					$known->setMemberId($membership->getMemberId());
					$known->setSingleId($membership->getSingleId());
					$known->setInheritanceLast($membership->getInheritanceLast());
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
			function (Membership $membership): string {
				return $membership->getCircleId();
			}, $memberships
		);

		$deprecated = [];
		foreach ($known as $item) {
			if (!in_array($item->getCircleId(), $circleIds)) {
				$deprecated[] = $item;
				$this->membershipRequest->delete($item);
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
				if ($item->getLevel() !== $membership->getLevel()) {
					$this->membershipRequest->update($membership);
					$new[] = $item;
				}
			} catch (ItemNotFoundException $e) {
				$this->membershipRequest->insert($membership);
				$new[] = $membership;
			}
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
}
