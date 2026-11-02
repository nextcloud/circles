<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\FederatedItems;

use OCA\Circles\AuditLog\Operation;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemAsyncProcess;
use OCA\Circles\IFederatedItemHighSeverity;
use OCA\Circles\IFederatedItemMemberRequired;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Helpers\MemberHelper;
use OCA\Circles\Service\AuditLogService;
use OCA\Circles\Service\EventService;
use OCA\Circles\Service\MembershipService;

/**
 * Class MemberLevel
 *
 * @package OCA\Circles\FederatedItems
 */
class MemberRemove implements
	IFederatedItem,
	IFederatedItemAsyncProcess,
	IFederatedItemHighSeverity,
	IFederatedItemMemberRequired {
	/**
	 * MemberAdd constructor.
	 *
	 * @param MemberRequest $memberRequest
	 * @param MembershipService $membershipService
	 * @param EventService $eventService
	 */
	public function __construct(
		private readonly MemberRequest $memberRequest,
		private readonly MembershipService $membershipService,
		private readonly EventService $eventService,
		private readonly AuditLogService $auditLogService,
	) {
	}

	/**
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedItemException
	 */
	public function verify(FederatedEvent $event): void {
		$circle = $event->getCircle();
		$member = $event->getMember();
		$initiator = $circle->getInitiator();

		$initiatorHelper = new MemberHelper($initiator);
		$initiatorHelper->mustBeModerator();
		$initiatorHelper->mustBeHigherLevelThan($member);

		$memberHelper = new MemberHelper($member);
		$memberHelper->cannotBeOwner();

		$event->setOutcome([]);
	}

	/**
	 * @param FederatedEvent $event
	 *
	 * @throws RequestBuilderException
	 */
	public function manage(FederatedEvent $event): void {
		$member = $event->getMember();
		$this->memberRequest->delete($member);

		$this->membershipService->onUpdate($member->getSingleId());
		// TODO: Remove invited members from this user that have not accepted their invitation

		$this->eventService->memberRemoving($event);
		$this->membershipService->updatePopulation($event->getCircle());

		$circle = $event->getCircle();
		$this->auditLogService->logTeamChange($circle, Operation::MemberRemoved, 'Member "%s" (%s, member %s) was removed from team %s ("%s") by "%s"', $this->auditLogService->getMemberParameters($member) + [
			'circleId' => $circle->getSingleId(),
			'name' => $this->auditLogService->getTeamName($circle),
			'initiator' => $this->auditLogService->getInitiator($circle),
		]);
	}

	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
		$this->eventService->memberRemoved($event, $results);
	}
}
