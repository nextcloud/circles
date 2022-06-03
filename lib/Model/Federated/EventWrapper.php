<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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


namespace OCA\Circles\Model\Federated;

use JsonSerializable;
use OCA\Circles\Tools\Db\IQueryRow;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Model\ReferencedDataStore;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Tools\Traits\TArrayTools;

/**
 * Class EventWrapper
 *
 * @package OCA\Circles\Model\Remote
 */
class EventWrapper implements IQueryRow, JsonSerializable {
	use TArrayTools;


	public const STATUS_INIT = 0;
	public const STATUS_FAILED = 1;
	public const STATUS_DONE = 8;
	public const STATUS_OVER = 9;

	public const TYPE_BROADCAST = 'broadcast';
	public const TYPE_INTERNAL = 'internal';


	/** @var string */
	private $token = '';

	/** @var FederatedEvent */
	private $event;

	private string $eventType;
	private ?ReferencedDataStore $store = null;

	/** @var SimpleDataStore */
	private $result;

	/** @var string */
	private $instance = '';

	/** @var int */
	private $interface = 0;

	/** @var int */
	private $severity = FederatedEvent::SEVERITY_LOW;

	/** @var int */
	private $retry = 0;

	/** @var int */
	private $status = 0;

	/** @var int */
	private $creation;


	public function __construct(string $eventType = '') {
		$this->eventType = $eventType;
		$this->result = new SimpleDataStore();
	}


	/**
	 * @return string
	 */
	public function getToken(): string {
		return $this->token;
	}

	/**
	 * @param string $token
	 *
	 * @return self
	 */
	public function setToken(string $token): self {
		$this->token = $token;

		return $this;
	}


	/**
	 * @return FederatedEvent
	 */
	public function getEvent(): FederatedEvent {
		return $this->event;
	}

	/**
	 * @param FederatedEvent $event
	 *
	 * @return self
	 */
	public function setEvent(FederatedEvent $event): self {
		$this->event = $event;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function hasEvent(): bool {
		return ($this->event !== null);
	}


	/**
	 * @return ReferencedDataStore
	 */
	public function getStore(): ReferencedDataStore {
		return $this->store;
	}

	/**
	 * @param ReferencedDataStore $store
	 *
	 * @return self
	 */
	public function setStore(ReferencedDataStore $store): self {
		$this->store = $store;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function hasStore(): bool {
		return ($this->store !== null);
	}


	/**
	 * @param string $eventType
	 *
	 * @return EventWrapper
	 */
	public function setEventType(string $eventType): self {
		$this->eventType = $eventType;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getEventType(): string {
		return $this->eventType;
	}


	/**
	 * @param SimpleDataStore $result
	 *
	 * @return $this
	 */
	public function setResult(SimpleDataStore $result): self {
		$this->result = $result;

		return $this;
	}

	/**
	 * @return SimpleDataStore
	 */
	public function getResult(): SimpleDataStore {
		return $this->result;
	}


	/**
	 * @return string
	 */
	public function getInstance(): string {
		return $this->instance;
	}

	/**
	 * @param string $instance
	 *
	 * @return self
	 */
	public function setInstance(string $instance): self {
		$this->instance = $instance;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getInterface(): int {
		return $this->interface;
	}

	/**
	 * @param int $interface
	 *
	 * @return self
	 */
	public function setInterface(int $interface): self {
		$this->interface = $interface;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getSeverity(): int {
		return $this->severity;
	}

	/**
	 * @param int $severity
	 *
	 * @return self
	 */
	public function setSeverity(int $severity): self {
		$this->severity = $severity;

		return $this;
	}

	/**
	 * @param int $retry
	 *
	 * @return EventWrapper
	 */
	public function setRetry(int $retry): self {
		$this->retry = $retry;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getRetry(): int {
		return $this->retry;
	}


	/**
	 * @return int
	 */
	public function getStatus(): int {
		return $this->status;
	}

	/**
	 * @param int $status
	 *
	 * @return self
	 */
	public function setStatus(int $status): self {
		$this->status = $status;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getCreation(): int {
		return $this->creation;
	}

	/**
	 * @param int $creation
	 *
	 * @return self
	 */
	public function setCreation(int $creation): self {
		$this->creation = $creation;

		return $this;
	}


	/**
	 * @param array $data
	 *
	 * @return IQueryRow
	 * @throws InvalidItemException
	 */
	public function importFromDatabase(array $data): IQueryRow {
		$this->setToken($this->get('token', $data));
		$this->setInstance($this->get('instance', $data));
		$this->setEventType($this->get('event_type', $data));
		$this->setInterface($this->getInt('interface', $data));
		$this->setSeverity($this->getInt('severity', $data, FederatedEvent::SEVERITY_LOW));
		$this->setStatus($this->getInt('status', $data, self::STATUS_INIT));

		if ($this->getEventType() === self::TYPE_BROADCAST) {
			$event = new FederatedEvent();
			$event->import($this->getArray('event', $data));
			$this->setEvent($event);
		}

		if ($this->getEventType() === self::TYPE_INTERNAL) {
			$store = new ReferencedDataStore();
			$store->import($this->getArray('store', $data));
			$this->setStore($store);
		}

//		try {
//			$store = new ReferencedDataStore();
//			$store->import($this->getArray('store', $data));
//			$this->setStore($store);
//		} catch (InvalidItemException $e) {
//		}

		$this->setResult(new SimpleDataStore($this->getArray('result', $data)));

		return $this;
	}


	/**
	 * @param array $data
	 *
	 * @return self
	 * @throws InvalidItemException
	 */
	public function import(array $data): self {
		$this->setToken($this->get('token', $data));
		$this->setInstance($this->get('instance', $data));
		$this->setEventType($this->get('eventType', $data));
		$this->setInterface($this->getInt('interface', $data));
		$this->setSeverity($this->getInt('severity', $data, FederatedEvent::SEVERITY_LOW));
		$this->setStatus($this->getInt('status', $data, self::STATUS_INIT));

		if ($this->getEventType() === self::TYPE_BROADCAST) {
			$event = new FederatedEvent();
			$event->import($this->getArray('event', $data));
			$this->setEvent($event);
		}

		if ($this->getEventType() === self::TYPE_INTERNAL) {
			$store = new ReferencedDataStore();
			$store->import($this->getArray('store', $data));
			$this->setStore($store);
		}

		$this->setResult(new SimpleDataStore($this->getArray('result', $data)));
		$this->setCreation($this->getInt('creation', $data));

		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'token' => $this->getToken(),
			'instance' => $this->getInstance(),
			'eventType' => $this->getEventType(),
			'interface' => $this->getInterface(),
			'event' => ($this->hasEvent()) ? $this->getEvent() : null,
			'store' => ($this->hasStore()) ? $this->getStore() : null,
			'result' => $this->getResult(),
			'severity' => $this->getSeverity(),
			'status' => $this->getStatus()
			//			'creation' => $this->getCreation()
		];
	}
}
