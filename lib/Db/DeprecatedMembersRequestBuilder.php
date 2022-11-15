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

use OCA\Circles\Model\DeprecatedMember;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MiscService;
use OCA\Circles\Service\TimezoneService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IL10N;

class DeprecatedMembersRequestBuilder extends DeprecatedRequestBuilder {
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
		ConfigService $configService, TimezoneService $timezoneService, MiscService $miscService
	) {
		parent::__construct($l10n, $connection, $configService, $timezoneService, $miscService);
		$this->groupManager = $groupManager;
	}


	/**
	 * Base of the Sql Insert request for Shares
	 *
	 * @return IQueryBuilder
	 */
	protected function getMembersInsertSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert(self::TABLE_MEMBERS)
		   ->setValue('joined', $qb->createNamedParameter($this->timezoneService->getUTCDate()));

		return $qb;
	}


	/**
	 * @return IQueryBuilder
	 */
	protected function getMembersSelectSql() {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select(
			'm.user_id', 'm.instance', 'm.user_type', 'm.circle_id', 'm.level', 'm.status', 'm.note',
			'm.contact_id', 'm.member_id', 'm.cached_name', 'm.cached_update', 'm.contact_meta', 'm.joined'
		)
		   ->from(self::TABLE_MEMBERS, 'm')
		   ->orderBy('m.joined');

		$this->default_select_alias = 'm';

		return $qb;
	}


	/**
	 * Base of the Sql Updte request for Members
	 *
	 * @param string /$circleId
	 * @param string $userId
	 * @param string $instance
	 * @param int $type
	 *
	 * @return IQueryBuilder
	 */
	protected function getMembersUpdateSql(string $circleId, string $userId, string $instance, int $type) {
		$qb = $this->dbConnection->getQueryBuilder();
		$expr = $qb->expr();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->update(self::TABLE_MEMBERS)
		   ->where(
		   	$expr->andX(
		   		$expr->eq('circle_id', $qb->createNamedParameter($circleId)),
		   		$expr->eq('user_id', $qb->createNamedParameter($userId)),
		   		$expr->eq('instance', $qb->createNamedParameter($instance)),
		   		$expr->eq('user_type', $qb->createNamedParameter($type))
		   	)
		   );

		return $qb;
	}


	/**
	 * Base of the Sql Delete request for Members
	 *
	 * @return IQueryBuilder
	 */
	protected function getMembersDeleteSql() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete(DeprecatedRequestBuilder::TABLE_MEMBERS);

		return $qb;
	}


	/**
	 * @param array $data
	 *
	 * @return DeprecatedMember
	 */
	protected function parseMembersSelectSql(array $data) {
		$member = new DeprecatedMember($data['user_id'], $data['user_type'], $data['circle_id']);
		$member->setNote($data['note']);
		$member->setContactId($data['contact_id']);
		$member->setMemberId($data['member_id']);
		$member->setCachedName($data['cached_name']);
		$member->setCachedUpdate($this->timezoneService->convertToTimestamp($data['cached_update']));

		$contactMeta = json_decode($data['contact_meta'], true);
		if (is_array($contactMeta)) {
			$member->setContactMeta($contactMeta);
		}

		$member->setLevel($data['level']);
		$member->setInstance($data['instance']);
		$member->setStatus($data['status']);
		$member->setJoined($this->timezoneService->convertTimeForCurrentUser($data['joined']));

		$joined = $this->timezoneService->convertToTimestamp($data['joined']);
		$member->setJoinedSince(time() - $joined);

		return $member;
	}


	/**
	 * @param array $data
	 *
	 * @return DeprecatedMember
	 */
	protected function parseGroupsSelectSql(array $data) {
		$member = new DeprecatedMember();
		$member->setCircleId($data['circle_id']);
		$member->setNote($data['note']);
		$member->setLevel($data['level']);

		if (key_exists('user_id', $data)) {
			$member->setType(DeprecatedMember::TYPE_USER);
			$member->setUserId($data['user_id']);
		} else {
			$member->setType(DeprecatedMember::TYPE_GROUP);
			$member->setUserId($data['group_id']);
		}

		$member->setJoined($this->timezoneService->convertTimeForCurrentUser($data['joined']));

		return $member;
	}
}
