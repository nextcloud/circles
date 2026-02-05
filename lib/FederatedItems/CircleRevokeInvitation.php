<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\FederatedItems;

use OCA\Circles\Db\CircleInvitationRequest;
use OCA\Circles\Exceptions\CircleNameTooShortException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Helpers\MemberHelper;
use OCA\Circles\Service\EventService;
use OCA\Circles\Tools\Traits\TDeserialize;

/**
 * Class CircleRevokeInvitation
 *
 * @package OCA\Circles\FederatedItems
 */
class CircleRevokeInvitation implements IFederatedItem {
	use TDeserialize;

	public function __construct(
		private CircleInvitationRequest $circleInvitationRequest,
		private EventService $eventService,
	) {
	}

	/**
	 * @param FederatedEvent $event
	 *
	 * @throws RequestBuilderException
	 * @throws CircleNameTooShortException
	 */
	public function verify(FederatedEvent $event): void {
		$circle = $event->getCircle();

		$initiatorHelper = new MemberHelper($circle->getInitiator());
		$initiatorHelper->mustBeAdmin();

		$new = clone $circle;
		$new->setCircleInvitation(null);

		$event->setOutcome($this->serialize($new));
	}

	/**
	 * @param FederatedEvent $event
	 *
	 * @throws RequestBuilderException
	 */
	public function manage(FederatedEvent $event): void {
		$circle = clone $event->getCircle();

		$this->circleInvitationRequest->delete($circle->getSingleId());
		// todo: do we need separate event here?
		$this->eventService->circleEditing($event);
	}

	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
		// todo: do we need separate event here?
		$this->eventService->circleEdited($event, $results);
	}
}
