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

/**
 * Class Version0024Date20220203123901
 *
 * @package OCA\Circles\Migration
 */
class Version0024Date20220203123901 extends SimpleMigrationStep {
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

		if ($schema->hasTable('circles_token')) {
			$table = $schema->getTable('circles_token');
			$table->changeColumn(
				'password', [
					'length' => 127
				]
			);
		}

		if ($schema->hasTable('circles_member')) {
			$table = $schema->getTable('circles_member');
			$table->changeColumn(
				'instance',
				[
					'default' => '',
					'notnull' => false,
					'length' => 255
				]
			);
		}

		if ($schema->hasTable('circles_circle')) {
			$table = $schema->getTable('circles_circle');
			$table->changeColumn(
				'display_name',
				[
					'notnull' => false,
					'default' => '',
					'length' => 255
				]
			);
		}

		// dropping to be re-created with the right primary keys.
		if ($schema->hasTable('circles_event')) {
			$schema->dropTable('circles_event');
		}

		return $schema;
	}
}
