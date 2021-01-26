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
use OCA\Circles\Exceptions\RemoteWrapperNotFoundException;
use OCA\Circles\Model\Remote\RemoteWrapper;


/**
 * Class GSEventsRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class RemoteWrapperRequestBuilder extends CoreRequestBuilder {


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getRemoteWrapperInsertSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_REMOTE_WRAPPER);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getRemoteWrapperUpdateSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_REMOTE_WRAPPER);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getRemoteWrapperSelectSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();

		$qb->select('gse.token', 'gse.event', 'gse.instance', 'gse.severity', 'gse.status', 'gse.creation')
		   ->from(self::TABLE_REMOTE_WRAPPER, 'gse')
		   ->setDefaultSelectAlias('gse');

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getRemoteWrapperDeleteSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_REMOTE_WRAPPER);

		return $qb;
	}


	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return RemoteWrapper
	 * @throws RemoteWrapperNotFoundException
	 */
	public function getItemFromRequest(CoreQueryBuilder $qb): RemoteWrapper {
		/** @var RemoteWrapper $wrapper */
		try {
			$wrapper = $qb->asItem(RemoteWrapper::class);
		} catch (RowNotFoundException $e) {
			throw new RemoteWrapperNotFoundException();
		}

		return $wrapper;
	}

	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return RemoteWrapper[]
	 */
	public function getItemsFromRequest(CoreQueryBuilder $qb): array {
		/** @var RemoteWrapper[] $result */
		return $qb->asItems(RemoteWrapper::class);
	}

}

