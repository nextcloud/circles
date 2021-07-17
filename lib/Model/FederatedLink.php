<?php
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

namespace OCA\Circles\Model;

use OCA\Circles\Exceptions\FederatedCircleStatusUpdateException;

class FederatedLink implements \JsonSerializable {
	public const STATUS_ERROR = -1;
	public const STATUS_LINK_REMOVE = 0;
	public const STATUS_LINK_DOWN = 1;
	public const STATUS_LINK_SETUP = 2;
	public const STATUS_REQUEST_DECLINED = 4;
	public const STATUS_REQUEST_SENT = 5;
	public const STATUS_LINK_REQUESTED = 6;
	public const STATUS_LINK_UP = 9;

	public const SHORT_UNIQUE_ID_LENGTH = 12;

	/** @var int */
	private $id;

	/** @var string */
	private $token;

	/** @var string */
	private $address;

	/** @var string */
	private $localAddress;

	/** @var int */
	private $status;

	/** @var int */
	private $creation;

	/** @var int */
	private $circleUniqueId;

	/** @var string */
	private $uniqueId = '';

	/** @var string */
	private $remoteCircleName;

	/** @var string */
	private $localCircleName;

	/** @var bool */
	private $fullJson = false;

	public function __construct() {
	}


	/**
	 * @param int $id
	 *
	 * @return FederatedLink
	 */
	public function setId($id) {
		$this->id = (int)$id;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @param $token
	 *
	 * @return $this
	 */
	public function setToken($token) {
		$this->token = (string)$token;

		return $this;
	}

	/**
	 * @param bool $full
	 *
	 * @return string
	 */
	public function getToken($full = false) {
		if ($full) {
			return $this->token;
		}

		return substr($this->token, 0, FederatedLink::SHORT_UNIQUE_ID_LENGTH);
	}


	/**
	 * @return string
	 */
	public function generateToken() {
		$token = bin2hex(openssl_random_pseudo_bytes(24));
		$this->setToken($token);

		return $token;
	}


	/**
	 * @param string $address
	 *
	 * @return FederatedLink
	 */
	public function setAddress($address) {
		$this->address = (string)$address;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAddress() {
		return $this->address;
	}


	/**
	 * @param string $address
	 *
	 * @return FederatedLink
	 */
	public function setLocalAddress($address) {
		$this->localAddress = (string)$address;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getLocalAddress() {
		return $this->localAddress;
	}


	/**
	 * @param string $circleUniqueId
	 *
	 * @return FederatedLink
	 */
	public function setCircleId($circleUniqueId) {
		$this->circleUniqueId = $circleUniqueId;

		return $this;
	}

	/**
	 * @param bool $full
	 *
	 * @return string
	 */
	public function getCircleId($full = false) {
		if ($full) {
			return $this->circleUniqueId;
		}

		return substr($this->circleUniqueId, 0, DeprecatedCircle::SHORT_UNIQUE_ID_LENGTH);
	}


	/**
	 * @param string $uniqueId
	 *
	 * @return FederatedLink
	 */
	public function setUniqueId($uniqueId) {
		$this->uniqueId = (string)$uniqueId;

		return $this;
	}

	/**
	 * @param bool $full
	 *
	 * @return string
	 */
	public function getUniqueId($full = false) {
		if ($full) {
			return $this->uniqueId;
		}

		return substr($this->uniqueId, 0, FederatedLink::SHORT_UNIQUE_ID_LENGTH);
	}


	/**
	 * @param string $circleName
	 *
	 * @return FederatedLink
	 */
	public function setRemoteCircleName($circleName) {
		$this->remoteCircleName = (string)$circleName;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getRemoteCircleName() {
		return $this->remoteCircleName;
	}


	/**
	 * @param string $circleName
	 *
	 * @return FederatedLink
	 */
	public function setCircleName($circleName) {
		$this->localCircleName = (string)$circleName;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCircleName() {
		return $this->localCircleName;
	}


	/**
	 * @param int $status
	 *
	 * @return FederatedLink
	 */
	public function setStatus($status) {
		$this->status = (int)$status;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getStatus() {
		return $this->status;
	}


	/**
	 * @param int $creation
	 *
	 * @return FederatedLink
	 */
	public function setCreation($creation) {
		if ($creation === null) {
			return $this;
		}

		$this->creation = $creation;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getCreation() {
		return $this->creation;
	}


	public function hasToBeValidStatusUpdate($status) {
		try {
			$this->hasToBeValidStatusUpdateWhileLinkDown($status);
			$this->hasToBeValidStatusUpdateWhileRequestDeclined($status);
			$this->hasToBeValidStatusUpdateWhileLinkRequested($status);
			$this->hasToBeValidStatusUpdateWhileRequestSent($status);
		} catch (FederatedCircleStatusUpdateException $e) {
			throw new FederatedCircleStatusUpdateException('The status could not be updated');
		}
	}


	/**
	 * @param $status
	 *
	 * @throws FederatedCircleStatusUpdateException
	 */
	private function hasToBeValidStatusUpdateWhileLinkDown($status) {
		if ($this->getStatus() === self::STATUS_LINK_DOWN) {
			return;
		}

		if ($status !== self::STATUS_LINK_REMOVE) {
			throw new FederatedCircleStatusUpdateException();
		}
	}


	/**
	 * @param $status
	 *
	 * @throws FederatedCircleStatusUpdateException
	 */
	private function hasToBeValidStatusUpdateWhileRequestDeclined($status) {
		if ($this->getStatus() !== self::STATUS_REQUEST_DECLINED
			&& $this->getStatus() !== self::STATUS_LINK_SETUP) {
			return;
		}

		if ($status !== self::STATUS_LINK_REMOVE) {
			throw new FederatedCircleStatusUpdateException();
		}
	}


	/**
	 * @param $status
	 *
	 * @throws FederatedCircleStatusUpdateException
	 */
	private function hasToBeValidStatusUpdateWhileLinkRequested($status) {
		if ($this->getStatus() !== self::STATUS_LINK_REQUESTED) {
			return;
		}

		if ($status !== self::STATUS_LINK_REMOVE && $status !== self::STATUS_LINK_UP) {
			throw new FederatedCircleStatusUpdateException();
		}
	}


	/**
	 * @param $status
	 *
	 * @throws FederatedCircleStatusUpdateException
	 */
	private function hasToBeValidStatusUpdateWhileRequestSent($status) {
		if ($this->getStatus() !== self::STATUS_REQUEST_SENT
			&& $this->getStatus() !== self::STATUS_LINK_UP
		) {
			return;
		}

		if ($status !== self::STATUS_LINK_REMOVE) {
			throw new FederatedCircleStatusUpdateException();
		}
	}


	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'token' => $this->getToken($this->fullJson),
			'address' => $this->getAddress(),
			'status' => $this->getStatus(),
			'circle_id' => $this->getCircleId(),
			'unique_id' => $this->getUniqueId($this->fullJson),
			'creation' => $this->getCreation()
		];
	}


	public function getJson($full = false) {
		$this->fullJson = $full;
		$json = json_encode($this);
		$this->fullJson = false;

		return $json;
	}


	public static function fromArray($arr) {
		if ($arr === null) {
			return null;
		}

		$link = new FederatedLink();

		$link->setId($arr['id']);
		$link->setToken($arr['token']);
		$link->setAddress($arr['address']);
		$link->setStatus($arr['status']);
		$link->setCircleId($arr['circle_id']);
		$link->setUniqueId($arr['unique_id']);
		$link->setCreation($arr['creation']);

		return $link;
	}


	public static function fromJSON($json) {
		return self::fromArray(json_decode($json, true));
	}
}
