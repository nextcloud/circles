<?php

/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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
use OCA\Circles\Model\AppService;

/**
 * Class GSEventsRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class RemoteRequestBuilder extends CoreRequestBuilder {


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getRemoteInsertSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_REMOTE)
		   ->setValue('creation', $qb->createNamedParameter($this->timezoneService->getUTCDate()));

		return $qb;
	}


	/**
	 * Base of the Sql Update request for Groups
	 *
	 * @return CoreQueryBuilder
	 */
	protected function getRemoteUpdateSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_REMOTE);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getRemoteSelectSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->select('r.id', 'r.uid', 'r.instance', 'r.href', 'r.item', 'r.creation')
		   ->from(self::TABLE_REMOTE, 'r');

		$qb->setDefaultSelectAlias('r');

		return $qb;
	}


	/**
	 * Base of the Sql Delete request
	 *
	 * @return CoreQueryBuilder
	 */
	protected function getRemoteDeleteSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_REMOTE);

		return $qb;
	}


	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return AppService
	 * @throws RowNotFoundException
	 */
	public function getItemFromRequest(CoreQueryBuilder $qb): AppService {
		/** @var AppService $appService */
		$appService = $qb->asItem(AppService::class);

		return $appService;
	}

	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return AppService[]
	 */
	public function getItemsFromRequest(CoreQueryBuilder $qb): array {
		/** @var AppService[] $result */
		return $qb->asItems(AppService::class);
	}

}
