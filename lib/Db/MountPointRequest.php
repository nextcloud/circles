<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Db;

use OCA\Circles\Exceptions\MountNotFoundException;
use OCA\Circles\Model\Mountpoint;
use OCA\Circles\Tools\Traits\TStringTools;

class MountPointRequest extends MountPointRequestBuilder {
	use TStringTools;

	/**
	 * @param Mountpoint $mountpoint
	 */
	public function insert(Mountpoint $mountpoint): void {
		$qb = $this->getMountPointInsertSql();

		$hash = ($mountpoint->getMountPoint() === '-') ? '' : md5($mountpoint->getMountPoint());
		$qb->setValue('mountpoint', $qb->createNamedParameter($mountpoint->getMountPoint()))
			->setValue('mountpoint_hash', $qb->createNamedParameter($hash))
			->setValue('mount_id', $qb->createNamedParameter($mountpoint->getMountId()))
			->setValue('single_id', $qb->createNamedParameter($mountpoint->getSingleId()));
		$qb->executeStatement();
	}

	/**
	 * @param Mountpoint $mountpoint
	 * @throws MountNotFoundException
	 */
	public function update(Mountpoint $mountpoint): void {
		$qb = $this->getMountPointUpdateSql();

		$hash = ($mountpoint->getMountPoint() === '-') ? '' : md5($mountpoint->getMountPoint());

		$qb->set('mountpoint', $qb->createNamedParameter($mountpoint->getMountPoint()))
			->set('mountpoint_hash', $qb->createNamedParameter($hash));
		$qb->limit('mount_id', $mountpoint->getMountId());
		$qb->limitToSingleId($mountpoint->getSingleId());
		$nb = $qb->executeStatement();

		if ($nb === 0) {
			throw new MountNotFoundException('Mount not found');
		}
	}
}
