<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\FederatedItems;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\MemberHelperException;
use OCA\Circles\Exceptions\MemberLevelException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemAsyncProcess;
use OCA\Circles\IFederatedItemHighSeverity;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Helpers\MemberHelper;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\EventService;
use OCA\Circles\Service\MembershipService;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCA\Circles\Tools\Traits\TNCLogger;

/**
 * Class CircleLeaveParentCircles
 *
 * @package OCA\Circles\FederatedItems
 */
class CircleLeaveParentCircles implements
	IFederatedItem,
	IFederatedItemHighSeverity,
	IFederatedItemAsyncProcess {
	use TDeserialize;
	use TNCLogger;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var CircleRequest */
	private $circleRequest;

	/** @var MembershipService */
	private $membershipService;

	/** @var EventService */
	private $eventService;

	/** @var ConfigService */
	private $configService;

	/**
	 * CircleLeaveParentCircles constructor.
	 *
	 * @param MemberRequest $memberRequest
	 * @param CircleRequest $circleRequest
	 * @param MembershipService $membershipService
	 * @param EventService $eventService
	 * @param ConfigService $configService
	 */
	public function __construct(
		MemberRequest $memberRequest,
		CircleRequest $circleRequest,
		MembershipService $membershipService,
		EventService $eventService,
		ConfigService $configService,
	) {
		$this->memberRequest = $memberRequest;
		$this->circleRequest = $circleRequest;
		$this->membershipService = $membershipService;
		$this->eventService = $eventService;
		$this->configService = $configService;

		$this->setup('app', Application::APP_ID);
	}

	/**
	 * @param FederatedEvent $event
	 *
	 * @throws RequestBuilderException
	 * @throws MemberHelperException
	 * @throws MemberLevelException
	 */
	public function verify(FederatedEvent $event): void {
		$circle = $event->getCircle();
		$initiator = $circle->getInitiator();
		$initiatorHelper = new MemberHelper($initiator);
		$initiatorHelper->mustBeOwner();

		$event->setOutcome($this->serialize($circle));
	}

	/**
	 * @param FederatedEvent $event
	 *
	 * @throws RequestBuilderException
	 * @throws MemberNotFoundException
	 */
	public function manage(FederatedEvent $event): void {
		$circle = $event->getCircle();
		$parentCircles = $this->membershipService->getParentCircles($circle);
		foreach ($parentCircles as $parentCircle) {
			$member = $this->memberRequest->getMember(
				$parentCircle->getSingleId(),
				$circle->getSingleId()
			);
			$this->memberRequest->delete($member);
			$this->membershipService->onUpdate($member->getSingleId());
			$this->membershipService->updatePopulation($parentCircle);
		}
		$this->eventService->circleLeavingParentCircles($event);
	}

	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
		$this->eventService->circleLeftParentCircles($event, $results);
	}
}
