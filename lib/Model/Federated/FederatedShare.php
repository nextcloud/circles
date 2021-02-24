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


use daita\MySmallPhpTools\Db\Nextcloud\nc21\INC21QueryRow;
use daita\MySmallPhpTools\IDeserializable;
use daita\MySmallPhpTools\Traits\TArrayTools;
use JsonSerializable;
use OCA\Circles\IFederatedModel;


/**
 * Class FederatedShare
 *
 * @package OCA\Circles\Model\Federated
 */
class FederatedShare implements IFederatedModel, JsonSerializable, INC21QueryRow, IDeserializable {


	use TArrayTools;


	/** @var int */
	private $id = 0;

	/** @var string */
	private $uniqueId = '';

	/** @var string */
	private $circleId = '';

	/** @var string */
	private $instance = '';


	/**
	 * FederatedShare constructor.
	 */
	function __construct() {
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
	 * @param string $uniqueId
	 *
	 * @return FederatedShare
	 */
	public function setUniqueId(string $uniqueId): self {
		$this->uniqueId = $uniqueId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUniqueId(): string {
		return $this->uniqueId;
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
	 * @return string
	 */
	public function getInstance(): string {
		return $this->instance;
	}


	/**
	 * @param array $data
	 *
	 * @return IDeserializable
	 */
	public function import(array $data): IDeserializable {
		$this->setId($this->getInt('id', $data));
		$this->setUniqueId($this->get('uniqueId', $data));
		$this->setCircleId($this->get('circleId', $data));
		$this->setInstance($this->get('instance', $data));

		return $this;
	}


	/**
	 * @param array $data
	 *
	 * @return INC21QueryRow
	 */
	public function importFromDatabase(array $data): INC21QueryRow {
		$this->setId($this->getInt('id', $data));
		$this->setUniqueId($this->get('unique_id', $data));
		$this->setCircleId($this->get('circle_id', $data));
		$this->setInstance($this->get('instance', $data));

		if ($this->getInstance() === '') {
			$this->setInstance($this->get('_params.local', $data));
		}

		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'id'       => $this->getId(),
			'uniqueId' => $this->getUniqueId(),
			'circleId' => $this->getCircleId(),
			'instance' => $this->getInstance()
		];
	}

}

