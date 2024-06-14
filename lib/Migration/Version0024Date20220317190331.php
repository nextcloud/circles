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

class Version0024Date20220317190331 extends SimpleMigrationStep {
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

		if ($schema->hasTable('circles_membership')) {
			$table = $schema->getTable('circles_membership');
			if (!$table->hasPrimaryKey()) {
				$indexes = $table->getIndexes();
				// conflict in Oracle with existing unique index, duplicate of primaryKey.
				foreach ($indexes as $index) {
					if ($index->isUnique()) {
						$table->dropIndex($index->getName());
					}
				}
				$table->setPrimaryKey(['single_id', 'circle_id']);
			}
		}

		return $schema;
	}
}
