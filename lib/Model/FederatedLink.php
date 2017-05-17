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
	private $remoteCircleId;

	/** @var string */
	private $remoteCircleName;

	/** @var string */
	private $localCircleName;


	public function __construct() {
	}


	/**
	 * @param $token
	 *
	 * @return $this
	 */
	public function setToken(string $token) {
		$this->token = $token;

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
		$token = '';
		for ($i = 0; $i <= 5; $i++) {
			$token .= uniqid('', true);
		}

		$this->setToken($token);

		return $token;
	}


	/**
	 * @param string $address
	 *
	 * @return FederatedLink
	 */
	public function setAddress(string $address) {
		$this->address = $address;

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
	public function setLocalAddress(string $address) {
		$this->localAddress = $address;

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
	public function setRemoteCircleId(int $circleId) {
		$this->remoteCircleId = $circleId;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getRemoteCircleId() {
		return $this->remoteCircleId;
	}


	/**
	 * @param string $circleName
	 *
	 * @return FederatedLink
	 */
	public function setRemoteCircleName(string $circleName) {
		$this->remoteCircleName = $circleName;

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
	public function setLocalCircleName(string $circleName) {
		$this->localCircleName = $circleName;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getLocalCircleName() {
		return $this->localCircleName;
	}


	/**
	 * @param int $status
	 *
	 * @return FederatedLink
	 */
	public function setStatus(int $status) {
		$this->status = $status;

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