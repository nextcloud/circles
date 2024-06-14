<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Model\Federated;

use JsonSerializable;
use OCA\Circles\IFederatedModel;
use OCA\Circles\Model\ManagedModel;
use OCA\Circles\Tools\Db\IQueryRow;
use OCA\Circles\Tools\IDeserializable;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Tools\Traits\TArrayTools;

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
