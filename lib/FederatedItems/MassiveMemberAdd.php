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


namespace OCA\Circles\FederatedItems;

use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\Tools\Traits\TStringTools;
use Exception;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemAsyncProcess;
use OCA\Circles\IFederatedItemHighSeverity;
use OCA\Circles\IFederatedItemMemberEmpty;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Helpers\MemberHelper;
use OCA\Circles\Model\Member;

/**
 * Class MemberAdd
 *
 * @package OCA\Circles\GlobalScale
 */
class MassiveMemberAdd extends SingleMemberAdd implements
	IFederatedItem,
	IFederatedItemAsyncProcess,
	IFederatedItemHighSeverity,
	IFederatedItemMemberEmpty {
	use TStringTools;
	use TNCLogger;


	/**
	 * @param FederatedEvent $event
	 */
	public function verify(FederatedEvent $event): void {
		$circle = $event->getCircle();
		$initiator = $circle->getInitiator();

		$initiatorHelper = new MemberHelper($initiator);
		if (!$circle->isConfig(Circle::CFG_FRIEND)) {
			$initiatorHelper->mustBeModerator();
		}

		$members = $event->getMembers();
		$filtered = [];

		foreach ($members as $member) {
			try {
				$filtered[] = $this->generateMember($event, $circle, $member);
			} catch (Exception $e) {
				$this->e($e, ['event' => $event->getWrapperToken()]);
			}
		}

		$event->setMembers($filtered);
		$event->setOutcome($this->serializeArray($filtered));

		foreach ($event->getMembers() as $member) {
			$event->setMember($member);
			$this->eventService->memberPreparing($event);
		}
	}


	/**
	 * @param FederatedEvent $event
	 */
	public function manage(FederatedEvent $event): void {
		foreach ($event->getMembers() as $member) {
			try {
				if (!$this->memberService->insertOrUpdate($member)) {
					continue;
				}

				$event->setMember($member);
				if ($member->getStatus() === Member::STATUS_INVITED) {
					$this->eventService->memberInviting($event);
				} else {
					$this->eventService->memberAdding($event);
				}
			} catch (Exception $e) {
			}
		}

		$this->membershipService->updatePopulation($event->getCircle());
	}


	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
		foreach ($event->getMembers() as $member) {
			$event->setMember($member);
			$this->eventService->memberAdded($event, $results);
		}
	}
}
