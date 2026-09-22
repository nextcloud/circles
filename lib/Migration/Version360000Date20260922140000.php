<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\Attributes\AddIndex;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\Attributes\DropColumn;
use OCP\Migration\Attributes\DropIndex;
use OCP\Migration\Attributes\IndexType;
use OCP\Migration\Attributes\ModifyColumn;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

/**
 * (mount_id, single_id) is the real natural key of circles_mountpoint: at most one alternate
 * mountpoint per member per mount. Nothing ever enforced that, so the composite key is made
 * primary here, replacing the surrogate autoincrement id.
 */
#[ModifyColumn(table: 'circles_mountpoint', name: 'mount_id', type: ColumnType::STRING)]
#[ModifyColumn(table: 'circles_mountpoint', name: 'single_id', type: ColumnType::STRING)]
#[DropIndex(table: 'circles_mountpoint', type: IndexType::INDEX, description: 'circles_mountpoint_ms is redundant with the new primary key')]
#[DropColumn(table: 'circles_mountpoint', name: 'id')]
#[AddIndex(table: 'circles_mountpoint', type: IndexType::PRIMARY)]
final class Version360000Date20260922140000 extends SimpleMigrationStep {
	public function __construct(
		private readonly IDBConnection $connection,
	) {
	}

	#[Override]
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$delete = $this->connection->getQueryBuilder();
		$delete->delete('circles_mountpoint')
			->where($delete->expr()->orX(
				$delete->expr()->isNull('mount_id'),
				$delete->expr()->eq('mount_id', $delete->createNamedParameter('')),
				$delete->expr()->isNull('single_id'),
				$delete->expr()->eq('single_id', $delete->createNamedParameter('')),
			));
		$delete->executeStatement();

		// A few installs may have raced two writers into inserting two rows for the same
		// (mount_id, single_id) before this constraint existed; keep the oldest one.
		$select = $this->connection->getQueryBuilder();
		$select->select('id', 'mount_id', 'single_id')
			->from('circles_mountpoint')
			->orderBy('id', 'ASC');
		$result = $select->executeQuery();

		$seen = [];
		$duplicateIds = [];
		while ($row = $result->fetch()) {
			$key = $row['mount_id'] . "\0" . $row['single_id'];
			if (isset($seen[$key])) {
				$duplicateIds[] = (int)$row['id'];
				continue;
			}
			$seen[$key] = true;
		}
		$result->closeCursor();

		foreach ($duplicateIds as $id) {
			$delete = $this->connection->getQueryBuilder();
			$delete->delete('circles_mountpoint')
				->where($delete->expr()->eq('id', $delete->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
			$delete->executeStatement();
		}
	}

	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('circles_mountpoint')) {
			return null;
		}

		$table = $schema->getTable('circles_mountpoint');
		$table->modifyColumn('mount_id', ['notnull' => true, 'default' => '']);
		$table->modifyColumn('single_id', ['notnull' => true, 'default' => '']);

		if ($table->hasIndex('circles_mountpoint_ms')) {
			$table->dropIndex('circles_mountpoint_ms');
		}

		if ($table->hasPrimaryKey()) {
			$table->dropPrimaryKey();
		}

		if ($table->hasColumn('id')) {
			$table->dropColumn('id');
		}

		$table->setPrimaryKey(['mount_id', 'single_id']);

		return $schema;
	}
}
