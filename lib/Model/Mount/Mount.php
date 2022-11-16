<?php

declare(strict_types=1);

/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2022
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

namespace OCA\Circles\Model\Mount;

use JsonSerializable;
use OCA\Circles\Model\ManagedModel;
use OCA\Circles\Tools\Traits\TArrayTools;

/**
 * Class Mount
 *
 * @package OCA\Circles\Model
 */
class Mount extends ManagedModel implements JsonSerializable {
	use TArrayTools;

	private string $circleId;
	private string $mountPoint2 = '';
	private string $absoluteMountPoint = '';

	public function __construct(string $circleId = '') {
		$this->circleId = $circleId;
	}

	public function setCircleId(string $circleId): self {
		$this->circleId = $circleId;

		return $this;
	}

	public function getCircleId(): string {
		return $this->circleId;
	}

	public function setMountPoint2(string $mountPoint): self {
		$this->mountPoint2 = $mountPoint;

		return $this;
	}

	public function getMountPoint2(): string {
		return $this->mountPoint2;
	}

	public function setAbsoluteMountPoint(string $absoluteMountPoint): self {
		$this->absoluteMountPoint = $absoluteMountPoint;

		return $this;
	}

	public function getAbsoluteMountPoint(): string {
		return $this->absoluteMountPoint;
	}



//	/**
//	 * @return string
//	 */
//	public function getMountPointHash(): string {
//		return $this->mountPointHash;
//	}
//
//	/**
//	 * @param string $mountPointHash
//	 *
//	 * @return Mount
//	 */
//	public function setMountPointHash(string $mountPointHash): self {
//		$this->mountPointHash = $mountPointHash;
//
//		return $this;
//	}


	/**
	 * @return array
	 */
	public function toMount(): array {
		return [
		];
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'circleId' => $this->getCircleId(),
			'mountPoint' => $this->getMountPoint2(),
			'absoluteMountPoint' => $this->getAbsoluteMountPoint()
		];
	}
}
