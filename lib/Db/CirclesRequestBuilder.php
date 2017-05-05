<?php

/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
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


use Doctrine\DBAL\Query\QueryBuilder;
use OCA\Circles\Model\Member;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Share;
use OCP\Share\IShare;

class CirclesRequestBuilder {


	/** @var IDBConnection */
	protected $dbConnection;

	private $default_select_alias;

	/**
	 * Limit the request to the Share by its Id.
	 *
	 * @param IQueryBuilder $qb
	 * @param $circleId
	 */
	protected function limitToCircle(& $qb, $circleId) {
		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? $this->default_select_alias . '.' : '';

		$qb->andWhere($expr->eq($pf . 'circle_id', $qb->createNamedParameter($circleId)));
	}


	/**
	 * Base of the Sql Insert request
	 *
	 * @return IQueryBuilder
	 */
	protected function getSharesInsertSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert('circles_shares')
		   ->setValue('creation', $qb->createFunction('NOW()'));

		return $qb;
	}


	/**
	 * @param int $level
	 *
	 * @return IQueryBuilder
	 */
	protected function getMembersSelectSql(int $level = Member::LEVEL_MEMBER) {
		$qb = $this->dbConnection->getQueryBuilder();
		$expr = $qb->expr();

		$qb->select('user_id', 'circle_id', 'level', 'status', 'joined')
		   ->from('circles_members', 'm')
		   ->where($expr->gte('m.level', $qb->createNamedParameter($level)));

		$this->default_select_alias = 'm';

		return $qb;

	}

	protected function parseMembersSelectSql(array $data) {
		return [
			'uid'      => $data['user_id'],
			'circleId' => $data['circle_id'],
			'level'    => $data['level'],
			'status'   => $data['status'],
			'joined'   => $data['joined']
		];
	}

}