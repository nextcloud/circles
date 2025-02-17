<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Db;

use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Tools\Exceptions\RowNotFoundException;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class CircleRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class CircleRequestBuilder extends CoreRequestBuilder {
	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getCircleInsertSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_CIRCLE)
			->setValue('creation', $qb->createNamedParameter($this->timezoneService->getUTCDate()));

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getCircleUpdateSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_CIRCLE);

		return $qb;
	}


	/**
	 * @param string $alias
	 * @param bool $single
	 *
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getCircleSelectSql(
		string $alias = CoreQueryBuilder::CIRCLE,
		bool $single = false,
	): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(self::TABLE_CIRCLE, self::$tables[self::TABLE_CIRCLE], $alias);

		if (!$single) {
			$qb->orderBy($alias . '.creation', 'asc');
		}

		return $qb;
	}


	/**
	 * Base of the Sql Delete request
	 *
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getCircleDeleteSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_CIRCLE);

		return $qb;
	}


	/**
	 * @param CoreQueryBuilder&IQueryBuilder $qb
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 */
	public function getItemFromRequest(CoreQueryBuilder $qb): Circle {
		/** @var Circle $circle */
		try {
			$circle = $qb->asItem(Circle::class);
		} catch (RowNotFoundException $e) {
			throw new CircleNotFoundException('Circle not found');
		} catch (\Exception $e) {
			throw new \Exception($qb->getSQL());
		}

		return $circle;
	}

	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return Circle[]
	 */
	public function getItemsFromRequest(CoreQueryBuilder $qb): array {
		/** @var Circle[] $result */
		return $qb->asItems(Circle::class);
	}
}
