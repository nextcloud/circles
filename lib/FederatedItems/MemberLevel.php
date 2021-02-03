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


namespace OCA\Circles\FederatedItems;


use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCA\Circles\Exceptions\MemberIsNotModeratorException;
use OCA\Circles\Exceptions\MemberIsNotOwnerException;
use OCA\Circles\Exceptions\MemberIsOwnerException;
use OCA\Circles\Exceptions\ModeratorIsNotHighEnoughException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemMustHaveMember;
use OCA\Circles\Model\DeprecatedCircle;
use OCA\Circles\Model\DeprecatedMember;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCA\Circles\Model\Member;


/**
 * Class MemberLevel
 *
 * @package OCA\Circles\FederatedItems
 */
class MemberLevel implements
	IFederatedItem,
	IFederatedItemMustHaveMember {


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedEventException
	 * @throws MemberDoesNotExistException
	 * @throws MemberIsNotModeratorException
	 * @throws MemberIsOwnerException
	 * @throws ModeratorIsNotHighEnoughException
	 */
	public function verify(FederatedEvent $event): void {
		$circle = $event->getCircle();
		$member = $event->getMember();
		$level = $event->getData()
					   ->gInt('level');

		if ($member->getLevel() === $level) {
			throw new FederatedEventException('This member already have the selected level');
		}


		if ($level === Member::LEVEL_OWNER) {
//			$this->verifySwitchOwner($event, $circle, $member);
		} else {
			$this->verifyMemberLevel($event, $circle, $member, $level);
		}

	}


	/**
	 * @param FederatedEvent $event
	 */
	public function manage(FederatedEvent $event): void {
		$level = $event->getData()
					   ->gInt('level');
//
//		$member = $event->getMember();
//		$this->cleanMember($member);
//
//		$member->setLevel($level);
//		$this->membersRequest->updateMemberLevel($member);
//
//		if ($level === DeprecatedMember::LEVEL_OWNER) {
//			$circle = $event->getDeprecatedCircle();
//			$isMod = $circle->getOwner();
//			if ($isMod->getInstance() === '') {
//				$isMod->setInstance($event->getSource());
//			}
//
//			$isMod->setLevel(DeprecatedMember::LEVEL_ADMIN);
//			$this->membersRequest->updateMemberLevel($isMod);
//		}
	}


	/**
	 * @param FederatedEvent[] $events
	 */
	public function result(array $events): void {
	}


	/**
	 * @param GSEvent $event
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 * @param int $level
	 *
	 * @throws MemberDoesNotExistException
	 * @throws MemberIsOwnerException
	 * @throws MemberIsNotModeratorException
	 * @throws ModeratorIsNotHighEnoughException
	 */
	private function verifyMemberLevel(
		GSEvent $event, DeprecatedCircle $circle, DeprecatedMember $member, int $level
	) {
		$member->hasToBeMember();
		$member->cantBeOwner();

		if (!$event->isForced()) {
			$isMod = $circle->getHigherViewer();
			$isMod->hasToBeModerator();
			$isMod->hasToBeHigherLevel($level);
			$isMod->hasToBeHigherLevel($member->getLevel());
		}
	}

	/**
	 * @param GSEvent $event
	 * @param DeprecatedCircle $circle
	 * @param DeprecatedMember $member
	 *
	 * @throws MemberDoesNotExistException
	 * @throws MemberIsNotOwnerException
	 * @throws MemberIsOwnerException
	 */
	private function verifySwitchOwner(GSEvent $event, DeprecatedCircle $circle, DeprecatedMember $member) {
		if (!$event->isForced()) {
			$isMod = $circle->getHigherViewer();
			$this->circlesService->hasToBeOwner($isMod);
		}

		$member->hasToBeMember();
		$member->cantBeOwner();
	}

}

