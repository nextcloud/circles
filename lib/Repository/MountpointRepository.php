<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Repository;

use OCA\Circles\Entity\Mountpoint;
use OCA\Circles\Exceptions\MountNotFoundException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\ORM\Repository;

/**
 * @extends Repository<Mountpoint>
 */
class MountpointRepository extends Repository {
	public const string entityClass = Mountpoint::class;

	/**
	 * @throws \OCP\DB\Exception if a mountpoint with the same hash already exists for this member
	 */
	public function insertMountpoint(Mountpoint $mountpoint): Mountpoint {
		$mountpoint->mountpointHash = self::computeHash($mountpoint->mountPoint);

		return $this->insert($mountpoint);
	}

	/**
	 * @throws MountNotFoundException if no row matches this mountId/singleId
	 */
	public function updateMountpoint(Mountpoint $mountpoint): void {
		$mountpoint->mountpointHash = self::computeHash($mountpoint->mountPoint);

		try {
			$this->findOneBy(['mountId' => $mountpoint->mountId, 'singleId' => $mountpoint->singleId]);
		} catch (DoesNotExistException) {
			throw new MountNotFoundException('Mount not found');
		}

		$this->update($mountpoint);
	}

	private static function computeHash(string $mountPoint): string {
		return $mountPoint === '-' ? '' : md5($mountPoint);
	}
}
