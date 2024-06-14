<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Model\Federated;

use JsonSerializable;
use OCA\Circles\Tools\Db\IQueryRow;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
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


	/** @var string */
	private $token = '';

	/** @var FederatedEvent */
	private $event;

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


	public function __construct() {
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
	 * @return self
	 * @throws InvalidItemException
	 */
	public function import(array $data): self {
		$this->setToken($this->get('token', $data));
		$this->setInstance($this->get('instance', $data));
		$this->setInterface($this->getInt('interface', $data));
		$this->setSeverity($this->getInt('severity', $data, FederatedEvent::SEVERITY_LOW));
		$this->setStatus($this->getInt('status', $data, self::STATUS_INIT));

		$event = new FederatedEvent();
		$event->import($this->getArray('event', $data));
		$this->setEvent($event);

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
			'interface' => $this->getInterface(),
			'event' => $this->getEvent(),
			'result' => $this->getResult(),
			'severity' => $this->getSeverity(),
			'status' => $this->getStatus()
			//			'creation' => $this->getCreation()
		];
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
		$this->setInterface($this->getInt('interface', $data));
		$this->setSeverity($this->getInt('severity', $data, FederatedEvent::SEVERITY_LOW));
		$this->setStatus($this->getInt('status', $data, self::STATUS_INIT));

		$event = new FederatedEvent();
		$event->import($this->getArray('event', $data));
		$this->setEvent($event);

		$this->setResult(new SimpleDataStore($this->getArray('result', $data)));

		return $this;
	}
}
