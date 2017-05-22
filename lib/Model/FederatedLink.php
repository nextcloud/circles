<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
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

class FederatedLink implements \JsonSerializable {


	const STATUS_ERROR = 0;
	const STATUS_LINK_DOWN = 1;
	const STATUS_LINK_SETUP = 2;
	const STATUS_REQUEST_DECLINED = 4;
	const STATUS_REQUEST_SENT = 6;
	const STATUS_LINK_UP = 9;

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
	private $circleId;

	/** @var string */
	private $uniqueId = '';

	/** @var string */
	private $remoteCircleName;

	/** @var string */
	private $localCircleName;


	public function __construct() {
	}


	/**
	 * @param int $id
	 *
	 * @return FederatedLink
	 */
	public function setId($id) {
		$this->id = (int) $id;

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
		$this->token = (string) $token;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getToken() {
		return $this->token;
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
		$this->address = (string) $address;

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
		$this->localAddress = (string) $address;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getLocalAddress() {
		return $this->localAddress;
	}


	/**
	 * @param int $circleId
	 *
	 * @return FederatedLink
	 */
	public function setCircleId($circleId) {
		$this->circleId = (int)$circleId;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getCircleId() {
		return $this->circleId;
	}


	/**
	 * @param string $uniqueId
	 *
	 * @return FederatedLink
	 */
	public function setUniqueId($uniqueId) {
		$this->uniqueId = (string) $uniqueId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUniqueId() {
		return $this->uniqueId;
	}


	/**
	 * @param string $circleName
	 *
	 * @return FederatedLink
	 */
	public function setRemoteCircleName($circleName) {
		$this->remoteCircleName = (string) $circleName;

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
		$this->localCircleName = (string) $circleName;

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
		$this->status = (int) $status;

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


	public function isValid() {

		if ($this->getStatus() === FederatedLink::STATUS_REQUEST_SENT
			|| $this->getStatus() === FederatedLink::STATUS_LINK_UP
		) {
			return true;
		}

		return false;
	}

	public function jsonSerialize() {
		return array(
			'token'    => $this->getToken(),
			'address'  => $this->getAddress(),
			'status'   => $this->getStatus(),
			'creation' => $this->getCreation()
		);
	}

	public static function fromJSON($json) {
	}

}