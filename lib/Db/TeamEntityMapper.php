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
class TeamEntityMapper extends CoreMapper {
	public const TABLE = 'teams_entities';
	private array $fields = [];

	public function __construct(
		IDBConnection $db,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($db, self::TABLE, TeamEntity::class);
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

	public function joinTeamEntity(
		IQueryBuilder $qb,
		string $aliasSingleId,
		string $fieldSingleId,
		string $aliasDetails = 'te',
		bool $leftJoin = false,
	): void {
		foreach ($this->getFields(TeamEntity::class) as $field) {
			$qb->selectAlias($aliasDetails . '.' . $field, $aliasDetails . '_' . $field);
		}

		$join = ($leftJoin) ? 'leftJoin' : 'innerJoin';
		$qb->$join(
			   $aliasSingleId,
			   self::TABLE,
			   $aliasDetails,
			   $qb->expr()->eq($aliasSingleId . '.' . $fieldSingleId, $aliasDetails . '.single_id')
		   );
	}

	/**
	 * @throws TeamEntityNotFoundException
	 */
	public function createTeamEntityFromRow(array $row, string $alias = ''): TeamEntity {
		$new = [];
		foreach($this->getFields(TeamEntity::class) as $field) {
			$new[$field] = $row[$alias . $field];
		}

		if ($new['id'] === null) {
			throw new TeamEntityNotFoundException();
		}

		return $this->mapRowToEntity($new);
	}

	public function emptyTable() {
		$qb = $this->db->getQueryBuilder();
		$qb->delete(self::TABLE);
		$qb->executeStatement();
	}
}
