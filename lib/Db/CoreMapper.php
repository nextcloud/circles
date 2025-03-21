<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Db;

use OCA\Circles\Model\Team;
use OCA\Circles\Model\TeamEntity;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Server;
use Psr\Log\LoggerInterface;

class CoreMapper extends QBMapper {
	/** @var array<string, list<string>> [class => list<fieldNames>] */
	private array $fields = [];

	/**
	 * returns the list of database table field names from an Entity
	 *
	 * @return string[]
	 */
	final protected function getFields(string $entityClass): array {
		if (!array_key_exists($entityClass, $this->fields)) {
			$entity = new $entityClass();
			if ($entity instanceof Entity) {
				$this->fields[$entityClass] = array_map(static function(string $property) use ($entity): string {
					return $entity->propertyToColumn($property);
				}, array_keys($entity->getFieldTypes()));
			} else {
				Server::get(LoggerInterface::class)->notice('calling CoreMapper::getFields() with a non-Entity model', ['entityClass' => $entityClass]);
				$this->fields[$entityClass] = [];
			}
		}

		return $this->fields[$entityClass];
	}

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

		// TODO finish, based on useMemberships
	}
}
