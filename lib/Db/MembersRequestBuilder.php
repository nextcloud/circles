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


use OCA\Circles\Model\Member;
use OCP\DB\QueryBuilder\IQueryBuilder;

class MembersRequestBuilder extends CoreRequestBuilder {


	/**
	 * Base of the Sql Insert request for Shares
	 *
	 * @return IQueryBuilder
	 */
	protected function getMembersInsertSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert(self::TABLE_MEMBERS)
		   ->setValue('joined', $qb->createFunction('NOW()'));

		return $qb;
	}


	/**
	 * @return IQueryBuilder
	 */
	protected function getMembersSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select('m.user_id', 'm.circle_id', 'm.level', 'm.status', 'm.note', 'm.joined')
		   ->from(self::TABLE_MEMBERS, 'm');

		$this->default_select_alias = 'm';

		return $qb;
	}


	/**
	 * @return IQueryBuilder
	 */
	protected function getGroupsSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select('g.circle_id', 'g.group_id', 'g.level', 'g.note', 'g.joined')
		   ->from(self::TABLE_GROUPS, 'g');
		$this->default_select_alias = 'g';

		return $qb;
	}


	/**
	 * Base of the Sql Insert request for Shares
	 *
	 * @return IQueryBuilder
	 */
	protected function getGroupsInsertSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert(self::TABLE_GROUPS)
		   ->setValue('joined', $qb->createFunction('NOW()'));

		return $qb;
	}


	/**
	 * Base of the Sql Update request for Groups
	 *
	 * @param int $circleId
	 * @param string $groupId
	 *
	 * @return IQueryBuilder
	 */
	protected function getGroupsUpdateSql($circleId, $groupId) {
		$qb = $this->dbConnection->getQueryBuilder();
		$expr = $qb->expr();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->update(self::TABLE_GROUPS)
		   ->where(
			   $expr->andX(
				   $expr->eq('circle_id', $qb->createNamedParameter($circleId)),
				   $expr->eq('group_id', $qb->createNamedParameter($groupId))
			   )
		   );

		return $qb;
	}

	/**
	 * Base of the Sql Updte request for Members
	 *
	 * @param int $circleId
	 * @param string $userId
	 *
	 * @return IQueryBuilder
	 */
	protected function getMembersUpdateSql($circleId, $userId) {
		$qb = $this->dbConnection->getQueryBuilder();
		$expr = $qb->expr();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->update(self::TABLE_MEMBERS)
		   ->where(
			   $expr->andX(
				   $expr->eq('circle_id', $qb->createNamedParameter($circleId)),
				   $expr->eq('user_id', $qb->createNamedParameter($userId))
			   )
		   );

		return $qb;
	}


	/**
	 * Base of the Sql Delete request for Groups
	 *
	 * @param string $groupId
	 *
	 * @return IQueryBuilder
	 */
	protected function getGroupsDeleteSql($groupId) {
		$qb = $this->dbConnection->getQueryBuilder();
		$expr = $qb->expr();

		$qb->delete(CoreRequestBuilder::TABLE_GROUPS)
		   ->where($expr->eq('group_id', $qb->createNamedParameter($groupId)));

		return $qb;
	}

	/**
	 * Base of the Sql Delete request for Members
	 *
	 * @param string $uniqueCircleId
	 * @param string $userId
	 *
	 * @return IQueryBuilder
	 */
	protected function getMembersDeleteSql($uniqueCircleId, $userId) {
		$qb = $this->dbConnection->getQueryBuilder();
		$expr = $qb->expr();

		$and = $expr->andX();
		if ($uniqueCircleId > 0) {
			$and->add($expr->eq('circle_id', $qb->createNamedParameter($uniqueCircleId)));
		}
		if ($userId !== '') {
			$and->add($expr->eq('user_id', $qb->createNamedParameter($userId)));
		}

		$qb->delete(CoreRequestBuilder::TABLE_MEMBERS)
		   ->where($and);

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
		$member->setNote($data['note']);
		$member->setLevel($data['level']);
		$member->setStatus($data['status']);
		$member->setJoined($data['joined']);

		return $member;
	}

	/**
	 * @param array $data
	 *
	 * @return Member
	 */
	protected function parseGroupsSelectSql(array $data) {
		$member = new Member($this->l10n);
		$member->setCircleId($data['circle_id']);
		$member->setNote($data['note']);
		$member->setLevel($data['level']);
		$member->setGroupId($data['group_id']);

		if (key_exists('user_id', $data)) {
			$member->setUserId($data['user_id']);
		}

		$member->setJoined($data['joined']);

		return $member;
	}

}