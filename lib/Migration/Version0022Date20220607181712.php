<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Circles\Migration;

use Closure;
use Doctrine\DBAL\Schema\SchemaException;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;


/**
 * Class Version0022Date20220526113601
 *
 * @package OCA\Circles\Migration
 */
class Version0022Date20220607181712 extends SimpleMigrationStep {


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

		if ($schema->hasTable('circles_event')) {
			$table = $schema->getTable('circles_event');
			if (!$table->hasColumn('updated')) {
				$table->addColumn(
					'updated', 'datetime', [
								 'notnull' => false,
							 ]
				);
			}
		}

		if ($schema->hasTable('circles_member')) {
			$table = $schema->getTable('circles_member');
			if (!$table->hasColumn('invited_by')) {
				$table->addColumn(
					'invited_by', 'string', [
									'notnull' => false,
									'default' => '',
									'length'  => 31,
								]
				);
			}
		}

		if ($schema->hasTable('circles_circle')) {
			$table = $schema->getTable('circles_circle');
			if (!$table->hasColumn('sanitized_name')) {
				$table->addColumn(
					'sanitized_name', 'string', [
										'notnull' => false,
										'default' => '',
										'length'  => 127
									]
				);

				$table->addIndex(['sanitized_name']);
			}
		}

		return $schema;
	}

}

