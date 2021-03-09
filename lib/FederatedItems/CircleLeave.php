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


use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemInitiatorMembershipNotRequired;
use OCA\Circles\IFederatedItemMemberOptional;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\StatusCode;


/**
 * Class CircleLeave
 *
 * @package OCA\Circles\FederatedItems
 */
class CircleLeave implements
	IFederatedItem,
	IFederatedItemInitiatorMembershipNotRequired,
	IFederatedItemMemberOptional {


	/** @var MemberRequest */
	private $memberRequest;

	/** @var CircleRequest */
	private $circleRequest;


	/**
	 * CircleLeave constructor.
	 *
	 * @param MemberRequest $memberRequest
	 * @param CircleRequest $circleRequest
	 */
	public function __construct(MemberRequest $memberRequest, CircleRequest $circleRequest) {
		$this->memberRequest = $memberRequest;
		$this->circleRequest = $circleRequest;
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedItemException
	 */
	public function verify(FederatedEvent $event): void {
		$circle = $event->getCircle();
		$member = $circle->getInitiator();

		if ($member->getId() === '') {
			throw new MemberNotFoundException(StatusCode::$CIRCLE_LEAVE[120], 120);
		}

		$event->setMember($member);
		$this->memberRequest->delete($member);

		$initiator = new FederatedUser();
		$initiator->importFromIFederatedUser($member);

		$outcome = $this->circleRequest->getCircle($circle->getId(), $initiator);

		$event->setOutcome($outcome->jsonSerialize());
	}


	/**
	 * @param FederatedEvent $event
	 */
	public function manage(FederatedEvent $event): void {
		$member = $event->getMember();
		$this->memberRequest->delete($member);
	}


	/**
	 * @param FederatedEvent[] $events
	 */
	public function result(array $events): void {
	}

}

