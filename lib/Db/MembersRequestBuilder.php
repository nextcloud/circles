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
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MiscService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IL10N;
use OCA\Circles\AppInfo\Application;

class MembersRequestBuilder extends CoreRequestBuilder {


	/** @var IGroupManager */
	protected $groupManager;

	/**
	 * CirclesRequestBuilder constructor.
	 *
	 * {@inheritdoc}
	 * @param IGroupManager $groupManager
	 */
	public function __construct(
		IL10N $l10n, IDBConnection $connection, IGroupManager $groupManager,
		ConfigService $configService, MiscService $miscService
	) {
		parent::__construct($l10n, $connection, $configService, $miscService);
		$this->groupManager = $groupManager;
	}


	/**
	 * Base of the Sql Insert request for Shares
	 *
	 * @return IQueryBuilder
	 */
	protected function getMembersInsertSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert(self::TABLE_MEMBERS);

		return $qb;
	}


	/**
	 * @return IQueryBuilder
	 */
	protected function getMembersSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select(
			'm.user_id', 'm.user_type', 'm.circle_id', 'm.level', 'm.status', 'm.note', 'm.joined'
		)
		   ->from(self::TABLE_MEMBERS, 'm')
		   ->orderBy('m.joined');

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
		$qb->insert(self::TABLE_GROUPS);

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
	 * @param Member $member
	 *
	 * @return IQueryBuilder
	 */
	protected function getMembersUpdateSql($circleId, Member $member) {
		$qb = $this->dbConnection->getQueryBuilder();
		$expr = $qb->expr();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->update(self::TABLE_MEMBERS)
		   ->where(
			   $expr->andX(
				   $expr->eq('circle_id', $qb->createNamedParameter($circleId)),
				   $expr->eq('user_id', $qb->createNamedParameter($member->getUserId())),
				   $expr->eq('user_type', $qb->createNamedParameter($member->getType()))
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
	 * @return IQueryBuilder
	 */
	protected function getMembersDeleteSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete(CoreRequestBuilder::TABLE_MEMBERS);

		return $qb;
	}


	/**
	 * @param array $data
	 *
	 * @return Member
	 */
	protected function parseMembersSelectSql(array $data) {
		$member = new Member($data['user_id'], $data['user_type'], $data['circle_id']);
		$member->setNote($data['note']);
		$member->setLevel($data['level']);
		$member->setStatus($data['status']);
		$app = new Application();
		$user = $app->getContainer()->query('UserSession')->getUser();
		$config = $app->getContainer()->query(ConfigService::class);
		$timezone = \OC::$server->getConfig()->getUserValue($user->getUID(), 'core', 'timezone', 'UTC');
		$date = \DateTime::createFromFormat('Y-m-d H:i:s', $data['joined']);
		$date->setTimezone(new \DateTimeZone($timezone));
		$member->setJoined($date->format('Y-m-d H:i:s'));
		return $member;
	}

	/**
	 * @param array $data
	 *
	 * @return Member
	 */
	protected function parseGroupsSelectSql(array $data) {
		$member = new Member();
		$member->setCircleId($data['circle_id']);
		$member->setNote($data['note']);
		$member->setLevel($data['level']);

		if (key_exists('user_id', $data)) {
			$member->setType(Member::TYPE_USER);
			$member->setUserId($data['user_id']);
		} else {
			$member->setType(Member::TYPE_GROUP);
			$member->setUserId($data['group_id']);
		}

		$member->setJoined($data['joined']);

		return $member;
	}

}