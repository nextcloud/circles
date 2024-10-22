<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
