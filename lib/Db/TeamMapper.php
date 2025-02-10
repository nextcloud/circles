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
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * @template-extends QBMapper<Team>
 */
class TeamMapper extends QBMapper {
	public const TABLE = 'teams';

	public function __construct(
		IDBConnection $db,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($db, self::TABLE, Team::class);

//		// TODO: This code should not be needed but some endpoint/api call might not use ITeamSession
//		if (!defined('USING_TEAMS_API')) {
//			$this->logger->notice('This endpoint/API must be using ITeamSession');
//			define('USING_TEAMS_API', TeamApi::V1);
//		}
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

}
