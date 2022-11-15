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

use OCA\Circles\Model\Federated\FederatedEvent;

/**
 * Class CircleDestroyedEvent
 *
 * This Event is called when it has been confirmed that the Circle have been destroyed on all instances
 * related to the Circle.
 *
 * Meaning that the event won't be triggered until each instances have been once available during the
 * retry-on-fail initiated in a background job
 *
 * WARNING: Unlike DestroyingCircleEvent, this Event is only called on the master instance of the Circle.
 *
 * @package OCA\Circles\Events
 */
class CircleDestroyedEvent extends CircleResultGenericEvent {
	/**
	 * CircleDestroyedEvent constructor.
	 *
	 * @param FederatedEvent $federatedEvent
	 * @param array $results
	 */
	public function __construct(FederatedEvent $federatedEvent, array $results) {
		parent::__construct($federatedEvent, $results);
	}
}
