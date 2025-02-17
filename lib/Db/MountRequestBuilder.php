<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Db;

use OCA\Circles\Exceptions\MountNotFoundException;
use OCA\Circles\Model\Mount;
use OCA\Circles\Tools\Exceptions\RowNotFoundException;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class MountRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class MountRequestBuilder extends CoreRequestBuilder {
	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getMountInsertSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_MOUNT);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getMountUpdateSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_MOUNT);

		return $qb;
	}


	/**
	 * @param string $alias
	 *
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getMountSelectSql(string $alias = CoreQueryBuilder::MOUNT): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(self::TABLE_MOUNT, self::$tables[self::TABLE_MOUNT], $alias);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getMountDeleteSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_MOUNT);

		return $qb;
	}


	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return Mount
	 * @throws MountNotFoundException
	 */
	public function getItemFromRequest(CoreQueryBuilder $qb): Mount {
		/** @var Mount $circle */
		try {
			$circle = $qb->asItem(Mount::class);
		} catch (RowNotFoundException $e) {
			throw new MountNotFoundException('Mount not found');
		}

		return $circle;
	}

	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return Mount[]
	 */
	public function getItemsFromRequest(CoreQueryBuilder $qb): array {
		/** @var Mount[] $result */
		return $qb->asItems(Mount::class);
	}
}
