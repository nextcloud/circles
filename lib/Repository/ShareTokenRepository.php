<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Repository;

use OCA\Circles\Entity\ShareToken;
use OCP\AppFramework\ORM\Repository;

/**
 * @extends Repository<ShareToken>
 */
class ShareTokenRepository extends Repository {
	public const string entityClass = ShareToken::class;

	public function updateSharePassword(string $circleId, string $hashedPassword): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->update($this->getTableName())
			->set('password', $qb->createNamedParameter($hashedPassword))
			->where($qb->expr()->eq('circle_id', $qb->createNamedParameter($circleId)));

		$qb->executeStatement();
	}
}
