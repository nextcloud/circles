<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
