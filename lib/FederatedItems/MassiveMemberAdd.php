<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\FederatedItems;

use Exception;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemAsyncProcess;
use OCA\Circles\IFederatedItemHighSeverity;
use OCA\Circles\IFederatedItemMemberEmpty;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Helpers\MemberHelper;
use OCA\Circles\Model\Member;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\Tools\Traits\TStringTools;

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
