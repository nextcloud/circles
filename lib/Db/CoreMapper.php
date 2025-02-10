<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Db;

use OCA\Circles\Model\TeamEntity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;

class CoreMapper extends QBMapper {
	/**
	 * Setting $useMemberships to FALSE will make your request heavier !
	 *
	 * @param TeamEntity|null $initiator
	 * @param IQueryBuilder $qb
	 * @param bool $useMemberships set to FALSE to bypass memberships structured caching, meaning making heavier request
	 */
	protected function limitToInitiator(?TeamEntity $initiator, IQueryBuilder $qb, bool $useMemberships = true): void {
		if ($initiator === null) {
			return;
		}
	}
}
