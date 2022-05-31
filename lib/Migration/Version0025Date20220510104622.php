<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2022
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
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version0025Date20220510104622 extends SimpleMigrationStep {


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
			if (!$table->hasColumn('event_type')) {
				$table->addColumn(
					'event_type', Types::STRING, [
									'notnull' => false,
									'default' => 'broadcast',
									'length' => 15
								]
				);
				$table->addIndex(['event_type']);
			}
			if (!$table->hasColumn('store')) {
				$table->addColumn(
					'store', Types::TEXT, [
							   'notnull' => false,
							   'default' => ''
						   ]
				);
			}
		}

		if (!$schema->hasTable('circles_item')) {
			$table = $schema->createTable('circles_item');
			$table->addColumn(
				'id', Types::INTEGER, [
						'autoincrement' => true,
						'notnull' => true,
						'length' => 11,
						'unsigned' => true,
					]
			);
			$table->addColumn(
				'single_id', Types::STRING, [
							   'notnull' => false,
							   'length' => 31,
						   ]
			);
			$table->addColumn(
				'instance', Types::STRING, [
							  'notnull' => false,
							  'length' => 255,
						  ]
			);
			$table->addColumn(
				'app_id', Types::STRING, [
							'length' => 255,
							'notnull' => false
						]
			);
			$table->addColumn(
				'item_type', Types::STRING, [
							   'length' => 127,
							   'notnull' => false
						   ]
			);
			$table->addColumn(
				'item_id', Types::STRING, [
							 'length' => 63,
							 'notnull' => false
						 ]
			);
			$table->addColumn(
				'checksum', Types::STRING, [
							  'length' => 127,
							  'notnull' => false
						  ]
			);
			$table->addColumn(
				'deleted', Types::BOOLEAN, [
							 'notnull' => false,
							 'default' => false
						 ]
			);

			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['single_id']);
			$table->addIndex(['app_id', 'item_type', 'item_id'], 'c_aiitii');
		}

		if (!$schema->hasTable('circles_share')) {
			$table = $schema->createTable('circles_share');
			$table->addColumn(
				'id', Types::INTEGER, [
						'autoincrement' => true,
						'notnull' => true,
						'length' => 11,
						'unsigned' => true,
					]
			);
			$table->addColumn(
				'single_id', Types::STRING, [
							   'notnull' => false,
							   'length' => 31,
						   ]
			);
			$table->addColumn(
				'circle_id', Types::STRING, [
							   'notnull' => false,
							   'length' => 31,
						   ]
			);

			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['single_id', 'circle_id'], 'c_sici');
		}

		if (!$schema->hasTable('circles_lock')) {
			$table = $schema->createTable('circles_lock');
			$table->addColumn(
				'id', Types::BIGINT, [
						'autoincrement' => true,
						'notnull' => true,
						'length' => 11,
						'unsigned' => true,
					]
			);
//			$table->addColumn(
//				'single_id', Types::STRING, [
//							   'notnull' => false,
//							   'length' => 31,
//						   ]
//			);
			$table->addColumn(
				'update_type', Types::STRING, [
								 'notnull' => false,
								 'length' => 31,
							 ]
			);
			$table->addColumn(
				'update_type_id', Types::STRING, [
									'notnull' => false,
									'length' => 31,
								]
			);
			$table->addColumn(
				'time', Types::INTEGER, [
						  'notnull' => false,
						  'length' => 7,
						  'unsigned' => true
					  ]
			);

			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['update_type', 'update_type_id'], 'c_ututi');
		}

		if (!$schema->hasTable('circles_debug')) {
			$table = $schema->createTable('circles_debug');
			$table->addColumn(
				'id', Types::BIGINT, [
						'autoincrement' => true,
						'notnull' => true,
						'length' => 14,
						'unsigned' => true,
					]
			);
			$table->addColumn(
				'thread', Types::STRING, [
							'notnull' => false,
							'length' => 31,
						]
			);
			$table->addColumn(
				'type', Types::STRING, [
						  'notnull' => false,
						  'length' => 31,
					  ]
			);
			$table->addColumn(
				'circle_id', Types::STRING, [
							   'notnull' => false,
							   'length' => 31,
						   ]
			);
			$table->addColumn(
				'instance', Types::STRING, [
							  'notnull' => false,
							  'length' => 127,
						  ]
			);
			$table->addColumn(
				'debug', Types::TEXT, [
						   'notnull' => false
					   ]
			);
			$table->addColumn(
				'time', Types::INTEGER, [
						  'notnull' => false,
						  'length' => 7,
						  'unsigned' => true
					  ]
			);

			$table->setPrimaryKey(['id']);
			$table->addIndex(['circle_id', 'instance'], 'circles_debug_cii');
			$table->addIndex(['time']);
		}

		return $schema;
	}
}
