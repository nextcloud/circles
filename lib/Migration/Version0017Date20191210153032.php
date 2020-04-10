<?php declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2019
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
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;


class Version0017Date20191210153032 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @return null|ISchemaWrapper
	 * @throws SchemaException
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('circles_gsevents')) {
			$table = $schema->createTable('circles_gsevents');
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

		if (!$schema->hasTable('circles_gsshares')) {
			$table = $schema->createTable('circles_gsshares');
			$table->addColumn(
				'id', 'integer', [
						'notnull'       => false,
						'length'        => 11,
						'autoincrement' => true,
						'unsigned'      => true
					]
			);
			$table->addColumn(
				'circle_id', 'string', [
							   'length'  => 15,
							   'notnull' => false
						   ]
			);
			$table->addColumn(
				'owner', 'string', [
						   'length'  => 15,
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
				'token', 'string', [
						   'notnull' => false,
						   'length'  => 63
					   ]
			);
			$table->addColumn(
				'parent', 'integer', [
							'notnull' => false,
							'length'  => 11,
						]
			);
			$table->addColumn(
				'mountpoint', 'text', [
								'notnull' => false
							]
			);
			$table->addColumn(
				'mountpoint_hash', 'string', [
									 'length'  => 64,
									 'notnull' => false
								 ]
			);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['circle_id', 'mountpoint_hash']);
		}

		if (!$schema->hasTable('circles_gsshares_mp')) {
			$table = $schema->createTable('circles_gsshares_mp');
			$table->addColumn(
				'share_id', 'integer', [
							  'length'  => 11,
							  'notnull' => false
						  ]
			);
			$table->addColumn(
				'user_id', 'string', [
							 'length'  => 127,
							 'notnull' => false
						 ]
			);
			$table->addColumn(
				'mountpoint', 'text', [
								'notnull' => false
							]
			);
			$table->addColumn(
				'mountpoint_hash', 'string', [
									 'length'  => 64,
									 'notnull' => false
								 ]
			);
			$table->setPrimaryKey(['share_id', 'user_id']);
		}

		return $schema;
	}


	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
	}

}

