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

use JsonSerializable;
use OCA\Circles\Exceptions\SyncedShareNotFoundException;
use OCA\Circles\Tools\Db\IQueryRow;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\IDeserializable;
use OCA\Circles\Tools\IReferencedObject;
use OCA\Circles\Tools\Traits\TArrayTools;

class SyncedShare implements IReferencedObject, IQueryRow, JsonSerializable {
	use TArrayTools;

	private int $id;
	private string $singleId;
	private string $circleId;

	public function __construct() {
	}


	/**
	 * @param int $id
	 *
	 * @return SyncedShare
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
	 * @param string $singleId
	 *
	 * @return SyncedShare
	 */
	public function setSingleId(string $singleId): self {
		$this->singleId = $singleId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getSingleId(): string {
		return $this->singleId;
	}

	/**
	 * @param string $circleId
	 *
	 * @return SyncedShare
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
	 * @param array $data
	 *
	 * @return ShareToken
	 * @throws InvalidItemException
	 */
	public function import(array $data): IDeserializable {
		if ($this->get('singleId', $data) === ''
			|| $this->get('circleId', $data) === '') {
			throw new InvalidItemException();
		}

		$this->setSingleId($this->get('singleId', $data));
		$this->setCircleId($this->get('circleId', $data));

		return $this;
	}


	/**
	 * @param array $data
	 * @param string $prefix
	 *
	 * @return IQueryRow
	 * @throws SyncedShareNotFoundException
	 */
	public function importFromDatabase(array $data, string $prefix = ''): IQueryRow {
		if ($this->get($prefix . 'single_id', $data) === '') {
			throw new SyncedShareNotFoundException();
		}

		$this->setCircleId($this->get($prefix . 'circle_id', $data));
		$this->setSingleId($this->get($prefix . 'single_id', $data));

		return $this;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'singleId' => $this->getSingleId(),
			'circleId' => $this->getCircleId()
		];
	}
}
