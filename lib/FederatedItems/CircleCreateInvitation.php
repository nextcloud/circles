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
use OCA\Circles\Model\CircleInvitation;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Helpers\MemberHelper;
use OCA\Circles\Service\EventService;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCP\Security\ISecureRandom;

/**
 * Class CircleCreateInvitation
 *
 * @package OCA\Circles\FederatedItems
 */
class CircleCreateInvitation implements IFederatedItem {
	use TDeserialize;

	public function __construct(
		private CircleInvitationRequest $circleInvitationRequest,
		private EventService $eventService,
		private ISecureRandom $random,
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

		$invitationCode = $this->random->generate(
			16,
			'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',
		);

		$circleInvitation = new CircleInvitation();
		$circleInvitation->setCircleId($circle->getSingleId());
		$circleInvitation->setInvitationCode($invitationCode);
		$circleInvitation->setCreatedBy($circle->getInitiator()->getUserId());

		$new->setCircleInvitation($circleInvitation);
		$event->getData()->sObj('circle_invitation', $circleInvitation);

		$event->setOutcome($this->serialize($new));
	}

	/**
	 * @param FederatedEvent $event
	 *
	 * @throws RequestBuilderException
	 */
	public function manage(FederatedEvent $event): void {
		/** @var CircleInvitation $circleInvitation */
		$circleInvitation = $event->getData()->gObj('circle_invitation');
		$this->circleInvitationRequest->replace($circleInvitation);

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
