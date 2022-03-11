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
