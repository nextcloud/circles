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

	/** @var int */
	private $status;

	/** @var int */
	private $creation;

	/** @var int */
	private $remoteCircleId;

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
	 * @param string $address
	 */
	public function setAddress(string $address) {
		$this->address = $address;
	}

	/**
	 * @return string
	 */
	public function getAddress() {
		return $this->address;
	}


	/**
	 * @param int $circleId
	 */
	public function setRemoteCircleId(int $circleId) {
		$this->remoteCircleId = $circleId;
	}

	/**
	 * @return int
	 */
	public function getRemoteCircleId() {
		return $this->remoteCircleId;
	}


	/**
	 * @param int $status
	 */
	public function setStatus(int $status) {
		$this->status = $status;
	}

	/**
	 * @return int
	 */
	public function getStatus() {
		return $this->status;
	}


	/**
	 * @param int $creation
	 */
	public function setCreation($creation) {
		if ($creation === null) {
			return;
		}

		$this->creation = $creation;
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