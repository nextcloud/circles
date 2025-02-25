<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Db;

use OCA\Circles\Exceptions\TeamMembershipNotFoundException;
use OCA\Circles\Model\TeamMember;
use OCA\Circles\Model\TeamMembership;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * @template-extends QBMapper<TeamMember>
 */
class TeamMembershipMapper extends QBMapper {
	public const TABLE = 'teams_memberships';

	private array $fields = [];

	public function __construct(
		IDBConnection $db,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($db, self::TABLE, TeamMembership::class);
	}

	public function getMembershipsRelatedToEntity(string $singleId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
		   ->from($this->getTableName())
		   ->where($qb->expr()->eq('single_id', $qb->createNamedParameter($singleId)));

		return $this->findEntities($qb);
	}

	public function getByTeamAndEntity(string $teamSingleId, string $singleId): TeamMembership {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('single_id', $qb->createNamedParameter($singleId)))
			->andWhere($qb->expr()->eq('team_single_id', $qb->createNamedParameter($teamSingleId)));

		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException) {
			throw new TeamMembershipNotFoundException('no team membership found');
		}
	}

	public function removeMembership(string $teamSingleId, string $singleId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
		   ->where($qb->expr()->eq('single_id', $qb->createNamedParameter($singleId)))
		   ->andWhere($qb->expr()->eq('team_single_id', $qb->createNamedParameter($teamSingleId)));

		$qb->executeStatement();
	}

	public function emptyTable() {
		$qb = $this->db->getQueryBuilder();
		$qb->delete(self::TABLE);
		$qb->executeStatement();
	}
}
