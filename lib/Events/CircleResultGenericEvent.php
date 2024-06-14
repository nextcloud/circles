<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Events;

use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Member;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCP\EventDispatcher\Event;

/**
 * Class CircleResultGenericEvent
 *
 * @package OCA\Circles\Events
 */
class CircleResultGenericEvent extends Event {
	/** @var FederatedEvent */
	private $federatedEvent;

	/** @var SimpleDataStore[] */
	private $results;

	/** @var Circle */
	private $circle;

	/** @var Member */
	private $member;


	/**
	 * CircleResultGenericEvent constructor.
	 *
	 * @param FederatedEvent $federatedEvent
	 * @param SimpleDataStore[] $results
	 */
	public function __construct(FederatedEvent $federatedEvent, array $results) {
		parent::__construct();

		$this->federatedEvent = $federatedEvent;
		$this->results = $results;

		$this->circle = $federatedEvent->getCircle();
		if ($federatedEvent->hasMember()) {
			$this->member = $federatedEvent->getMember();
		}
	}


	/**
	 * @return FederatedEvent
	 */
	public function getFederatedEvent(): FederatedEvent {
		return $this->federatedEvent;
	}


	/**
	 * @return SimpleDataStore[]
	 */
	public function getResults(): array {
		return $this->results;
	}


	/**
	 * @return Circle
	 */
	public function getCircle(): Circle {
		return $this->circle;
	}


	/**
	 * @return bool
	 */
	public function hasMember(): bool {
		return (!is_null($this->member));
	}

	/**
	 * @return Member|null
	 */
	public function getMember(): ?Member {
		return $this->member;
	}
}
