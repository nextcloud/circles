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


use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21Logger;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Db\MembershipRequest;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Membership;


/**
 * Class MembershipService
 *
 * @package OCA\Circles\Service
 */
class MembershipService {

	use TNC21Logger;


	/** @var MembershipRequest */
	private $membershipRequest;

	/** @var MemberRequest */
	private $memberRequest;


	/**
	 * MembershipService constructor.
	 *
	 * @param MembershipRequest $membershipRequest
	 * @param MemberRequest $memberRequest
	 */
	public function __construct(MembershipRequest $membershipRequest, MemberRequest $memberRequest) {
		$this->membershipRequest = $membershipRequest;
		$this->memberRequest = $memberRequest;
	}


	/**
	 * @param IFederatedUser $member
	 *
	 * @return int
	 */
	public function onMemberUpdate(IFederatedUser $member): int {
		if ($member->getUserType() === Member::TYPE_CIRCLE) {
			$this->onCircleUpdate($member->getSingleId());
		} else {
			return $this->manageMemberships($member->getSingleId());
		}
	}


	/**
	 * @param string $circleId
	 */
	public function onCircleUpdate(string $circleId): void {

	}


	/**
	 * @param string $singleId
	 *
	 * @return int
	 */
	public function manageMemberships(string $singleId): int {
		$memberships = $this->generateMemberships($singleId);

		return $this->updateDatabaseMemberships($memberships, $singleId);
	}


	/**
	 * @param string $singleId
	 * @param string $circleId
	 * @param array $memberships
	 * @param array $knownIds
	 *
	 * @return array
	 */
	private function generateMemberships(
		string $singleId,
		string $circleId = '',
		array &$memberships = [],
		array $knownIds = []
	): array {
		$circleId = ($circleId === '') ? $singleId : $circleId;
		$knownIds[] = $circleId;

		$members = $this->memberRequest->getMembersBySingleId($circleId);
		foreach ($members as $member) {
			$membership = new Membership($member);
			$membership->setId($singleId);
			$this->fillMemberships($membership, $memberships);

			if (!in_array($member->getCircleId(), $knownIds)) {
				$this->generateMemberships($singleId, $member->getCircleId(), $memberships, $knownIds);
			}
		}

		return $memberships;
	}


	/**
	 * @param Membership $membership
	 * @param array $memberships
	 */
	private function fillMemberships(Membership $membership, array &$memberships) {
		foreach ($memberships as $known) {
			if ($known->getCircleId() === $membership->getCircleId()) {
				if ($known->getLevel() < $membership->getLevel()) {
					$known->setLevel($membership->getLevel());
					$known->setMemberId($membership->getMemberId());
				}

				return;
			}
		}

		$memberships[$membership->getCircleId()] = $membership;
	}


	/**
	 * @param Membership[] $memberships
	 * @param string $singleId
	 *
	 * @return int
	 */
	private function updateDatabaseMemberships(array $memberships, string $singleId): int {
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
		$circleIds = array_map(
			function(Membership $membership): string {
				return $membership->getCircleId();
			}, $known
		);

		$count = 0;
		foreach ($memberships as $item) {
			if ($item->getCircleId() === $item->getId()) {
				continue;
			}

			if (!in_array($item->getCircleId(), $circleIds)) {
				$this->membershipRequest->insert($item);
				$count++;
			} else {
				foreach ($known as $knownItem) {
					if ($knownItem->getCircleId() === $item->getCircleId()) {
						if ($knownItem->getLevel() !== $item->getLevel()) {
							$this->membershipRequest->update($item);
							$count++;
						}

						break;
					}
				}
			}
		}

		return $count;
	}

}

