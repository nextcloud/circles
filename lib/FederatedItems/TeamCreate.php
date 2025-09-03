<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\FederatedItems;

use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedEventDSyncException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemCircleCheckNotRequired;
use OCA\Circles\IFederatedItemHighSeverity;
use OCA\Circles\IFederatedItemMustBeInitializedLocally;
use OCA\Circles\Managers\TeamEntityManager;
use OCA\Circles\Managers\TeamManager;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Service\EventService;

class TeamCreate implements
	IFederatedItem,
	IFederatedItemHighSeverity,
	IFederatedItemCircleCheckNotRequired,
	IFederatedItemMustBeInitializedLocally {

	public function __construct(
		private TeamManager $teamManager,
		private TeamEntityManager $teamEntityManager,
		private TeamMembershipService $membershipService,
		private EventService $eventService,
	) {
	}

	public function verify(FederatedEvent $event): void {
		$team = $event->getTeam();

		$event->setOutcome($team->jsonSerialize());
	}

	/**
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedEventDSyncException
	 * @throws InvalidIdException
	 * @throws RequestBuilderException
	 */
	public function manage(FederatedEvent $event): void {
		$team = $event->getTeam();
		$owner = $team->getOwner();
		//
		//
		//
		//
		//
		try {
			$this->teamManager->getTeam($team->getSingleId());
			throw new FederatedEventDSyncException('Circle already exist');
		} catch (CircleNotFoundException $e) {
		}

		$this->teamManager->confirmNaming($team);

		try {
			$this->teamEntityManager->getTeamEntity($owner->getMemberSingleId());
			throw new FederatedEventDSyncException('Owner already exist');
		} catch (MemberNotFoundException $e) {
		}

		if ($owner->hasInvitedBy()) {
			$owner->setNoteObj('invitedBy', $owner->getInvitedBy());
		}

		$this->circleRequest->save($circle);
		$this->memberRequest->save($owner);

		$this->membershipService->onUpdate($owner->getSingleId());
		$this->membershipService->updatePopulation($circle);

		$this->eventService->circleCreating($event);
	}

	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
		$this->eventService->circleCreated($event, $results);
	}
}
