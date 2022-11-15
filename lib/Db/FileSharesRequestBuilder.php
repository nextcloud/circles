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

use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * @deprecated
 * Class FileSharesRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class FileSharesRequestBuilder extends DeprecatedRequestBuilder {
	/**
	 * Base of the Sql Delete request
	 *
	 * @return IQueryBuilder
	 */
	protected function getFileSharesDeleteSql(): IQueryBuilder {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete(self::TABLE_FILE_SHARES);
		$qb->where(
			$qb->expr()
			   ->eq('share_type', $qb->createNamedParameter(self::SHARE_TYPE))
		);

		return $qb;
	}


	/**
	 * @return IQueryBuilder
	 */
	protected function getFileSharesSelectSql(): IQueryBuilder {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select(
			's.id',
			's.share_with',
			's.file_source',
			's.uid_owner',
			's.uid_initiator',
			's.permissions',
			's.token',
			's.password',
			's.file_target'
		)
		   ->from(self::TABLE_FILE_SHARES, 's');

		$this->default_select_alias = 's';

		return $qb;
	}


	/**
	 * @return IQueryBuilder
	 */
	protected function getFileSharesUpdateSql(): IQueryBuilder {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->update(self::TABLE_FILE_SHARES);

		return $qb;
	}
}
