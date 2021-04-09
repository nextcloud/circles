<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
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


use daita\MySmallPhpTools\Exceptions\RowNotFoundException;
use OCA\Circles\Exceptions\EventWrapperNotFoundException;
use OCA\Circles\Model\Federated\EventWrapper;


/**
 * Class GSEventsRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class EventWrapperRequestBuilder extends CoreQueryBuilder {


	/**
	 * @return CoreRequestBuilder
	 */
	protected function getEventWrapperInsertSql(): CoreRequestBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_EVENT);

		return $qb;
	}


	/**
	 * @return CoreRequestBuilder
	 */
	protected function getEventWrapperUpdateSql(): CoreRequestBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_EVENT);

		return $qb;
	}


	/**
	 * @return CoreRequestBuilder
	 */
	protected function getEventWrapperSelectSql(): CoreRequestBuilder {
		$qb = $this->getQueryBuilder();

		$qb->select(
			'gse.token', 'gse.event', 'gse.result', 'gse.instance', 'gse.severity', 'gse.status',
			'gse.creation'
		)
		   ->from(self::TABLE_EVENT, 'gse')
		   ->setDefaultSelectAlias('gse');

		return $qb;
	}


	/**
	 * @return CoreRequestBuilder
	 */
	protected function getEventWrapperDeleteSql(): CoreRequestBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_EVENT);

		return $qb;
	}


	/**
	 * @param CoreRequestBuilder $qb
	 *
	 * @return EventWrapper
	 * @throws EventWrapperNotFoundException
	 */
	public function getItemFromRequest(CoreRequestBuilder $qb): EventWrapper {
		/** @var EventWrapper $wrapper */
		try {
			$wrapper = $qb->asItem(EventWrapper::class);
		} catch (RowNotFoundException $e) {
			throw new EventWrapperNotFoundException();
		}

		return $wrapper;
	}

	/**
	 * @param CoreRequestBuilder $qb
	 *
	 * @return EventWrapper[]
	 */
	public function getItemsFromRequest(CoreRequestBuilder $qb): array {
		/** @var EventWrapper[] $result */
		return $qb->asItems(EventWrapper::class);
	}

}

