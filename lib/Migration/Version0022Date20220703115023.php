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
class Version0022Date20220703115023 extends SimpleMigrationStep {
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
			if (!$table->hasColumn('retry')) {
				$table->addColumn(
					'retry', 'integer', [
						'length' => 3,
						'notnull' => false
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
						'length' => 31,
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
						'length' => 127
					]
				);

				$table->addIndex(['sanitized_name']);
			}
		}


		if ($schema->hasTable('circles_membership')) {
			$table = $schema->getTable('circles_membership');
			$table->changeColumn(
				'circle_id', [
					'notnull' => true,
					'length' => 31,
				]
			);
			$table->changeColumn(
				'single_id', [
					'notnull' => true,
					'length' => 31,
				]
			);
			$table->changeColumn(
				'inheritance_first', [
					'notnull' => true,
					'length' => 31,
				]
			);
			$table->changeColumn(
				'inheritance_last', [
					'notnull' => true,
					'length' => 31,
				]
			);
		}


		if ($schema->hasTable('circles_mount')) {
			$table = $schema->getTable('circles_mount');
			$table->changeColumn(
				'mount_id', [
					'notnull' => true,
					'length' => 31,
				]
			);
			$table->changeColumn(
				'circle_id', [
					'notnull' => true,
					'length' => 31,
				]
			);
			$table->changeColumn(
				'single_id', [
					'notnull' => true,
					'length' => 31,
				]
			);
		}


		if ($schema->hasTable('circles_mountpoint')) {
			$table = $schema->getTable('circles_mountpoint');
			$table->changeColumn(
				'mount_id', [
					'notnull' => true,
					'length' => 31,
				]
			);
			$table->changeColumn(
				'single_id', [
					'notnull' => true,
					'length' => 31,
				]
			);
		}


		if ($schema->hasTable('circles_share_lock')) {
			$table = $schema->getTable('circles_share_lock');
			$table->changeColumn(
				'item_id', [
					'notnull' => true,
					'length' => 31,
				]
			);
			$table->changeColumn(
				'circle_id', [
					'notnull' => true,
					'length' => 31,
				]
			);
		}



		/**
		 * CIRCLES_TOKEN
		 */
		if (!$schema->hasTable('circles_token')) {
			$table = $schema->createTable('circles_token');
			$table->addColumn(
				'id', 'integer', [
					'autoincrement' => true,
					'notnull' => true,
					'length' => 11,
					'unsigned' => true,
				]
			);
			$table->addColumn(
				'share_id', 'integer', [
					'notnull' => false,
					'length' => 11
				]
			);
			$table->addColumn(
				'circle_id', 'string', [
					'notnull' => false,
					'length' => 31
				]
			);
			$table->addColumn(
				'single_id', 'string', [
					'notnull' => false,
					'length' => 31
				]
			);
			$table->addColumn(
				'member_id', 'string', [
					'notnull' => false,
					'length' => 31
				]
			);
			$table->addColumn(
				'token', 'string', [
					'notnull' => false,
					'length' => 31
				]
			);
			$table->addColumn(
				'password', 'string', [
					'notnull' => false,
					'length' => 31
				]
			);
			$table->addColumn(
				'accepted', 'integer', [
					'notnull' => false,
					'length' => 1
				]
			);

			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['share_id', 'circle_id', 'single_id', 'member_id', 'token'], 'sicisimit');
		}


		return $schema;
	}
}
