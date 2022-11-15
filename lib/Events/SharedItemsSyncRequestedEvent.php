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

use JsonSerializable;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCP\EventDispatcher\Event;

/**
 * Class CircleMemberAddedEvent
 *
 * @package OCA\Circles\Events
 */
class SharedItemsSyncRequestedEvent extends Event {
	/** @var FederatedEvent */
	private $federatedEvent;

	/** @var Circle */
	private $circle;


	/** @var array */
	private $sharedItems = [];


	/**
	 * CircleMemberAddedEvent constructor.
	 *
	 * @param FederatedEvent $federatedEvent
	 */
	public function __construct(FederatedEvent $federatedEvent) {
		parent::__construct();

		$this->federatedEvent = $federatedEvent;
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


	/**
	 * @param string $appId
	 * @param string $itemType
	 * @param JsonSerializable $item
	 */
	public function addSharedItem(string $appId, string $itemType, JsonSerializable $item): void {
		$this->initArray($appId, $itemType);
		$this->sharedItems[$appId][$itemType][] = $item;
	}

	/**
	 * @param string $appId
	 * @param string $source
	 * @param array $data
	 */
	public function addSharedArray(string $appId, string $source, array $data): void {
		$this->initArray($appId, $source);
		$this->sharedItems[$appId][$source][] = $data;
	}


	/**
	 * @param string $appId
	 * @param string $itemType
	 */
	private function initArray(string $appId, string $itemType) {
		if (!is_array($this->sharedItems[$appId])) {
			$this->sharedItems[$appId] = [];
		}

		if (!is_array($this->sharedItems[$appId][$itemType])) {
			$this->sharedItems[$appId][$itemType] = [];
		}
	}
}
