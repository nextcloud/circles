<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2022
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
use OCA\Circles\Tools\Db\IQueryRow;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\IDeserializable;
use OCA\Circles\Tools\IReferencedObject;
use OCA\Circles\Tools\Traits\TArrayTools;

class SyncedItemLock implements IReferencedObject, IQueryRow, JsonSerializable {
	use TArrayTools;

	private int $id;
//	private string $singleId;
	private string $updateType;
	private string $updateTypeId;
	private int $time = 0;
	private bool $verifyChecksum;


	public function __construct(
		string $updateType = '',
		string $updateTypeId = '',
		bool $verifyChecksum = false
	) {
		$this->updateType = $updateType;
		$this->updateTypeId = $updateTypeId;
		$this->verifyChecksum = $verifyChecksum;
	}


	/**
	 * @param int $id
	 *
	 * @return SyncedItemLock
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


//	/**
//	 * @param string $singleId
//	 *
//	 * @return SyncedItemLock
//	 */
//	public function setSingleId(string $singleId): self {
//		$this->singleId = $singleId;
//
//		return $this;
//	}
//
//	/**
//	 * @return string
//	 */
//	public function getSingleId(): string {
//		return $this->singleId;
//	}

	/**
	 * @param string $updateType
	 *
	 * @return SyncedItemLock
	 */
	public function setUpdateType(string $updateType): self {
		$this->updateType = $updateType;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUpdateType(): string {
		return $this->updateType;
	}


	/**
	 * @param string $updateTypeId
	 *
	 * @return SyncedItemLock
	 */
	public function setUpdateTypeId(string $updateTypeId): self {
		$this->updateTypeId = $updateTypeId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUpdateTypeId(): string {
		return $this->updateTypeId;
	}


	/**
	 * @param int $time
	 *
	 * @return SyncedItemLock
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
	 * @param bool $verifyChecksum
	 *
	 * @return SyncedItemLock
	 */
	public function setVerifyChecksum(bool $verifyChecksum): self {
		$this->verifyChecksum = $verifyChecksum;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isVerifyChecksum(): bool {
		return $this->verifyChecksum;
	}


	/**
	 * @param array $data
	 *
	 * @return ShareToken
	 * @throws InvalidItemException
	 */
	public function import(array $data): IDeserializable {
//		if ($this->getInt('singleId', $data) === 0) {
//			throw new InvalidItemException();
//		}

//		$this->setSingleId($this->get('singleId', $data));
		$this->setUpdateType($this->get('updateType', $data));
		$this->setUpdateTypeId($this->get('updateTypeId', $data));
		$this->setTime($this->getInt('time', $data));

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
		if ($this->getInt($prefix . 'time', $data) < 1) {
			throw new InvalidItemException();
		}

		$this->setUpdateType($this->get($prefix . 'update_type', $data));
		$this->setUpdateTypeId($this->get($prefix . 'update_type_id', $data));
		$this->setTime($this->getInt($prefix . 'time', $data));

		return $this;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'updateType' => $this->getUpdateType(),
			'updateTypeId' => $this->getUpdateTypeId(),
			'time' => $this->getTime(),
			'verifyChecksum' => $this->isVerifyChecksum()
		];
	}
}
