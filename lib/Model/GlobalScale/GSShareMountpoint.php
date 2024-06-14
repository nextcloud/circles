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
class GSShareMountpoint implements JsonSerializable {
	use TArrayTools;


	/** @var int */
	private $shareId = 0;

	/** @var string */
	private $userId = '';

	/** @var string */
	private $mountPoint = '';


	/**
	 * GSShareMountpoint constructor.
	 *
	 * @param int $shareId
	 * @param string $userId
	 * @param string $mountPoint
	 */
	public function __construct(int $shareId = 0, string $userId = '', string $mountPoint = '') {
		$this->shareId = $shareId;
		$this->userId = $userId;
		$this->mountPoint = $mountPoint;
	}


	/**
	 * @return string
	 */
	public function getUserId(): string {
		return $this->userId;
	}

	/**
	 * @param string $userId
	 *
	 * @return GSShareMountpoint
	 */
	public function setUserId(string $userId): self {
		$this->userId = $userId;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getShareId(): int {
		return $this->shareId;
	}

	/**
	 * @param int $shareId
	 *
	 * @return $this
	 */
	public function setShareId(int $shareId): self {
		$this->shareId = $shareId;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getMountPoint(): string {
		return $this->mountPoint;
	}

	/**
	 * @param string $mountPoint
	 *
	 * @return GSShareMountpoint
	 */
	public function setMountPoint(string $mountPoint): self {
		$this->mountPoint = $mountPoint;

		return $this;
	}


	/**
	 * @param array $data
	 *
	 * @return GSShareMountpoint
	 */
	public function importFromDatabase(array $data): self {
		$this->setShareId($this->getInt('share_id', $data));
		$this->setUserId($this->get('user_id', $data));
		$this->setMountPoint($this->get('mountpoint', $data));

		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'userId' => $this->getUserId(),
			'shareId' => $this->getShareId(),
			'mountPoint' => $this->getMountPoint(),
		];
	}
}
