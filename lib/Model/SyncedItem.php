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
use OCA\Circles\Exceptions\SyncedItemNotFoundException;
use OCA\Circles\IFederatedModel;
use OCA\Circles\Tools\Db\IQueryRow;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\IDeserializable;
use OCA\Circles\Tools\IReferencedObject;
use OCA\Circles\Tools\Traits\TArrayTools;

class SyncedItem extends ManagedModel implements IFederatedModel, IReferencedObject, IQueryRow, JsonSerializable {
	use TArrayTools;

	private int $id;
	private string $singleId;
	private string $instance = '';
	private string $appId;
	private string $itemType;
	private string $itemId;
	private string $checksum = '';
	private array $serialized = [];
	private bool $deleted = false;

	public function __construct() {
	}


	/**
	 * @param int $id
	 *
	 * @return SyncedItem
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
	 * @return SyncedItem
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
	 * @param string $instance
	 *
	 * @return SyncedItem
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
	 * @return bool
	 */
	public function isLocal(): bool {
		return $this->getManager()->isLocalInstance($this->getInstance());
	}


	/**
	 * @param string $appId
	 *
	 * @return SyncedItem
	 */
	public function setAppId(string $appId): self {
		$this->appId = $appId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAppId(): string {
		return $this->appId;
	}


	/**
	 * @param string $itemType
	 *
	 * @return SyncedItem
	 */
	public function setItemType(string $itemType): self {
		$this->itemType = $itemType;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getItemType(): string {
		return $this->itemType;
	}


	/**
	 * @param string $itemId
	 *
	 * @return SyncedItem
	 */
	public function setItemId(string $itemId): self {
		$this->itemId = $itemId;

		return $this;
	}

	/**
	 * @param int $itemId
	 *
	 * @return SyncedItem
	 */
	public function setItemIdAsInt(int $itemId): self {
		$this->itemId = (string)$itemId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getItemId(): string {
		return $this->itemId;
	}

	/**
	 * @return int
	 */
	public function getItemIdAsInt(): int {
		return (int)$this->itemId;
	}

	/**
	 * @param string $checksum
	 *
	 * @return SyncedItem
	 */
	public function setChecksum(string $checksum): self {
		$this->checksum = $checksum;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getChecksum(): string {
		return $this->checksum;
	}


	/**
	 * @param array $serialized
	 *
	 * @return SyncedItem
	 */
	public function setSerialized(array $serialized): self {
		$this->serialized = $serialized;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getSerialized(): array {
		return $this->serialized;
	}


	/**
	 * @param bool $deleted
	 */
	public function setDeleted(bool $deleted): void {
		$this->deleted = $deleted;
	}

	/**
	 * @return bool
	 */
	public function isDeleted(): bool {
		return $this->deleted;
	}


	/**
	 * @param array $data
	 *
	 * @return ShareToken
	 * @throws InvalidItemException
	 */
	public function import(array $data): IDeserializable {
		if ($this->get('singleId', $data) === 0) {
			throw new InvalidItemException();
		}

		$this->setSingleId($this->get('singleId', $data));
		$this->setInstance($this->get('instance', $data));
		$this->setAppId($this->get('appId', $data));
		$this->setItemType($this->get('itemType', $data));
		$this->setItemId($this->get('itemId', $data));
		$this->setChecksum($this->get('checksum', $data));
		$this->setSerialized($this->getArray('serializedData', $data));
		$this->setDeleted($this->getBool('deleted', $data));

		return $this;
	}


	/**
	 * @param array $data
	 * @param string $prefix
	 *
	 * @return IQueryRow
	 * @throws SyncedItemNotFoundException
	 */
	public function importFromDatabase(array $data, string $prefix = ''): IQueryRow {
		if ($this->get($prefix . 'single_id', $data) === '') {
			throw new SyncedItemNotFoundException();
		}

		$this->setSingleId($this->get($prefix . 'single_id', $data));
		$this->setInstance($this->get($prefix . 'instance', $data));
		$this->setInstance($this->get($prefix . 'instance', $data));
		$this->setAppId($this->get($prefix . 'app_id', $data));
		$this->setItemType($this->get($prefix . 'item_type', $data));
		$this->setItemId($this->get($prefix . 'item_id', $data));
		$this->setChecksum($this->get($prefix . 'checksum', $data));
		$this->setDeleted($this->getBool($prefix . 'deleted', $data));

		if ($this->getInstance() === '') {
			$this->setInstance($this->getManager()->getLocalInstance());
		}

		return $this;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'singleId' => $this->getSingleId(),
			'instance' => $this->getInstance(),
			'appId' => $this->getAppId(),
			'itemType' => $this->getItemType(),
			'itemId' => $this->getItemId(),
			'checksum' => $this->getChecksum(),
			'serializedData' => $this->getSerialized(),
			'deleted' => $this->isDeleted()
		];
	}


	/**
	 * @param SyncedItem $item
	 *
	 * @return bool
	 */
	public function compareWith(SyncedItem $item): bool {
		return !($this->getSingleId() !== $item->getSingleId()
				 || $this->getAppId() !== $item->getAppId()
				 || $this->getItemType() !== $item->getItemType()
				 || $this->getItemId() !== $item->getItemId());
	}
}
