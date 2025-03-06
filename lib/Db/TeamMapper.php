<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Db;

use NCU\Security\Signature\Exceptions\SignatoryNotFoundException;
use NCU\Security\Signature\Model\Signatory;
use OCA\Circles\Enum\TeamApi;
use OCA\Circles\Exceptions\TeamEntityNotFoundException;
use OCA\Circles\Exceptions\TeamNotFoundException;
use OCA\Circles\Model\Team;
use OCA\Circles\Model\TeamEntity;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * @template-extends QBMapper<Team>
 */
class TeamMapper extends CoreMapper {
	public const TABLE = 'teams';
	private array $fields = [];

	public function __construct(
		IDBConnection $db,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($db, self::TABLE, Team::class);
	}

	public function getBySingleId(string $singleId): Team {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
		   ->from($this->getTableName())
		   ->where($qb->expr()->eq('single_id', $qb->createNamedParameter($singleId)));

		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException) {
			throw new TeamNotFoundException('no team found');
		}
	}

	public function joinTeam(
		IQueryBuilder $qb,
		string $aliasSingleId,
		string $fieldSingleId,
		string $aliasDetails = 't',
		bool $leftJoin = false
	): void {
		foreach ($this->getFields(Team::class) as $field) {
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
	 * @throws TeamNotFoundException
	 */
	public function createTeamFromRow(array $row, string $alias = ''): Team {
		$new = [];
		foreach($this->getFields(Team::class) as $field) {
			$new[$field] = $row[$alias . $field];
		}

		if ($new['id'] === null) {
			throw new TeamNotFoundException();
		}

		return $this->mapRowToEntity($new);
	}

	public function emptyTable() {
		$qb = $this->db->getQueryBuilder();
		$qb->delete(self::TABLE);
		$qb->executeStatement();
	}
}
