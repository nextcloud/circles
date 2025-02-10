<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Db;

use OCA\Circles\Enum\TeamApi;
use OCA\Circles\Model\TeamEntity;
use OCA\Circles\Model\TeamMember;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * @template-extends QBMapper<TeamMember>
 */
class TeamMemberMapper extends CoreMapper {
	public const TABLE = 'teams_members';

	private array $fields = [];

	public function __construct(
		IDBConnection $db,
		private readonly TeamEntityMapper $teamEntityMapper,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($db, self::TABLE, TeamMember::class);

//		// TODO: This code should not be needed but some endpoint/api call might not use ITeamSession
//		if (!defined('USING_TEAMS_API')) {
//			$this->logger->notice('This endpoint/API must be using ITeamSession');
//			define('USING_TEAMS_API', TeamApi::V1);
//		}
	}

	/**
	 * @return TeamMember[]
	 */
	public function getTeamMembers(?TeamEntity $initiator, string $teamSingleId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
		   ->from($this->getTableName(), 'tm')
		   ->where($qb->expr()->eq('team_single_id', $qb->createNamedParameter($teamSingleId)));

		$this->limitToInitiator($initiator, $qb, false);
		$this->teamEntityMapper->leftJoinTeamEntity($qb, 'tm', 'member_single_id');

		$result = $qb->executeQuery();
		$teamMembers = [];
		while ($row = $result->fetch()) {
			$teamMember = $this->createTeamEntityFromRow($row);
			$teamMember->setEntity($this->teamEntityMapper->createTeamEntityFromRow($row, 'te_'));
			$teamMembers[] = $teamMember;
		}

		return $teamMembers;
	}

	public function createTeamEntityFromRow(array $row, string $alias = ''): TeamMember {
		$new = [];
		foreach($this->getTeamMemberFields() as $field) {
			$new[$field] = $row[$alias . $field];
		}

		return $this->mapRowToEntity($new);
	}

	private function getTeamMemberFields(): array {
		if (empty($this->fields)) {
			$entity = new TeamMember();
			$this->fields = array_keys($entity->getFieldTypes());
		}

		return $this->fields;
	}
}
