<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Circles\Db;

use OCA\Circles\Exceptions\MountNotFoundException;
use OCA\Circles\Model\Mount;
use OCA\Circles\Model\Mountpoint;
use OCA\Circles\Tools\Exceptions\RowNotFoundException;
use OCP\DB\QueryBuilder\IQueryBuilder;

class MountPointRequestBuilder extends CoreRequestBuilder {
	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getMountPointInsertSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_MOUNTPOINT);

		return $qb;
	}

	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getMountPointUpdateSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_MOUNTPOINT);

		return $qb;
	}

	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getMountPointSelectSql(string $alias = CoreQueryBuilder::MOUNTPOINT): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(self::TABLE_MOUNTPOINT, self::$tables[self::TABLE_MOUNTPOINT], $alias);

		return $qb;
	}

	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getMountPointDeleteSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_MOUNTPOINT);

		return $qb;
	}

	public function getItemFromRequest(CoreQueryBuilder $qb): MountPoint {
		/** @var MountPoint $mountpoint */
		try {
			$mountpoint = $qb->asItem(MountPoint::class);
		} catch (RowNotFoundException $e) {
			throw new MountNotFoundException('Mount not found');
		}

		return $mountpoint;
	}

	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return Mount[]
	 */
	public function getItemsFromRequest(CoreQueryBuilder $qb): array {
		/** @var MountPoint[] $result */
		return $qb->asItems(MountPoint::class);
	}
}
