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
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version0024Date20220203123902 extends SimpleMigrationStep {
	/**
	 * @param IDBConnection $connection
	 */
	public function __construct(IDBConnection $connection) {
	}

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

		if (!$schema->hasTable('circles_event')) {
			$table = $schema->createTable('circles_event');
			$table->addColumn(
				'token', ColumnType::String, [
					'notnull' => false,
					'length' => 63,
				]
			);
			$table->addColumn(
				'event', ColumnType::Text, [
					'notnull' => false
				]
			);
			$table->addColumn(
				'result', ColumnType::Text, [
					'notnull' => false
				]
			);
			$table->addColumn(
				'instance', ColumnType::String, [
					'length' => 255,
					'notnull' => false
				]
			);
			$table->addColumn(
				'interface', ColumnType::Integer, [
					'notnull' => true,
					'length' => 1,
					'default' => 0
				]
			);
			$table->addColumn(
				'severity', ColumnType::Integer, [
					'length' => 3,
					'notnull' => false
				]
			);
			$table->addColumn(
				'retry', ColumnType::Integer, [
					'length' => 3,
					'notnull' => false
				]
			);
			$table->addColumn(
				'status', ColumnType::Integer, [
					'length' => 3,
					'notnull' => false
				]
			);
			$table->addColumn(
				'updated', ColumnType::Datetime, [
					'notnull' => false,
				]
			);
			$table->addColumn(
				'creation', ColumnType::Bigint, [
					'length' => 14,
					'notnull' => false
				]
			);

			$table->setPrimaryKey(['token', 'instance']);
		}

		return $schema;
	}
}
