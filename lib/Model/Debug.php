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


namespace OCA\Circles\Model;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use JsonSerializable;
use OCA\Circles\Tools\Db\IQueryRow;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\IDeserializable;
use OCA\Circles\Tools\IReferencedObject;
use OCA\Circles\Tools\Model\ReferencedDataStore;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCA\Circles\Tools\Traits\TDeserialize;

class Debug implements
	IReferencedObject,
	IQueryRow,
	JsonSerializable {

	use TArrayTools;
	use TDeserialize;

	private int $id;
	private string $thread;
	private string $type;
	private string $circleId;
	private string $instance = '';
	private ReferencedDataStore $debug;
	private int $time = 0;


	/**
	 * @param ReferencedDataStore|null $data
	 * @param string $circleId
	 */
	public function __construct(
		?ReferencedDataStore $data = null,
		string $circleId = '',
		string $thread = '',
		string $type = ''
	) {
		if (!is_null($data)) {
			$this->setDebug($data);
		}

		$this->setThread($thread);
		$this->setCircleId($circleId);
		$this->setType($type);
	}


	/**
	 * @param int $id
	 *
	 * @return Debug
	 */
	public function setId(int $id): self {
		$this->id = $id;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}


	/**
	 * @param string $thread
	 *
	 * @return Debug
	 */
	public function setThread(string $thread): self {
		$this->thread = $thread;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getThread(): string {
		return $this->thread;
	}


	/**
	 * @param string $type
	 *
	 * @return Debug
	 */
	public function setType(string $type): self {
		$this->type = $type;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}


	/**
	 * @param string $circleId
	 *->
	 *
	 * @return Debug
	 */
	public function setCircleId(string $circleId): self {
		$this->circleId = $circleId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCircleId(): string {
		return $this->circleId;
	}


	/**
	 * @param string $instance
	 *
	 * @return Debug
	 */
	public function setInstance(string $instance): self {
		$this->instance = $instance;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getInstance(): string {
		return $this->instance;
	}


	/**
	 * @param ReferencedDataStore $debug
	 *
	 * @return Debug
	 */
	public function setDebug(ReferencedDataStore $debug): self {
		$this->debug = $debug;

		return $this;
	}

	/**
	 * @return ReferencedDataStore
	 */
	public function getDebug(): ReferencedDataStore {
		return $this->debug;
	}


	/**
	 * @param int $time
	 *
	 * @return Debug
	 */
	public function setTime(int $time): self {
		$this->time = $time;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getTime(): int {
		return $this->time;
	}


	/**
	 * @param array $data
	 *
	 * @return IDeserializable
	 * @throws InvalidItemException
	 */
	public function import(array $data): IDeserializable {
		$this->setThread($this->get('thread', $data));
		$this->setType($this->get('type', $data));
		$this->setCircleId($this->get('circleId', $data));
		$this->setInstance($this->get('instance', $data));
		$this->setTime($this->getInt('time', $data));

		/** @var ReferencedDataStore $store */
		$store = $this->deserialize($this->getArray('debug', $data), ReferencedDataStore::class);
		$this->setDebug($store);

		return $this;
	}


	/**
	 * @param array $data
	 * @param string $prefix
	 *
	 * @return IQueryRow
	 * @throws InvalidItemException
	 */
	public function importFromDatabase(array $data, string $prefix = ''): IQueryRow {
		if (empty($this->getArray($prefix . 'debug', $data))) {
			throw new InvalidItemException();
		}

		$this->setId($this->getInt($prefix . 'id', $data));
		$this->setThread($this->get($prefix . 'thread', $data));
		$this->setType($this->get($prefix . 'type', $data));
		$this->setCircleId($this->get($prefix . 'circle_id', $data));
		$this->setInstance($this->get($prefix . 'instance', $data));
		$this->setTime($this->getInt($prefix . 'time', $data));

		/** @var ReferencedDataStore $store */
		$store = $this->deserialize($this->getArray('debug', $data), ReferencedDataStore::class);
		$this->setDebug($store);

		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'thread' => $this->getThread(),
			'type' => $this->getType(),
			'circleId' => $this->getCircleId(),
			'instance' => $this->getInstance(),
			'debug' => $this->getDebug(),
			'time' => $this->getTime()
		];
	}
}
