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
use OC\L10N\L10N;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class CirclesRequestBuilder {

	const TABLE_CIRCLES = 'circles_circles';
	const TABLE_MEMBERS = 'circles_members';

	/** @var IDBConnection */
	protected $dbConnection;

	/** @var L10N */
	protected $l10n;

	private $default_select_alias;


	/**
	 * Join the Circles table
	 *
	 * @param IQueryBuilder $qb
	 */
	protected function joinCircles(& $qb, $field) {
		$expr = $qb->expr();

		$qb->from(self::TABLE_CIRCLES, 'c')
		   ->andWhere($expr->eq('c.id', $field));
	}


	/**
	 * Limit the request to the Share by its Id.
	 *
	 * @param IQueryBuilder $qb
	 * @param int $circleId
	 */
	protected function limitToCircle(IQueryBuilder &$qb, $circleId) {
		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? $this->default_select_alias . '.' : '';

		$qb->andWhere($expr->eq($pf . 'circle_id', $qb->createNamedParameter($circleId)));
	}



	/**
	 * Limit the request to the Share by its Id.
	 *
	 * @param IQueryBuilder $qb
	 * @param int $id
	 */
	protected function limitToId(IQueryBuilder &$qb, $id) {
		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? $this->default_select_alias . '.' : '';

		$qb->andWhere($expr->eq($pf . 'id', $qb->createNamedParameter($id)));
	}


	/**
	 * Limit the request to the Share by its Id.
	 *
	 * @param IQueryBuilder $qb
	 * @param string $userId
	 */
	protected function limitToUserId(IQueryBuilder &$qb, $userId) {
//		$expr = $qb->expr();
//		$pf = ($qb->getType() === QueryBuilder::SELECT) ? $this->default_select_alias . '.' : '';
//
//		$qb->andWhere($expr->eq($pf . 'userid', $qb->createNamedParameter($userId)));
	}





	protected function limitToMemberLevel(IQueryBuilder &$qb, $level) {
		$qb->where(
			$qb->expr()
			   ->gte('m.level', $qb->createNamedParameter($level))
		);
	}


	/**
	 * @param IQueryBuilder $qb
	 *
	 * @deprecated
	 * never used in fact.
	 */
	protected function leftJoinOwner(IQueryBuilder &$qb) {

		if ($qb->getType() !== QueryBuilder::SELECT) {
			return;
		}

		$expr = $qb->expr();
		$pf = $this->default_select_alias . '.';

		$qb->leftJoin(
			$this->default_select_alias, MembersMapper::TABLENAME, 'o',
			$expr->andX(
				$expr->eq($pf . 'id', 'o.circle_id'),
				$expr->eq('o.level', $qb->createNamedParameter(Member::LEVEL_OWNER))
			)
		);
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
	 * @return IQueryBuilder
	 */
	protected function getMembersSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		$qb->select('user_id', 'circle_id', 'level', 'status', 'joined')
		   ->from('circles_members', 'm');

		$this->default_select_alias = 'm';

		return $qb;
	}


	/**
	 * @return IQueryBuilder
	 */
	protected function getCirclesSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		$qb->select('c.id', 'c.unique_id', 'c.name', 'c.description', 'c.type', 'c.creation')
		   ->from('circles_circles', 'c');
		$this->default_select_alias = 'c';

		return $qb;
	}

	/**
	 * @param array $data
	 *
	 * @return Member
	 */
	protected function parseMembersSelectSql(array $data) {
		$member = new Member($this->l10n);
		$member->setUserId($data['user_id']);
		$member->setCircleId($data['circle_id']);
		$member->setLevel($data['level']);
		$member->setStatus($data['status']);
		$member->setJoined($data['joined']);

		return $member;
	}


	/**
	 * @param array $data
	 *
	 * @return Circle
	 */
	protected function parseCirclesSelectSql(array $data) {
		if ($data === null) {
			return null;
		}

		$circle = new Circle($this->l10n);
		$circle->setId($data['id']);
		$circle->setUniqueId($data['unique_id']);
		$circle->setName($data['name']);
		$circle->setDescription($data['description']);
		$circle->setType($data['type']);
		$circle->setCreation($data['creation']);

		return $circle;
	}
}