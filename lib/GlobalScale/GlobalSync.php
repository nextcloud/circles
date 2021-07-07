<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
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


namespace OCA\Circles\GlobalScale;

use OCA\Circles\Exceptions\CircleAlreadyExistsException;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\MemberAlreadyExistsException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCA\Circles\Model\DeprecatedMember;

/**
 * Class GlobalSync
 *
 * @package OCA\Circles\GlobalScale
 */
class GlobalSync extends AGlobalScaleEvent {


	/**
	 * @param GSEvent $event
	 * @param bool $localCheck
	 * @param bool $mustBeChecked
	 */
	public function verify(GSEvent $event, bool $localCheck = false, bool $mustBeChecked = false): void {
	}


	/**
	 * @param GSEvent $event
	 */
	public function manage(GSEvent $event): void {
		$data = $event->getData();
		$circles = [];
		foreach ($data->gAll() as $circle) {
			$circle = DeprecatedCircle::fromArray($circle, true);
			$circles[] = $circle;

			$this->syncCircle($circle, $event->getSource());
			$this->removeDeprecateMembers($circle, $event->getSource());
		}
	}


	/**
	 * @param GSEvent[] $events
	 */
	public function result(array $events): void {
	}


	/**
	 * @param DeprecatedCircle $circle
	 * @param string $source
	 */
	private function syncCircle(DeprecatedCircle $circle, string $source): void {
		try {
			$knownCircle = $this->circlesRequest->forceGetCircle($circle->getUniqueId(), true);

			if (!$this->compareCircles($knownCircle, $circle)) {
				try {
					$this->circlesRequest->updateCircle($circle);
				} catch (CircleAlreadyExistsException $e) {
				}
			}
		} catch (CircleDoesNotExistException $e) {
			try {
				$this->circlesRequest->createCircle($circle);
			} catch (CircleAlreadyExistsException $e) {
			}
		}

		foreach ($circle->getMembers() as $member) {
			if ($member->getInstance() === '') {
				$member->setInstance($source);
			}

			try {
				$knownMember = $this->membersRequest->forceGetMember(
					$circle->getUniqueId(), $member->getUserId(), $member->getType(), $member->getInstance()
				);

				if ($this->compareMembers($knownMember, $member)) {
					continue;
				}

				$this->miscService->log(
					'updating member :' . json_encode($member) . ' from ' . json_encode($knownMember), 2
				);
				$this->membersRequest->updateMemberLevel($member);
			} catch (MemberDoesNotExistException $e) {
				try {
					$this->miscService->log(
						'creating member :' . json_encode($member), 2
					);
					$this->membersRequest->createMember($member);
				} catch (MemberAlreadyExistsException $e) {
				}
			}
		}
	}


	private function removeDeprecateMembers(DeprecatedCircle $circle, string $source): void {
		$knownMembers = $this->membersRequest->forceGetMembers($circle->getUniqueId());

		foreach ($knownMembers as $knownItem) {
			try {
				$this->getMember($knownItem, $circle->getMembers(), $source);
			} catch (MemberDoesNotExistException $e) {
				$this->miscService->log('removing deprecated member :' . json_encode($knownItem), 2);
				$this->membersRequest->removeMember($knownItem);
				$this->gsSharesRequest->removeGSSharesFromMember($knownItem);
			}
		}
	}


	/**
	 * @param DeprecatedMember $item
	 * @param DeprecatedMember[] $members
	 * @param string $source
	 *
	 * @throws MemberDoesNotExistException
	 */
	private function getMember(DeprecatedMember $item, array $members, string $source) {
		foreach ($members as $member) {
			if ($member->getInstance() === '') {
				$member->setInstance($source);
			}

			if ($this->compareMembers($member, $item)) {
				return;
			}
		}

		throw new MemberDoesNotExistException();
	}
}
