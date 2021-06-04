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


use daita\MySmallPhpTools\Traits\Nextcloud\nc22\TNC22Logger;
use daita\MySmallPhpTools\Traits\TStringTools;
use Exception;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemAsyncProcess;
use OCA\Circles\IFederatedItemHighSeverity;
use OCA\Circles\IFederatedItemMemberEmpty;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Helpers\MemberHelper;


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
	use TNC22Logger;


	/**
	 * @param FederatedEvent $event
	 */
	public function verify(FederatedEvent $event): void {
		$circle = $event->getCircle();
		$initiator = $circle->getInitiator();

		$initiatorHelper = new MemberHelper($initiator);
		$initiatorHelper->mustBeModerator();

		$members = $event->getMembers();
		$filtered = [];

		foreach ($members as $member) {
			try {
				$filtered[] = $this->generateMember($event, $circle, $member);
			} catch (Exception $e) {
			}
		}

		$event->setMembers($filtered);
		$event->setOutcome($filtered);
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws InvalidIdException
	 */
	public function manage(FederatedEvent $event): void {
		$members = $event->getMembers();

		foreach ($members as $member) {
			try {
				$member->setNoteObj('invitedBy', $member->getInvitedBy());

				$this->federatedUserService->confirmSingleIdUniqueness($member);
				$this->memberRequest->insertOrUpdate($member);
				$this->membershipService->onUpdate($member->getSingleId());
				$this->eventService->multipleMemberAdding($event);
			} catch (FederatedUserException | RequestBuilderException $e) {
			}
		}
	}


	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
		$this->eventService->multipleMemberAdded($event, $results);
	}

}

