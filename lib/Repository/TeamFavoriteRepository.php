<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Repository;

use OCA\Circles\Entity\TeamFavorite;
use OCP\AppFramework\ORM\Repository;

/**
 * @extends Repository<TeamFavorite>
 */
class TeamFavoriteRepository extends Repository {
	public const string entityClass = TeamFavorite::class;

	/**
	 * @return list<string>
	 */
	public function getCircleIds(string $userId, bool $accessibleOnly = false): array {
		$qb = $this->connection->getQueryBuilder();
		$qb->selectDistinct(['favorite.circle_id', 'favorite.position'])
			->from($this->getTableName(), 'favorite')
			->where($qb->expr()->eq('favorite.user_id', $qb->createNamedParameter($userId)));

		if ($accessibleOnly) {
			$expr = $qb->expr();
			$qb->innerJoin('favorite', 'circles_member', 'user_member',
				$expr->eq('user_member.user_id', 'favorite.user_id'));
			$qb->leftJoin('user_member', 'circles_membership', 'membership', $expr->andX(
				$expr->eq('membership.single_id', 'user_member.single_id'),
				$expr->eq('membership.circle_id', 'favorite.circle_id'),
			));
			$qb->leftJoin('membership', 'circles_member', 'inherited_member', $expr->andX(
				$expr->eq('inherited_member.single_id', 'membership.inheritance_first'),
				$expr->eq('inherited_member.circle_id', 'favorite.circle_id'),
			));
			$qb->andWhere($expr->orX(
				$expr->eq('user_member.circle_id', 'favorite.circle_id'),
				$expr->isNotNull('inherited_member.single_id'),
			));
		}

		$qb->orderBy('favorite.position', 'ASC')
			->addOrderBy('favorite.circle_id', 'ASC');

		$result = $qb->executeQuery();
		$circleIds = [];
		while ($row = $result->fetch()) {
			$circleIds[] = $row['circle_id'];
		}
		$result->closeCursor();

		return $circleIds;
	}

	public function add(string $userId, string $circleId): void {
		$last = iterator_to_array($this->findBy(
			['userId' => $userId],
			['position' => \SortDirection::Descending],
			1,
		), false);

		$favorite = new TeamFavorite();
		$favorite->userId = $userId;
		$favorite->circleId = $circleId;
		$favorite->position = $last === [] ? 0 : $last[0]->position + 1;
		$this->insert($favorite);
	}

	public function remove(string $userId, string $circleId): void {
		$this->deleteBy(['userId' => $userId, 'circleId' => $circleId]);
	}

	/**
	 * @param list<string> $circleIds
	 */
	public function replaceOrder(string $userId, array $circleIds): void {
		$positions = array_flip($circleIds);
		$this->connection->beginTransaction();
		try {
			foreach ($this->findBy(['userId' => $userId]) as $favorite) {
				if (!isset($positions[$favorite->circleId])) {
					continue;
				}
				$favorite->position = $positions[$favorite->circleId];
				$this->update($favorite);
			}
			$this->connection->commit();
		} catch (\Throwable $exception) {
			$this->connection->rollBack();
			throw $exception;
		}
	}

	public function removeCircle(string $circleId): void {
		$this->deleteBy(['circleId' => $circleId]);
	}

	public function removeUser(string $userId): void {
		$this->deleteBy(['userId' => $userId]);
	}
}
