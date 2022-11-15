<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Circles\Events;

use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Member;
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
