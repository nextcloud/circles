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


namespace OCA\Circles\Db;

use OCA\Circles\Tools\Exceptions\RowNotFoundException;
use OCA\Circles\Exceptions\EventWrapperNotFoundException;
use OCA\Circles\Model\Federated\EventWrapper;

/**
 * Class GSEventsRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class EventWrapperRequestBuilder extends CoreRequestBuilder {
	/**
	 * @return CoreQueryBuilder
	 */
	protected function getEventWrapperInsertSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_EVENT);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getEventWrapperUpdateSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_EVENT);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
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
	 * @return CoreQueryBuilder
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
