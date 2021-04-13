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


use daita\MySmallPhpTools\Exceptions\ItemNotFoundException;
use daita\MySmallPhpTools\Traits\Nextcloud\nc22\TNC22Logger;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Db\MembershipRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Membership;


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


	/**
	 * MembershipService constructor.
	 *
	 * @param MembershipRequest $membershipRequest
	 * @param CircleRequest $circleRequest
	 * @param MemberRequest $memberRequest
	 */
	public function __construct(
		MembershipRequest $membershipRequest,
		CircleRequest $circleRequest,
		MemberRequest $memberRequest
	) {
		$this->membershipRequest = $membershipRequest;
		$this->circleRequest = $circleRequest;
		$this->memberRequest = $memberRequest;
	}


	/**
	 * @param string $singleId
	 */
	public function onUpdate(string $singleId): void {
		if ($singleId === '') {
			return;
		}

		try {
			$this->circleRequest->getFederatedUserBySingleId($singleId);
		} catch (CircleNotFoundException | FederatedUserNotFoundException | OwnerNotFoundException $e) {
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
	 * @param string $singleId
	 *
	 * @return int
	 */
	public function manageMemberships(string $singleId): int {
		$memberships = $this->generateMemberships($singleId);

		return $this->updateMembershipsDatabase($singleId, $memberships);
	}


	/**
	 * @param string $id
	 *
	 * @return array
	 */
	private function getChildrenMembers(string $id): array {
		$singleIds = array_map(
			function(Member $item): string {
				return $item->getSingleId();
			}, $this->memberRequest->getMembers($id)
		);

		foreach ($singleIds as $singleId) {
			if ($singleId !== $id) {
				$singleIds = array_merge($singleIds, $this->getChildrenMembers($singleId));
			}
		}

		return array_unique($singleIds);
	}


	/**
	 * @param string $id
	 *
	 * @return array
	 */
	private function getChildrenMemberships(string $id): array {
		$singleIds = array_map(
			function(Membership $item): string {
				return $item->getSingleId();
			}, $this->membershipRequest->getChildren($id)
		);

		foreach ($singleIds as $singleId) {
			if ($singleId !== $id) {
				$singleIds = array_merge($singleIds, $this->getChildrenMemberships($singleId));
			}
		}

		return array_unique($singleIds);
	}


	/**
	 * @param string $singleId
	 * @param string $circleId
	 * @param array $memberships
	 * @param array $knownIds
	 * @param array $path
	 *
	 * @return array
	 */
	public function generateMemberships(
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
			$membership = new Membership($member, $singleId, $circleId);
			$membership->setPath(array_reverse($path));
			$this->fillMemberships($membership, $memberships);

			if (!in_array($member->getCircleId(), $knownIds)) {
				$this->generateMemberships($singleId, $member->getCircleId(), $memberships, $knownIds, $path);
			}
		}

		return $memberships;
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
					$known->setMemberId($membership->getMemberId());
					$known->setSingleId($membership->getSingleId());
					$known->setParent($membership->getParent());
				}

				return;
			}
		}

		$memberships[$membership->getCircleId()] = $membership;
	}


	/**
	 * @param string $singleId
	 * @param Membership[] $memberships
	 *
	 * @return int
	 */
	public function updateMembershipsDatabase(string $singleId, array $memberships): int {
		$known = $this->membershipRequest->getMemberships($singleId);

		$count = 0;
		$count += $this->removeDeprecatedMemberships($memberships, $known);
		$count += $this->createNewMemberships($memberships, $known);

		return $count;
	}


	/**
	 * @param array $memberships
	 * @param array $known
	 *
	 * @return int
	 */
	private function removeDeprecatedMemberships(array $memberships, array $known): int {
		$circleIds = array_map(
			function(Membership $membership): string {
				return $membership->getCircleId();
			}, $memberships
		);

		$count = 0;
		foreach ($known as $item) {
			if (!in_array($item->getCircleId(), $circleIds)) {
				$this->membershipRequest->delete($item);
				$count++;
			}
		}

		return $count;
	}


	/**
	 * @param array $memberships
	 * @param array $known
	 *
	 * @return int
	 */
	private function createNewMemberships(array $memberships, array $known): int {
		$count = 0;
		foreach ($memberships as $membership) {
			try {
				$item = $this->getMembershipsFromList($known, $membership->getCircleId());
				if ($item->getLevel() !== $membership->getLevel()) {
					$this->membershipRequest->update($membership);
					$count++;
				}
			} catch (ItemNotFoundException $e) {
				$this->membershipRequest->insert($membership);
				$count++;
			}
		}

		return $count;
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
	 * @param string $singleId
	 * @param bool $all
	 */
	public function resetMemberships(string $singleId = '', bool $all = false) {
		$this->membershipRequest->removeBySingleId($singleId, $all);
	}

}

