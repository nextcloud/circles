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
 * Class Version0021Date20210105123456
 *
 * @package OCA\Circles\Migration
 */
class Version0021Date20210105123456 extends SimpleMigrationStep {


	/** @var IDBConnection */
	private $connection;


	/**
	 * @param IDBConnection $connection
	 */
	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}


	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		try {
			$circles = $schema->getTable('circle_circles');
			if (!$circles->hasColumn('config')) {
				$circles->addColumn(
					'config', 'integer', [
								'notnull'  => false,
								'length'   => 11,
								'unsigned' => true
							]
				);
			}
			if (!$circles->hasColumn('source')) {
				$circles->addColumn(
					'source', 'integer', [
								'notnull'  => false,
								'length'   => 5,
								'unsigned' => true
							]
				);
			}
			if (!$circles->hasColumn('instance')) {
				$circles->addColumn(
					'instance', 'string', [
								  'notnull' => false,
								  'default' => '',
								  'length'  => 255
							  ]
				);
			}
			if (!$circles->hasColumn('display_name')) {
				$circles->addColumn(
					'display_name', 'string', [
									  'notnull' => false,
									  'default' => '',
									  'length'  => 127
								  ]
				);
			}
			$circles->addIndex(['config']);
		} catch (SchemaException $e) {
		}


		try {
			$circles = $schema->getTable('circle_members');
			if (!$circles->hasColumn('single_id')) {
				$circles->addColumn(
					'single_id', 'string', [
								   'notnull' => false,
								   'length'  => 15
							   ]
				);
			}
			if (!$circles->hasColumn('circle_source')) {
				$circles->addColumn(
					'circle_source', 'string', [
									   'notnull' => false,
									   'length'  => 63
								   ]
				);
			}
		} catch (SchemaException $e) {
		}


		if (!$schema->hasTable('circle_remotes')) {
			$table = $schema->createTable('circle_remotes');
			$table->addColumn(
				'id', 'integer', [
						'autoincrement' => true,
						'notnull'       => true,
						'length'        => 4,
						'unsigned'      => true,
					]
			);
			$table->addColumn(
				'type', 'string', [
						  'notnull' => true,
						  'length'  => 15,
						  'default' => 'Unknown'
					  ]
			);
			$table->addColumn(
				'uid', 'string', [
						 'notnull' => false,
						 'length'  => 20,
					 ]
			);
			$table->addColumn(
				'instance', 'string', [
							  'notnull' => false,
							  'length'  => 127,
						  ]
			);
			$table->addColumn(
				'href', 'string', [
						  'notnull' => false,
						  'length'  => 254,
					  ]
			);
			$table->addColumn(
				'item', 'text', [
						  'notnull' => false,
					  ]
			);
			$table->addColumn(
				'creation', 'datetime', [
							  'notnull' => false,
						  ]
			);

			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['instance']);
			$table->addIndex(['uid']);
			$table->addIndex(['href']);
		}


		if (!$schema->hasTable('circle_events')) {
			$table = $schema->createTable('circle_events');
			$table->addColumn(
				'token', 'string', [
						   'notnull' => false,
						   'length'  => 63,
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
							  'length'  => 255,
							  'notnull' => false
						  ]
			);
			$table->addColumn(
				'severity', 'integer', [
							  'length'  => 3,
							  'notnull' => false
						  ]
			);
			$table->addColumn(
				'status', 'integer', [
							'length'  => 3,
							'notnull' => false
						]
			);
			$table->addColumn(
				'creation', 'bigint', [
							  'length'  => 14,
							  'notnull' => false
						  ]
			);

			$table->addUniqueIndex(['token', 'instance']);
		}


		if (!$schema->hasTable('circle_membership')) {
			$table = $schema->createTable('circle_membership');

			$table->addColumn(
				'circle_id', 'string', [
							   'notnull' => true,
							   'length'  => 15,
						   ]
			);
			$table->addColumn(
				'single_id', 'string', [
							   'notnull' => true,
							   'length'  => 15,
						   ]
			);
			$table->addColumn(
				'level', 'integer', [
						   'notnull'  => true,
						   'length'   => 1,
						   'unsigned' => true
					   ]
			);
			$table->addColumn(
				'inheritance_first', 'string', [
									   'notnull' => true,
									   'length'  => 15,
								   ]
			);
			$table->addColumn(
				'inheritance_last', 'string', [
									  'notnull' => true,
									  'length'  => 15,
								  ]
			);
			$table->addColumn(
				'inheritance_depth', 'integer', [
									   'notnull'  => true,
									   'length'   => 2,
									   'unsigned' => true
								   ]
			);
			$table->addColumn(
				'inheritance_path', 'text', [
									  'notnull' => true
								  ]
			);

			$table->addIndex(['single_id']);
			$table->addUniqueIndex(['single_id', 'circle_id']);
			$table->addIndex(['inheritance_first', 'inheritance_last', 'circle_id'], 'ifilci');
		}


		if (!$schema->hasTable('circle_mount')) {
			$table = $schema->createTable('circle_mount');
			$table->addColumn(
				'id', 'integer', [
						'autoincrement' => true,
						'notnull'       => true,
						'length'        => 11,
						'unsigned'      => true,
					]
			);
			$table->addColumn(
				'mount_id', 'string', [
							  'notnull' => false,
							  'length'  => 15
						  ]
			);
			$table->addColumn(
				'circle_id', 'string', [
							   'notnull' => false,
							   'length'  => 15
						   ]
			);
			$table->addColumn(
				'single_id', 'string', [
						   'notnull' => false,
						   'length'  => 15
					   ]
			);
			$table->addColumn(
				'token', 'string', [
						   'notnull' => false,
						   'length'  => 63
					   ]
			);
			$table->addColumn(
				'parent', 'integer', [
							'notnull' => false,
							'length'  => 11
						]
			);
			$table->addColumn(
				'mountpoint', 'text', [
								'notnull' => false
							]
			);
			$table->addColumn(
				'mountpoint_hash', 'string', [
									 'notnull' => false,
									 'length'  => 64
								 ]
			);

			$table->setPrimaryKey(['id']);
			$table->addIndex(['circle_id', 'mount_id', 'parent', 'token'], 'cmpt');
		}


		if (!$schema->hasTable('circle_mountpoint')) {
			$table = $schema->createTable('circle_mountpoint');
			$table->addColumn(
				'id', 'integer', [
						'autoincrement' => true,
						'notnull'       => true,
						'length'        => 11,
						'unsigned'      => true,
					]
			);
			$table->addColumn(
				'mount_id', 'string', [
							  'notnull' => false,
							  'length'  => 15
						  ]
			);
			$table->addColumn(
				'single_id', 'string', [
							   'notnull' => false,
							   'length'  => 15
						   ]
			);
			$table->addColumn(
				'mountpoint', 'text', [
								'notnull' => false
							]
			);
			$table->addColumn(
				'mountpoint_hash', 'string', [
									 'notnull' => false,
									 'length'  => 64
								 ]
			);

			$table->setPrimaryKey(['id']);
			$table->addIndex(['mount_id', 'single_id'], 'ms');
		}


		if (!$schema->hasTable('circle_share_locks')) {
			$table = $schema->createTable('circle_share_locks');
			$table->addColumn(
				'id', 'integer', [
						'autoincrement' => true,
						'notnull'       => true,
						'length'        => 4,
						'unsigned'      => true,
					]
			);
			$table->addColumn(
				'item_id', 'string', [
							 'notnull' => true,
							 'length'  => 15
						 ]
			);
			$table->addColumn(
				'circle_id', 'string', [
							   'notnull' => true,
							   'length'  => 15
						   ]
			);
			$table->addColumn(
				'instance', 'string', [
							  'notnull' => true,
							  'length'  => 127,
						  ]
			);

			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['item_id', 'circle_id']);
		}

		return $schema;
	}


}
