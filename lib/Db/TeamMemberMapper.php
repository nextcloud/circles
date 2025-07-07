<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Db;

use OCA\Circles\Model\TeamEntity;
use OCA\Circles\Model\TeamMember;
use OCP\AppFramework\Db\QBMapper;
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
		private readonly TeamMapper $teamMapper,
		private readonly TeamEntityMapper $teamEntityMapper,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($db, self::TABLE, TeamMember::class);
	}

	/**
	 * @return TeamMember[]
	 */
	public function getTeamMembers(?TeamEntity $initiator, string $teamSingleId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('tm.*')
		   ->from($this->getTableName(), 'tm')
		   ->where($qb->expr()->eq('team_single_id', $qb->createNamedParameter($teamSingleId)));

		$this->limitToInitiator($initiator, $qb, false);
		$this->teamEntityMapper->joinTeamEntity($qb, 'tm', 'member_single_id', 'te');

		$result = $qb->executeQuery();
		$teamMembers = [];
		while ($row = $result->fetch()) {
			$teamMember = $this->createTeamMemberFromRow($row);
			$teamMember->setEntity($this->teamEntityMapper->createTeamEntityFromRow($row, 'te_'));
			$teamMembers[] = $teamMember;
		}

		return $teamMembers;
	}


	public function getEntityTeams(?TeamEntity $initiator, string $singleId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('tm.*')
		   ->from($this->getTableName(), 'tm')
		   ->where($qb->expr()->eq('member_single_id', $qb->createNamedParameter($singleId)));

		$this->limitToInitiator($initiator, $qb, false);
		$this->teamMapper->joinTeam($qb, 'tm', 'team_single_id', 't');

		$result = $qb->executeQuery();
		$teamMembers = [];
		while ($row = $result->fetch()) {
			$teamMember = $this->createTeamMemberFromRow($row);
			$teamMember->setTeam($this->teamMapper->createTeamFromRow($row, 't_'));
			$teamMembers[] = $teamMember;
		}

		return $teamMembers;
	}

	public function createTeamMemberFromRow(array $row, string $alias = ''): TeamMember {
		$new = [];
		foreach($this->getFields(TeamMember::class) as $field) {
			$new[$field] = $row[$alias . $field];
		}

		return $this->mapRowToEntity($new);
	}

	public function emptyTable() {
		$qb = $this->db->getQueryBuilder();
		$qb->delete(self::TABLE);
		$qb->executeStatement();
	}
}
