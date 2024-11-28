<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
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

use JsonSerializable;
use OCA\Circles\Exceptions\MountPointNotFoundException;
use OCA\Circles\Tools\Db\IQueryRow;
use OCA\Circles\Tools\Traits\TArrayTools;

/**
 * Class Mountpoint
 *
 * @package OCA\Circles\Model
 */
class Mountpoint implements IQueryRow, JsonSerializable {
	use TArrayTools;

	public function __construct(
		private string $mountId = '',
		private string $singleId = '',
		private string $mountPoint = '',
	) {
	}

	public function getMountId(): string {
		return $this->mountId;
	}

	public function setMountId(string $mountId): self {
		$this->mountId = $mountId;

		return $this;
	}

	public function getSingleId(): string {
		return $this->singleId;
	}

	public function setSingleId(string $singleId): self {
		$this->singleId = $singleId;

		return $this;
	}

	public function getMountPoint(): string {
		return $this->mountPoint;
	}
	public function setMountPoint(string $mountPoint): self {
		$this->mountPoint = $mountPoint;

		return $this;
	}

	public function importFromDatabase(array $data, string $prefix = ''): IQueryRow {
		if ($this->get($prefix . 'mountpoint', $data) === '') {
			throw new MountPointNotFoundException();
		}

		$this->setMountId($this->get($prefix . 'mount_id', $data));
		$this->setSingleId($this->get($prefix . 'single_id', $data));
		$this->setMountPoint($this->get($prefix . 'mountpoint', $data));

		return $this;
	}

	public function jsonSerialize(): array {
		return [
			'mountId' => $this->getMountId(),
			'singleId' => $this->getSingleId(),
			'mountPoint' => $this->getMountPoint(),
		];
	}
}
