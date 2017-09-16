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

class RemoteMount {

	/** @var string */
	private $circleId;

	/** @var string */
	private $remoteCircleId;

	/** @var Cloud */
	private $remoteCloud;

	/** @var string */
	private $token;

	/** @var string */
	private $password;

	/** @var string */
	private $remoteFilename;

	/** @var string */
	private $author;

	/** @var string */
	private $mountPoint;

	/** @var string */
	private $mountPointHash;

	/** @var string */
	private $created;


	/**
	 * RemoteMount constructor.
	 */
	function __construct() {
	}


	/**
	 * @param string $circleId
	 *
	 * @return $this
	 */
	public function setCircleId($circleId) {
		$this->circleId = $circleId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCircleId() {
		return $this->circleId;
	}


	/**
	 * @param string $circleId
	 *
	 * @return $this
	 */
	public function setRemoteCircleId($circleId) {
		$this->remoteCircleId = $circleId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getRemoteCircleId() {
		return $this->remoteCircleId;
	}


	/**
	 * @return Cloud
	 */
	public function getRemoteCloud() {
		return $this->remoteCloud;
	}

	/**
	 * @param Cloud $cloud
	 *
	 * @return $this
	 */
	public function setRemoteCloud($cloud) {
		$this->remoteCloud = $cloud;

		return $this;
	}


	/**
	 * @param string $token
	 *
	 * @return $this
	 */
	public function setToken($token) {
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
	 * @param string $password
	 *
	 * @return $this
	 */
	public function setPassword($password) {
		$this->password = $password;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPassword() {
		return $this->password;
	}


	/**
	 * @param string $filename
	 *
	 * @return $this
	 */
	public function setRemoteFileId($filename) {
		$this->remoteFilename = $filename;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getRemoteFileId() {
		return $this->remoteFilename;
	}


	/**
	 * @param string $filename
	 *
	 * @return $this
	 */
	public function setRemoteFileName($filename) {
		$this->remoteFilename = $filename;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getRemoteFileName() {
		return $this->remoteFilename;
	}


	/**
	 * @param string $author
	 *
	 * @return $this
	 */
	public function setAuthor($author) {
		$this->author = $author;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAuthor() {
		return $this->author;
	}


	/**
	 * @param string $mountPoint
	 *
	 * @return $this
	 */
	public function setMountPoint($mountPoint) {
		$this->mountPoint = $mountPoint;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getMountPoint() {
		return $this->mountPoint;
	}


	/**
	 * @param string $mountPointHash
	 *
	 * @return $this
	 */
	public function setMountPointHash($mountPointHash) {
		$this->mountPointHash = $mountPointHash;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getMountPointHash() {
		return $this->mountPointHash;
	}


	/**
	 * @param string $created
	 *
	 * @return $this
	 */
	public function setCreated($created) {
		$this->created = $created;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCreated() {
		return $this->created;
	}


}