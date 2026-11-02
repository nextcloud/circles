<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Schema\ColumnType;
use OCP\DB\Schema\SchemaException;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version20260922120000 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The closure returns a schema wrapper.
	 * @param array $options
	 *
	 * @throws SchemaException
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('circles_team_favorites')) {
			$table = $schema->createTable('circles_team_favorites');
			$table->addColumn('id', ColumnType::Integer, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 8,
				'unsigned' => true,
			]);
			$table->addColumn('user_id', ColumnType::String, [
				'length' => 255,
				'notnull' => true,
			]);
			$table->addColumn('circle_id', ColumnType::String, [
				'length' => 32,
				'notnull' => true,
			]);
			$table->addColumn('position', ColumnType::Integer, [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('created', ColumnType::Datetime, [
				'notnull' => true,
			]);

			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['user_id', 'circle_id']);
			$table->addIndex(['user_id', 'position']);
			$table->addIndex(['circle_id']);
		}

		return $schema;
	}
}
