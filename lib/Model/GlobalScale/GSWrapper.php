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


namespace OCA\Circles\Model\GlobalScale;

use OCA\Circles\Tools\Traits\TArrayTools;
use JsonSerializable;
use OCA\Circles\Exceptions\JsonException;
use OCA\Circles\Exceptions\ModelException;

/**
 * Class GSEvent
 *
 * @package OCA\Circles\Model\GlobalScale
 */
class GSWrapper implements JsonSerializable {
	use TArrayTools;


	public const STATUS_INIT = 0;
	public const STATUS_FAILED = 1;
	public const STATUS_DONE = 8;
	public const STATUS_OVER = 9;


	/** @var string */
	private $token = '';

	/** @var GSEvent */
	private $event;

	/** @var string */
	private $instance = '';

	/** @var int */
	private $severity = GSEvent::SEVERITY_LOW;

	/** @var int */
	private $status = 0;

	/** @var int */
	private $creation;


	public function __construct() {
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
	 * @return GSWrapper
	 */
	public function setToken(string $token): self {
		$this->token = $token;

		return $this;
	}


	/**
	 * @return GSEvent
	 */
	public function getEvent(): GSEvent {
		return $this->event;
	}

	/**
	 * @param GSEvent $event
	 *
	 * @return GSWrapper
	 */
	public function setEvent(GSEvent $event): self {
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
	 * @return string
	 */
	public function getInstance(): string {
		return $this->instance;
	}

	/**
	 * @param string $instance
	 *
	 * @return GSWrapper
	 */
	public function setInstance(string $instance): self {
		$this->instance = $instance;

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
	 * @return GSWrapper
	 */
	public function setSeverity(int $severity): self {
		$this->severity = $severity;

		return $this;
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
	 * @return GSWrapper
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
	 * @return GSWrapper
	 */
	public function setCreation(int $creation): self {
		$this->creation = $creation;

		return $this;
	}


	/**
	 * @param array $data
	 *
	 * @return GSWrapper
	 * @throws JsonException
	 * @throws ModelException
	 */
	public function import(array $data): self {
		$this->setToken($this->get('token', $data));
		$this->setInstance($this->get('instance', $data));
		$this->setSeverity($this->getInt('severity', $data, GSEvent::SEVERITY_LOW));
		$this->setStatus($this->getInt('status', $data, GSWrapper::STATUS_INIT));

		$event = new GSEvent();
		$event->importFromJson($this->get('event', $data));

		$this->setEvent($event);

		$this->setCreation($this->getInt('creation', $data));

		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		$arr = [
			'id' => $this->getToken(),
			'event' => $this->getEvent(),
			'severity' => $this->getSeverity(),
			'status' => $this->getStatus(),
			'creation' => $this->getCreation()
		];

		$this->cleanArray($arr);

		return $arr;
	}
}
