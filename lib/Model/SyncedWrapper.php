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
use OCA\Circles\IFederatedUser;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\IDeserializable;
use OCA\Circles\Tools\IReferencedObject;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCA\Circles\Tools\Traits\TDeserialize;

class SyncedWrapper implements IReferencedObject, JsonSerializable {
	use TArrayTools;
	use TDeserialize;

	private ?IFederatedUser $federatedUser;
	private ?SyncedItem $item;
	private ?SyncedItemLock $lock;
	private ?SyncedShare $share;
	private array $extraData;

	public function __construct(
		?IFederatedUser $federatedUser = null,
		?SyncedItem $item = null,
		?SyncedItemLock $lock = null,
		?SyncedShare $share = null,
		array $extraData = []
	) {
		$this->federatedUser = $federatedUser;
		$this->item = $item;
		$this->share = $share;
		$this->lock = $lock;
		$this->extraData = $extraData;
	}


	/**
	 * @param IFederatedUser $federatedUser
	 *
	 * @return SyncedWrapper
	 */
	public function setFederatedUser(IFederatedUser $federatedUser): self {
		$this->federatedUser = $federatedUser;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function hasFederatedUser(): bool {
		return !is_null($this->federatedUser);
	}

	/**
	 * @return IFederatedUser
	 */
	public function getFederatedUser(): ?IFederatedUser {
		return $this->federatedUser;
	}


	/**
	 * @param SyncedItem $item
	 *
	 * @return SyncedWrapper
	 */
	public function setItem(SyncedItem $item): self {
		$this->item = $item;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function hasItem(): bool {
		return !is_null($this->item);
	}

	/**
	 * @return SyncedItem
	 */
	public function getItem(): ?SyncedItem {
		return $this->item;
	}


	/**
	 * @param SyncedItem $lock
	 *
	 * @return SyncedWrapper
	 */
	public function setLock(SyncedItemLock $lock): self {
		$this->lock = $lock;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function hasLock(): bool {
		return !is_null($this->lock);
	}

	/**
	 * @return SyncedItemLock
	 */
	public function getLock(): ?SyncedItemLock {
		return $this->lock;
	}


	/**
	 * @param SyncedShare $share
	 *
	 * @return SyncedWrapper
	 */
	public function setShare(SyncedShare $share): self {
		$this->share = $share;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function hasShare(): bool {
		return !is_null($this->share);
	}

	/**
	 * @return SyncedShare
	 */
	public function getShare(): ?SyncedShare {
		return $this->share;
	}


	/**
	 * @param array $extraData
	 *
	 * @return SyncedWrapper
	 */
	public function setExtraData(array $extraData): self {
		$this->extraData = $extraData;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getExtraData(): array {
		return $this->extraData;
	}


	/**
	 * @param array $data
	 *
	 * @return ShareToken
	 */
	public function import(array $data): IDeserializable {
		// TODO: use ReferencedDataStore
		try {
			/** @var IFederatedUser $user */
			$user = $this->deserialize($this->getArray('federatedUser', $data), FederatedUser::class);
			$this->setFederatedUser($user);
		} catch (InvalidItemException $e) {
		}

		try {
			/** @var SyncedItem $item */
			$item = $this->deserialize($this->getArray('item', $data), SyncedItem::class);
			$this->setItem($item);
		} catch (InvalidItemException $e) {
		}

		try {
			/** @var SyncedItemLock $lock */
			$lock = $this->deserialize($this->getArray('lock', $data), SyncedItemLock::class);
			$this->setLock($lock);
		} catch (InvalidItemException $e) {
		}

		try {
			/** @var SyncedShare $share */
			$share = $this->deserialize($this->getArray('share', $data), SyncedShare::class);
			$this->setShare($share);
		} catch (InvalidItemException $e) {
		}

		$this->setExtraData($this->getArray('extraData', $data));

		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'federatedUser' => $this->getFederatedUser(),
			'item' => $this->getItem(),
			'share' => $this->getShare(),
			'lock' => $this->getLock(),
			'extraData' => $this->getExtraData()
		];
	}
}
