<?php

declare(strict_types=1);


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


namespace OCA\Circles\Model\GlobalScale;

use OCA\Circles\Tools\Traits\TArrayTools;
use JsonSerializable;

/**
 * Class GSShare
 *
 * @package OCA\Circles\Model\GlobalScale
 */
class GSShare implements JsonSerializable {
	use TArrayTools;


	/** @var int */
	private $id = 0;

	/** @var string */
	private $circleId = '';

	/** @var string */
	private $defaultMountPoint = '';

	/** @var string */
	private $mountPoint = '';

	/** @var int */
	private $parent = -1;

	/** @var string */
	private $owner = '';

	/** @var string */
	private $instance = '';

	/** @var string */
	private $token = '';

	/** @var string */
	private $password = '';


	/**
	 * GSShare constructor.
	 *
	 * @param string $circleId
	 * @param string $token
	 */
	public function __construct(string $circleId = '', string $token = '') {
		$this->circleId = $circleId;
		$this->token = $token;
	}


	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}

	public function setId(int $id): self {
		$this->id = $id;

		return $this;
	}


	/**
	 *
	 * @return string
	 */
	public function getCircleId(): string {
		return $this->circleId;
	}

	/**
	 * @param string $circleId
	 *
	 * @return GSShare
	 */
	public function setCircleId(string $circleId): self {
		$this->circleId = $circleId;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getDefaultMountPoint(): string {
		return $this->defaultMountPoint;
	}

	/**
	 * @param string $mountPoint
	 *
	 * @return GSShare
	 */
	public function setDefaultMountPoint(string $mountPoint): self {
		$this->defaultMountPoint = $mountPoint;

		return $this;
	}


	/**
	 * @param string $userId
	 *
	 * @return string
	 */
	public function getMountPoint(string $userId = ''): string {
		$mountPoint = $this->mountPoint;

		if ($mountPoint === '') {
			$mountPoint = $this->defaultMountPoint;
		}

		if ($userId === '') {
			return $mountPoint;
		}

		return '/' . $userId . '/files/' . ltrim($mountPoint, '/');
	}

	/**
	 * @param string $mountPoint
	 *
	 * @return GSShare
	 */
	public function setMountPoint(string $mountPoint): self {
		$this->mountPoint = $mountPoint;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getParent(): int {
		return $this->parent;
	}

	/**
	 * @param int $parent
	 *
	 * @return GSShare
	 */
	public function setParent(int $parent): self {
		$this->parent = $parent;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getOwner(): string {
		return $this->owner;
	}

	/**
	 * @param string $owner
	 *
	 * @return GSShare
	 */
	public function setOwner(string $owner): self {
		$this->owner = $owner;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getInstance(): string {
		return $this->instance;
	}

	/**
	 * @param string $instance
	 *
	 * @return GSShare
	 */
	public function setInstance(string $instance): self {
		$this->instance = $instance;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getToken(): string {
		return $this->token;
	}


	/**
	 * @param string $token
	 *
	 * @return GSShare
	 */
	public function setToken(string $token): self {
		$this->token = $token;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getPassword(): string {
		return $this->password;
	}

	/**
	 * @param string $password
	 *
	 * @return GSShare
	 */
	public function setPassword(string $password): self {
		$this->password = $password;

		return $this;
	}


	/**
	 * @param array $data
	 *
	 * @return GSShare
	 */
	public function importFromDatabase(array $data): self {
		$this->setId($this->getInt('id', $data));
		$this->setCircleId($this->get('circle_id', $data));
		$this->setOwner($this->get('owner', $data));
		$this->setInstance($this->get('instance', $data));
		$this->setToken($this->get('token', $data));
		$this->setParent($this->getInt('parent', $data));
		$this->setMountPoint($this->get('gsshares_mountpoint', $data));
		$this->setDefaultMountPoint($this->get('mountpoint', $data));

		return $this;
	}


	/**
	 * @param string $userId
	 * @param string $protocol
	 *
	 * @return array
	 */
	public function toMount(string $userId, string $protocol = 'https'): array {
		return [
			'owner' => $this->getOwner(),
			'remote' => $protocol . '://' . $this->getInstance(),
			'token' => $this->getToken(),
			'share_token' => $this->getToken(),
			'password' => $this->getPassword(),
			'mountpoint' => $this->getMountPoint($userId)
		];
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'defaultMountPoint' => $this->getDefaultMountPoint(),
			'mountPoint' => $this->getMountPoint(),
			'parent' => $this->getParent(),
			'owner' => $this->getOwner(),
			'instance' => $this->getInstance(),
			'token' => $this->getToken(),
			'password' => $this->getPassword()
		];
	}
}
