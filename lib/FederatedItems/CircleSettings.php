<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\FederatedItems;

use OCA\Circles\Db\CircleRequest;
use OCA\Circles\IFederatedItem;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Tools\Traits\TDeserialize;

/**
 * Class CircleSettings
 *
 * @package OCA\Circles\FederatedItems
 */
class CircleSettings implements IFederatedItem {
	use TDeserialize;


	/** @var CircleRequest */
	private $circleRequest;


	/**
	 * CircleSettings constructor.
	 *
	 * @param CircleRequest $circleRequest
	 */
	public function __construct(CircleRequest $circleRequest) {
		$this->circleRequest = $circleRequest;
	}


	/**
	 * @param FederatedEvent $event
	 */
	public function verify(FederatedEvent $event): void {
		$circle = $event->getCircle();
		$new = clone $circle;
		$event->setOutcome($this->serialize($new));
	}


	/**
	 * @param FederatedEvent $event
	 */
	public function manage(FederatedEvent $event): void {
	}


	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
	}
}
