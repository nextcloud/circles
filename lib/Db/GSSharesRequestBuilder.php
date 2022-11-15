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

use OCA\Circles\Model\GlobalScale\GSShare;
use OCA\Circles\Model\GlobalScale\GSShareMountpoint;
use OCP\DB\QueryBuilder\IQueryBuilder;

/** * @deprecated
 *
 * Class GSSharesRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class GSSharesRequestBuilder extends DeprecatedRequestBuilder {
	/**
	 * Base of the Sql Insert request for Shares
	 *
	 * @return IQueryBuilder
	 */
	protected function getGSSharesInsertSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert(self::TABLE_GSSHARES);

		return $qb;
	}


	/**
	 * Base of the Sql Insert request for Shares Mountpoint
	 *
	 * @return IQueryBuilder
	 */
	protected function getGSSharesMountpointInsertSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert(self::TABLE_GSSHARES_MOUNTPOINT);

		return $qb;
	}


	/**
	 * Base of the Sql Update request
	 *
	 * @return IQueryBuilder
	 */
	protected function getGSSharesUpdateSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->update(self::TABLE_GSSHARES);

		return $qb;
	}


	/**
	 * @return IQueryBuilder
	 */
	protected function getGSSharesMountpointUpdateSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->update(self::TABLE_GSSHARES_MOUNTPOINT);

		return $qb;
	}


	/**
	 * @return IQueryBuilder
	 */
	protected function getGSSharesSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select(
			'gsh.id', 'gsh.circle_id', 'gsh.owner', 'gsh.instance', 'gsh.token', 'gsh.parent',
			'gsh.mountpoint', 'gsh.mountpoint_hash'
		)
		   ->from(self::TABLE_GSSHARES, 'gsh');

		$this->default_select_alias = 'gsh';

		return $qb;
	}


	/**
	 * @return IQueryBuilder
	 */
	protected function getGSSharesMountpointSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select(
			'gsmp.user_id', 'gsmp.share_id', 'gsmp.mountpoint', 'gsmp.mountpoint_hash'
		)
		   ->from(self::TABLE_GSSHARES_MOUNTPOINT, 'gsmp');

		$this->default_select_alias = 'gsmp';

		return $qb;
	}


	/**
	 * Base of the Sql Delete request
	 *
	 * @return IQueryBuilder
	 */
	protected function getGSSharesDeleteSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete(self::TABLE_GSSHARES);

		return $qb;
	}


	/**
	 * Base of the Sql Delete request
	 *
	 * @return IQueryBuilder
	 */
	protected function getGSSharesMountpointDeleteSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete(self::TABLE_GSSHARES_MOUNTPOINT);

		return $qb;
	}


	/**
	 * @param array $data
	 *
	 * @return GSShare
	 */
	protected function parseGSSharesSelectSql($data): GSShare {
		$share = new GSShare();
		$share->importFromDatabase($data);

		return $share;
	}


	/**
	 * @param array $data
	 *
	 * @return GSShareMountpoint
	 */
	protected function parseGSSharesMountpointSelectSql($data): GSShareMountpoint {
		$share = new GSShareMountpoint();
		$share->importFromDatabase($data);

		return $share;
	}
}
