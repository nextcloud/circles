<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Repository;

use OCA\Circles\Entity\Mountpoint;
use OCA\Circles\Exceptions\MountNotFoundException;
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
	 * Unlike the base update(), this is not identified by the entity's own id: callers only ever
	 * have a freshly built Mountpoint (mountId + singleId + mountPoint), never one fetched back
	 * from storage, so the match has to be on that business key instead.
	 *
	 * @throws MountNotFoundException if no row matches this mountId/singleId
	 */
	public function updateMountpoint(Mountpoint $mountpoint): void {
		$mountpoint->mountpointHash = self::computeHash($mountpoint->mountPoint);

		$qb = $this->connection->getQueryBuilder();
		$qb->update($this->getTableName())
			->set('mountpoint', $qb->createNamedParameter($mountpoint->mountPoint))
			->set('mountpoint_hash', $qb->createNamedParameter($mountpoint->mountpointHash))
			->where($qb->expr()->eq('mount_id', $qb->createNamedParameter($mountpoint->mountId)))
			->andWhere($qb->expr()->eq('single_id', $qb->createNamedParameter($mountpoint->singleId)));

		if ($qb->executeStatement() === 0) {
			throw new MountNotFoundException('Mount not found');
		}
	}

	private static function computeHash(string $mountPoint): string {
		return $mountPoint === '-' ? '' : md5($mountPoint);
	}
}
