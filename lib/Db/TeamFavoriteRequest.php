<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Db;

use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\TimezoneService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class TeamFavoriteRequest extends CoreRequestBuilder {
	public function __construct(
		private readonly IDBConnection $db,
		TimezoneService $timezoneService,
		ConfigService $configService,
	) {
		parent::__construct($timezoneService, $configService);
	}

	/**
	 * @return list<string>
	 */
	public function getCircleIds(string $userId, bool $accessibleOnly = false): array {
		$qb = $this->getQueryBuilder();
		$qb->selectDistinct('favorite.circle_id', 'favorite.position')
			->from(self::TABLE_TEAM_FAVORITES, 'favorite')
			->where($qb->expr()->eq('favorite.user_id', $qb->createNamedParameter($userId)))
			->orderBy('favorite.position', 'ASC')
			->addOrderBy('favorite.circle_id', 'ASC');

		if ($accessibleOnly) {
			$expr = $qb->expr();
			$qb->innerJoin('favorite', self::TABLE_MEMBER, 'user_member',
				$expr->eq('user_member.user_id', 'favorite.user_id'));
			$qb->leftJoin('user_member', self::TABLE_MEMBERSHIP, 'membership', $expr->andX(
				$expr->eq('membership.single_id', 'user_member.single_id'),
				$expr->eq('membership.circle_id', 'favorite.circle_id'),
			));
			$qb->leftJoin('membership', self::TABLE_MEMBER, 'inherited_member', $expr->andX(
				$expr->eq('inherited_member.single_id', 'membership.inheritance_first'),
				$expr->eq('inherited_member.circle_id', 'favorite.circle_id'),
			));
			$qb->andWhere($expr->orX(
				$expr->eq('user_member.circle_id', 'favorite.circle_id'),
				$expr->isNotNull('inherited_member.single_id'),
			));
		}

		$result = $qb->executeQuery();
		$circleIds = [];
		while ($row = $result->fetch()) {
			$circleIds[] = $row['circle_id'];
		}
		$result->closeCursor();

		return $circleIds;
	}

	public function add(string $userId, string $circleId): void {
		$max = $this->getQueryBuilder();
		$max->select($max->func()->max('position'))
			->from(self::TABLE_TEAM_FAVORITES)
			->where($max->expr()->eq('user_id', $max->createNamedParameter($userId)));
		$result = $max->executeQuery();
		$lastPosition = $result->fetchOne();
		$result->closeCursor();
		$position = $lastPosition === null || $lastPosition === false ? 0 : (int)$lastPosition + 1;

		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_TEAM_FAVORITES)
			->setValue('user_id', $qb->createNamedParameter($userId))
			->setValue('circle_id', $qb->createNamedParameter($circleId))
			->setValue('position', $qb->createNamedParameter($position, IQueryBuilder::PARAM_INT))
			->setValue('created', $qb->createNamedParameter($this->timezoneService->getUTCDate()));
		$qb->executeStatement();
	}

	public function remove(string $userId, string $circleId): void {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_TEAM_FAVORITES)
			->where($qb->expr()->andX(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($userId)),
				$qb->expr()->eq('circle_id', $qb->createNamedParameter($circleId)),
			));
		$qb->executeStatement();
	}

	/**
	 * @param list<string> $circleIds
	 */
	public function replaceOrder(string $userId, array $circleIds): void {
		$this->db->beginTransaction();
		try {
			foreach ($circleIds as $position => $circleId) {
				$update = $this->getQueryBuilder();
				$update->update(self::TABLE_TEAM_FAVORITES)
					->set('position', $update->createNamedParameter($position, IQueryBuilder::PARAM_INT))
					->where($update->expr()->eq('user_id', $update->createNamedParameter($userId)))
					->andWhere($update->expr()->eq('circle_id', $update->createNamedParameter($circleId)));
				$update->executeStatement();
			}
			$this->db->commit();
		} catch (\Throwable $exception) {
			$this->db->rollBack();
			throw $exception;
		}
	}

	public function removeCircle(string $circleId): void {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_TEAM_FAVORITES)
			->where($qb->expr()->eq('circle_id', $qb->createNamedParameter($circleId)));
		$qb->executeStatement();
	}

	public function removeUser(string $userId): void {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_TEAM_FAVORITES)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
		$qb->executeStatement();
	}
}
