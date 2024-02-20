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
		private FederatedEvent $federatedEvent
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
