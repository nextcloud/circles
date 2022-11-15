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
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Model\Federated\RemoteInstance;

/**
 * Class RemoteRequestBuilder
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
	 * @param string $alias
	 *
	 * @return CoreQueryBuilder
	 */
	protected function getRemoteSelectSql(string $alias = CoreQueryBuilder::REMOTE): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->generateSelect(self::TABLE_REMOTE, self::$tables[self::TABLE_REMOTE], $alias);

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
	 * @return RemoteInstance
	 * @throws RemoteNotFoundException
	 */
	public function getItemFromRequest(CoreQueryBuilder $qb): RemoteInstance {
		/** @var RemoteInstance $appService */
		try {
			$appService = $qb->asItem(RemoteInstance::class);
		} catch (RowNotFoundException $e) {
			throw new RemoteNotFoundException('Unknown remote instance');
		}

		return $appService;
	}

	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return RemoteInstance[]
	 */
	public function getItemsFromRequest(CoreQueryBuilder $qb): array {
		/** @var RemoteInstance[] $result */
		return $qb->asItems(RemoteInstance::class);
	}
}
