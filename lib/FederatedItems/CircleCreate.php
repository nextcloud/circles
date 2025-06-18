<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\FederatedItems;

use OC\User\NoUserException;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedEventDSyncException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemCircleCheckNotRequired;
use OCA\Circles\IFederatedItemHighSeverity;
use OCA\Circles\IFederatedItemMustBeInitializedLocally;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\EventService;
use OCA\Circles\Service\MaintenanceService;
use OCA\Circles\Service\MembershipService;
use OCA\Circles\Tools\Traits\TDeserialize;

/**
 * Class CircleCreate
 *
 * @package OCA\Circles\FederatedItems
 */
class CircleCreate implements
	IFederatedItem,
	IFederatedItemHighSeverity,
	IFederatedItemCircleCheckNotRequired,
	IFederatedItemMustBeInitializedLocally {
	use TDeserialize;

	public function __construct(
		private CircleRequest $circleRequest,
		private MemberRequest $memberRequest,
		private CircleService $circleService,
		private MembershipService $membershipService,
		private MaintenanceService $maintenanceService,
		private EventService $eventService,
	) {
	}


	/**
	 * @param FederatedEvent $event
	 */
	public function verify(FederatedEvent $event): void {
		$circle = $event->getCircle();

		$event->setOutcome($this->serialize($circle));
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedEventDSyncException
	 * @throws InvalidIdException
	 * @throws RequestBuilderException
	 */
	public function manage(FederatedEvent $event): void {
		$circle = $event->getCircle();
		$owner = $circle->getOwner();

		try {
			$this->circleRequest->getCircle($circle->getSingleId());
			throw new FederatedEventDSyncException('Circle already exist');
		} catch (CircleNotFoundException $e) {
		}

		$this->circleService->confirmName($circle);

		try {
			$this->memberRequest->getMemberById($owner->getId());
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

		try {
			$this->maintenanceService->updateDisplayName($owner);
		} catch (NoUserException) {
			// ignoreable
		}

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
