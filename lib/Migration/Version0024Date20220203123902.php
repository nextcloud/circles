<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Migration;

use Closure;
use Doctrine\DBAL\Schema\SchemaException;
use OCP\DB\ISchemaWrapper;
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
				'token', 'string', [
					'notnull' => false,
					'length' => 63,
				]
			);
			$table->addColumn(
				'event', 'text', [
					'notnull' => false
				]
			);
			$table->addColumn(
				'result', 'text', [
					'notnull' => false
				]
			);
			$table->addColumn(
				'instance', 'string', [
					'length' => 255,
					'notnull' => false
				]
			);
			$table->addColumn(
				'interface', 'integer', [
					'notnull' => true,
					'length' => 1,
					'default' => 0
				]
			);
			$table->addColumn(
				'severity', 'integer', [
					'length' => 3,
					'notnull' => false
				]
			);
			$table->addColumn(
				'retry', 'integer', [
					'length' => 3,
					'notnull' => false
				]
			);
			$table->addColumn(
				'status', 'integer', [
					'length' => 3,
					'notnull' => false
				]
			);
			$table->addColumn(
				'updated', 'datetime', [
					'notnull' => false,
				]
			);
			$table->addColumn(
				'creation', 'bigint', [
					'length' => 14,
					'notnull' => false
				]
			);

			$table->setPrimaryKey(['token', 'instance']);
		}

		return $schema;
	}
}
