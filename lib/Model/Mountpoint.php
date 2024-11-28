<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
