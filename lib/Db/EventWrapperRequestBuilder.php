<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Db;

use OCA\Circles\Exceptions\EventWrapperNotFoundException;
use OCA\Circles\Model\Federated\EventWrapper;
use OCA\Circles\Tools\Exceptions\RowNotFoundException;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class GSEventsRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class EventWrapperRequestBuilder extends CoreRequestBuilder {
	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getEventWrapperInsertSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_EVENT);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getEventWrapperUpdateSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_EVENT);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getEventWrapperSelectSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();

		$qb->generateSelect(
			self::TABLE_EVENT,
			self::$tables[self::TABLE_EVENT],
			CoreQueryBuilder::FEDERATED_EVENT
		);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder&IQueryBuilder
	 */
	protected function getEventWrapperDeleteSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_EVENT);

		return $qb;
	}


	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return EventWrapper
	 * @throws EventWrapperNotFoundException
	 */
	public function getItemFromRequest(CoreQueryBuilder $qb): EventWrapper {
		/** @var EventWrapper $wrapper */
		try {
			$wrapper = $qb->asItem(EventWrapper::class);
		} catch (RowNotFoundException $e) {
			throw new EventWrapperNotFoundException();
		}

		return $wrapper;
	}

	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return EventWrapper[]
	 */
	public function getItemsFromRequest(CoreQueryBuilder $qb): array {
		/** @var EventWrapper[] $result */
		return $qb->asItems(EventWrapper::class);
	}
}
