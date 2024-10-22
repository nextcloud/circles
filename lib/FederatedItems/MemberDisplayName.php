<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\FederatedItems;

use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\FederatedItemBadRequestException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\MemberLevelException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemHighSeverity;
use OCA\Circles\IFederatedItemInitiatorMembershipNotRequired;
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
	IFederatedItemInitiatorMembershipNotRequired,
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
		ConfigService $configService,
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
