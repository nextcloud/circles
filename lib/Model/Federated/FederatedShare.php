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


namespace OCA\Circles\Model\Federated;

use OCA\Circles\Tools\Db\IQueryRow;
use OCA\Circles\Tools\IDeserializable;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Tools\Traits\TArrayTools;
use JsonSerializable;
use OCA\Circles\IFederatedModel;
use OCA\Circles\Model\ManagedModel;

/**
 * Class FederatedShare
 *
 * @package OCA\Circles\Model\Federated
 */
class FederatedShare extends ManagedModel implements IFederatedModel, JsonSerializable, IQueryRow, IDeserializable {
	use TArrayTools;


	/** @var int */
	private $id = 0;

	/** @var string */
	private $itemId = '';

	/** @var string */
	private $circleId = '';

	/** @var string */
	private $instance = '';

	/** @var string */
	private $lockStatus = '';

	/** @var SimpleDataStore */
	private $data;


	/**
	 * FederatedShare constructor.
	 */
	public function __construct() {
	}


	/**
	 * @param int $id
	 *
	 * @return FederatedShare
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
	 * @param string $itemId
	 *
	 * @return FederatedShare
	 */
	public function setItemId(string $itemId): self {
		$this->itemId = $itemId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getItemId(): string {
		return $this->itemId;
	}


	/**
	 * @param string $circleId
	 *
	 * @return FederatedShare
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
	 * @return FederatedShare
	 */
	public function setInstance(string $instance): self {
		$this->instance = $instance;

		return $this;
	}


	/**
	 * @param string $lockStatus
	 *
	 * @return FederatedShare
	 */
	public function setLockStatus(string $lockStatus): self {
		$this->lockStatus = $lockStatus;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getLockStatus(): string {
		return $this->lockStatus;
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
	 * @param array $data
	 *
	 * @return IDeserializable
	 */
	public function import(array $data): IDeserializable {
		$this->setId($this->getInt('id', $data));
		$this->setItemId($this->get('itemId', $data));
		$this->setCircleId($this->get('circleId', $data));
		$this->setInstance($this->get('instance', $data));

		return $this;
	}


	/**
	 * @param array $data
	 *
	 * @return IQueryRow
	 */
	public function importFromDatabase(array $data): IQueryRow {
		$this->setId($this->getInt('id', $data));
		$this->setItemId($this->get('item_id', $data));
		$this->setCircleId($this->get('circle_id', $data));
		$this->setInstance($this->get('instance', $data));

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
			'id' => $this->getId(),
			'itemId' => $this->getItemId(),
			'circleId' => $this->getCircleId(),
			'instance' => $this->getInstance()
		];
	}
}
