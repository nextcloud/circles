<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Db;

use OCA\Circles\Enum\TeamApi;
use OCA\Circles\Model\TeamMember;
use OCA\Circles\Model\TeamMembership;
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
}
