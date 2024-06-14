<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Migration;

use Closure;
use Doctrine\DBAL\Schema\SchemaException;
use OC\DB\Connection;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Class Version0021Date20210105123456
 *
 * @package OCA\Circles\Migration
 */
class Version0022Date20220526111723 extends SimpleMigrationStep {
	/** @var Connection */
	private $connection;


	/**
	 * @param Connection $connection
	 */
	public function __construct(Connection $connection) {
		$this->connection = $connection;
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

		// check if migration to 22 already done
		if ($schema->hasTable('circles_event')) {
			return $schema;
		}

		$prefix = $this->connection->getPrefix();
		$tables = $schema->getTables();
		foreach ($tables as $table) {
			$tableName = $table->getName();
			if (substr($tableName, 0, 8 + strlen($prefix)) === $prefix . 'circles_') {
				$tableName = substr($tableName, strlen($prefix));
				$schema->dropTable($tableName);
			}
		}

		return $schema;
	}
}
