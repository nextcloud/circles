<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\FederatedItems;

use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\FederatedItemBadRequestException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemAsyncProcess;
use OCA\Circles\IFederatedItemHighSeverity;
use OCA\Circles\IFederatedItemMemberEmpty;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Helpers\MemberHelper;
use OCA\Circles\Service\EventService;
use OCA\Circles\Service\MembershipService;
use OCA\Circles\StatusCode;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCA\Circles\Tools\Traits\TStringTools;

/**
 * Class CircleDestroy
 *
 * @package OCA\Circles\FederatedItems
 */
class CircleDestroy implements
	IFederatedItem,
	IFederatedItemHighSeverity,
	IFederatedItemAsyncProcess,
	IFederatedItemMemberEmpty {
	use TStringTools;
	use TDeserialize;


	/** @var CircleRequest */
	private $circleRequest;

	/** @var MemberRequest */
	private $memberRequest;


	private $eventService;

	/** @var MembershipService */
	private $membershipService;


	/**
	 * CircleDestroy constructor.
	 *
	 * @param CircleRequest $circleRequest
	 * @param MemberRequest $memberRequest
	 * @param EventService $eventService
	 * @param MembershipService $membershipService
	 */
	public function __construct(
		CircleRequest $circleRequest, MemberRequest $memberRequest, EventService $eventService,
		MembershipService $membershipService,
	) {
		$this->circleRequest = $circleRequest;
		$this->memberRequest = $memberRequest;
		$this->eventService = $eventService;
		$this->membershipService = $membershipService;
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedItemBadRequestException
	 */
	public function verify(FederatedEvent $event): void {
		$circle = $event->getCircle();
		if ($circle->isConfig(Circle::CFG_APP)) {
			throw new FederatedItemBadRequestException(
				StatusCode::$CIRCLE_DESTROY[120],
				120
			);
		}

		$initiator = $circle->getInitiator();

		$initiatorHelper = new MemberHelper($initiator);
		$initiatorHelper->mustBeOwner();

		$event->setOutcome($this->serialize($circle));
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws RequestBuilderException
	 */
	public function manage(FederatedEvent $event): void {
		$circle = $event->getCircle();

		$this->eventService->circleDestroying($event);

		$this->circleRequest->delete($circle);
		$this->memberRequest->deleteAllFromCircle($circle);
		$this->membershipService->onUpdate($circle->getSingleId());
	}


	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
		$this->eventService->circleDestroyed($event, $results);
	}
}
