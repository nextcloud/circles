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
				'id', 'integer', [
					'autoincrement' => true,
					'notnull' => true,
					'length' => 8,
					'unsigned' => true,
				]
			);
			$table->addColumn(
				'circle_id', 'string', [
					'length' => 32,
					'notnull' => true,
				]
			);
			$table->addColumn(
				'invitation_code', 'string', [
					'length' => 16,
					'notnull' => true,
				]
			);
			$table->addColumn(
				'created_by', 'string', [
					'length' => 255,
					'notnull' => true,
				]
			);
			$table->addColumn(
				'created', 'datetime', [
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
