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


namespace OCA\Circles\Service;


use OCA\Circles\Events\CircleMemberAddedEvent;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCP\EventDispatcher\IEventDispatcher;


/**
 * Class CircleEventService
 *
 * @package OCA\Circles\Service
 */
class CircleEventService {


	/** @var IEventDispatcher */
	private $eventDispatcher;


	/**
	 * CircleEventService constructor.
	 *
	 * @param IEventDispatcher $eventDispatcher
	 */
	public function __construct(IEventDispatcher $eventDispatcher) {
		$this->eventDispatcher = $eventDispatcher;
	}


	/**
	 * @param FederatedEvent $event
	 */
	public function onMemberAdded(FederatedEvent $event): void {
		$this->eventDispatcher->dispatchTyped(new CircleMemberAddedEvent($event));
	}

}

