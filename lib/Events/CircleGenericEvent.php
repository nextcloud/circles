<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Events;

use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCP\EventDispatcher\Event;

class CircleGenericEvent extends Event {
	public const INVITED = 1;
	public const JOINED = 2;
	public const ADDED = 3;
	public const REMOVED = 4;
	public const LEFT = 5;
	public const LEVEL = 6;
	public const NAME = 7;
	public const REQUESTED = 8;

	private Circle $circle;

	public function __construct(
		private FederatedEvent $federatedEvent,
	) {
		parent::__construct();
		$this->circle = $federatedEvent->getCircle();
	}


	/**
	 * @return FederatedEvent
	 */
	public function getFederatedEvent(): FederatedEvent {
		return $this->federatedEvent;
	}

	/**
	 * @return Circle
	 */
	public function getCircle(): Circle {
		return $this->circle;
	}
}
