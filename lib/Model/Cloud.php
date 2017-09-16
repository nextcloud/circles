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

class Cloud {

	/** @var string */
	private $cloudId;

	/** @var string */
	private $address;

	/** @var int */
	private $status;

	/** @var string */
	private $note;

	/** @var string */
	private $created;


	/**
	 * RemoteMount constructor.
	 *
	 * @param string $cloudId
	 * @param string $address
	 */
	function __construct($cloudId = '', $address = '') {
		$this->setCloudId($cloudId);
		$this->setAddress($address);
	}


	/**
	 * @param string $cloudId
	 */
	public function setCloudId($cloudId) {
		$this->cloudId = $cloudId;
	}

	/**
	 * @return string
	 */
	public function getCloudId() {
		return $this->cloudId;
	}


	/**
	 * @param string $address
	 */
	public function setAddress($address) {
		$this->address = $address;
	}

	/**
	 * @return string
	 */
	public function getAddress() {
		return $this->address;
	}


	/**
	 * @param int $status
	 */
	public function setStatus($status) {
		$this->status = $status;
	}

	/**
	 * @return int
	 */
	public function getStatus() {
		return $this->status;
	}


	/**
	 * @param string $note
	 */
	public function setNote($note) {
		$this->note = $note;
	}

	/**
	 * @return string
	 */
	public function getNote() {
		return $this->note;
	}


	/**
	 * @param string $created
	 */
	public function setCreated($created) {
		$this->created = $created;
	}

	/**
	 * @return string
	 */
	public function getCreated() {
		return $this->created;
	}


}