<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Schema\ColumnType;
use OCP\DB\Schema\SchemaException;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version8100Date20261129153333 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @return null|ISchemaWrapper
	 * @throws SchemaException
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('circles_invitations')) {
			$table = $schema->createTable('circles_invitations');

			$table->addColumn(
				'id', ColumnType::Integer, [
					'autoincrement' => true,
					'notnull' => true,
					'length' => 8,
					'unsigned' => true,
				]
			);
			$table->addColumn(
				'circle_id', ColumnType::String, [
					'length' => 32,
					'notnull' => true,
				]
			);
			$table->addColumn(
				'invitation_code', ColumnType::String, [
					'length' => 16,
					'notnull' => true,
				]
			);
			$table->addColumn(
				'created_by', ColumnType::String, [
					'length' => 255,
					'notnull' => true,
				]
			);
			$table->addColumn(
				'created', ColumnType::Datetime, [
					'notnull' => true,
				]
			);

			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['circle_id']);
			$table->addUniqueIndex(['invitation_code']);
		}

		return $schema;
	}
}
