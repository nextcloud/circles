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

use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\FederatedItemBadRequestException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\MemberLevelException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemHighSeverity;
use OCA\Circles\IFederatedItemMemberEmpty;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\EventService;
use OCA\Circles\Service\MembershipService;
use OCA\Circles\StatusCode;
use OCA\Circles\Tools\Traits\TDeserialize;

/**
 * Class MemberDisplayName
 *
 * @package OCA\Circles\FederatedItems
 */
class MemberDisplayName implements
	IFederatedItem,
	IFederatedItemHighSeverity,
	IFederatedItemMemberEmpty {
	use TDeserialize;


	/** @var MemberRequest */
	private $memberRequest;

	/** @var MembershipService */
	private $membershipService;

	/** @var EventService */
	private $eventService;

	/** @var ConfigService */
	private $configService;


	/**
	 * MemberDisplayName constructor.
	 *
	 * @param MemberRequest $memberRequest
	 * @param MembershipService $membershipService
	 * @param EventService $eventService
	 * @param ConfigService $configService
	 */
	public function __construct(
		MemberRequest $memberRequest,
		MembershipService $membershipService,
		EventService $eventService,
		ConfigService $configService
	) {
		$this->memberRequest = $memberRequest;
		$this->membershipService = $membershipService;
		$this->eventService = $eventService;
		$this->configService = $configService;
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedItemException
	 * @throws FederatedItemBadRequestException
	 * @throws MemberLevelException
	 */
	public function verify(FederatedEvent $event): void {
		$member = $event->getCircle()
						->getInitiator();

		$displayName = $event->getParams()->g('displayName');

		if ($displayName === '') {
			throw new FederatedItemBadRequestException(StatusCode::$MEMBER_DISPLAY_NAME[120], 120);
		}

		$event->getData()->s('displayName', $displayName);

		$outcomeMember = clone $member;
		$outcomeMember->setDisplayName($displayName);

		$event->setOutcome($this->serialize($outcomeMember));
	}


	/**
	 * @param FederatedEvent $event
	 */
	public function manage(FederatedEvent $event): void {
		$circle = $event->getCircle();
		$member = $circle->getInitiator();
		$displayName = $event->getData()->g('displayName');

		$member->setDisplayName($displayName);
		$this->memberRequest->updateDisplayName($member->getSingleId(), $displayName, $circle->getSingleId());

		$event->setMember($member);
		$this->eventService->memberNameEditing($event);
	}


	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
		$this->eventService->memberNameEdited($event, $results);
	}
}
