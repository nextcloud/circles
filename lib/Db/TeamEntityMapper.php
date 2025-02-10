<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Db;

use Exception;
use OCA\Circles\Enum\TeamApi;
use OCA\Circles\Enum\TeamEntityType;
use OCA\Circles\Exceptions\TeamEntityNotFoundException;
use OCA\Circles\Model\TeamEntity;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * @template-extends QBMapper<TeamEntity>
 */
class TeamEntityMapper extends QBMapper {
	public const TABLE = 'teams_entities';

	private array $fields = [];

	public function __construct(
		IDBConnection $db,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($db, self::TABLE, TeamEntity::class);

//		// TODO: This code should not be needed but some endpoint/api call might not use ITeamSession
//		if (!defined('USING_TEAMS_API')) {
//			$this->logger->notice('This endpoint/API must be using ITeamSession', ['exception' => new Exception()]);
//			define('USING_TEAMS_API', TeamApi::V1);
//		}
	}

	public function getBySingleId(string $singleId) {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
		   ->from($this->getTableName())
		   ->where($qb->expr()->eq('single_id', $qb->createNamedParameter($singleId)));

		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException) {
			throw new TeamEntityNotFoundException('no team entity found');
		}
	}

	public function getByOrigId(TeamEntityType $type, string $origId) {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
		   ->from($this->getTableName())
		   ->where($qb->expr()->eq('single_id', $qb->createNamedParameter($type->value)))
		   ->where($qb->expr()->eq('orig_id', $qb->createNamedParameter($origId)));

		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException) {
			throw new TeamEntityNotFoundException('no team entity found');
		}
	}

	public function leftJoinTeamEntity(IQueryBuilder $qb, string $aliasSingleId, string $fieldSingleId, string $aliasDetails = 'te'): void {
		foreach ($this->getTeamEntityFields() as $field) {
			$qb->selectAlias($aliasDetails . '.' . $field, $aliasDetails . '_' . $field);
		}
		$qb->leftJoin(
			   $aliasSingleId,
			   self::TABLE,
			   $aliasDetails,
			   $qb->expr()->eq($aliasSingleId . '.' . $fieldSingleId, $aliasDetails . '.single_id')
		   );
	}

	public function createTeamEntityFromRow(array $row, string $alias = ''): TeamEntity {
		$new = [];
		foreach($this->getTeamEntityFields() as $field) {
			$new[$field] = $row[$alias . $field];
		}

		return $this->mapRowToEntity($new);
	}

	private function getTeamEntityFields(): array {
		if (empty($this->fields)) {
			$entity = new TeamEntity();
			$this->fields = array_keys($entity->getFieldTypes());
		}

		return $this->fields;
	}
}
