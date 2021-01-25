<?php declare(strict_types=1);


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


namespace OCA\Circles\Model\Remote;


use daita\MySmallPhpTools\Traits\TArrayTools;
use JsonSerializable;


/**
 * Class RemoteWrapper
 *
 * @package OCA\Circles\Model\Remote
 */
class RemoteWrapper implements JsonSerializable {


	use TArrayTools;


	const STATUS_INIT = 0;
	const STATUS_FAILED = 1;
	const STATUS_DONE = 8;
	const STATUS_OVER = 9;


	/** @var string */
	private $token = '';

	/** @var RemoteEvent */
	private $event;

	/** @var string */
	private $instance = '';

	/** @var int */
	private $severity = RemoteEvent::SEVERITY_LOW;

	/** @var int */
	private $status = 0;

	/** @var int */
	private $creation;


	function __construct() {
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
	 * @return RemoteEvent
	 */
	public function getEvent(): RemoteEvent {
		return $this->event;
	}

	/**
	 * @param RemoteEvent $event
	 *
	 * @return self
	 */
	public function setEvent(RemoteEvent $event): self {
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
	 * @return self
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
	 * @return self
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
	 */
	public function import(array $data): self {
		$this->setToken($this->get('token', $data));
		$this->setInstance($this->get('instance', $data));
		$this->setSeverity($this->getInt('severity', $data, RemoteEvent::SEVERITY_LOW));
		$this->setStatus($this->getInt('status', $data, self::STATUS_INIT));

		$event = new RemoteEvent();
		$event->import($this->getArray('event', $data));
		$this->setEvent($event);

		$this->setCreation($this->getInt('creation', $data));

		return $this;
	}


	/**
	 * @return array
	 */
	function jsonSerialize(): array {
		return [
			'id'       => $this->getToken(),
			'event'    => $this->getEvent(),
			'severity' => $this->getSeverity(),
			'status'   => $this->getStatus(),
			'creation' => $this->getCreation()
		];
	}

}

