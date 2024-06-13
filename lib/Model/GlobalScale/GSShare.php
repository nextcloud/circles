<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Model\GlobalScale;

use JsonSerializable;
use OCA\Circles\Tools\Traits\TArrayTools;

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
