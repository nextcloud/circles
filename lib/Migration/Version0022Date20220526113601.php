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
use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Class Version0022Date20220526113601
 *
 * @package OCA\Circles\Migration
 */
class Version0022Date20220526113601 extends SimpleMigrationStep {
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


		/**
		 * CIRCLES_CIRCLE
		 */
		if (!$schema->hasTable('circles_circle')) {
			$table = $schema->createTable('circles_circle');

			$table->addColumn(
				'id', 'integer', [
					'autoincrement' => true,
					'notnull' => true,
					'length' => 4,
					'unsigned' => true,
				]
			);
			$table->addColumn(
				'unique_id', 'string', [
					'notnull' => true,
					'length' => 31,
				]
			);
			$table->addColumn(
				'name', 'string', [
					'notnull' => true,
					'length' => 127,
				]
			);
			$table->addColumn(
				'display_name', 'string', [
					'notnull' => false,
					'default' => '',
					'length' => 255
				]
			);
			$table->addColumn(
				'sanitized_name', 'string', [
					'notnull' => false,
					'default' => '',
					'length' => 127
				]
			);
			$table->addColumn(
				'instance', 'string', [
					'notnull' => false,
					'default' => '',
					'length' => 255
				]
			);
			$table->addColumn(
				'config', 'integer', [
					'notnull' => false,
					'length' => 11,
					'unsigned' => true
				]
			);
			$table->addColumn(
				'source', 'integer', [
					'notnull' => false,
					'length' => 5,
					'unsigned' => true
				]
			);
			$table->addColumn(
				'settings', 'text', [
					'notnull' => false
				]
			);
			$table->addColumn(
				'description', 'text', [
					'notnull' => false
				]
			);
			$table->addColumn(
				'creation', 'datetime', [
					'notnull' => false,
				]
			);
			$table->addColumn(
				'contact_addressbook', 'integer', [
					'notnull' => false,
					'unsigned' => true,
					'length' => 7,
				]
			);
			$table->addColumn(
				'contact_groupname', 'string', [
					'notnull' => false,
					'length' => 127,
				]
			);

			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['unique_id']);
			$table->addIndex(['config']);
			$table->addIndex(['instance']);
			$table->addIndex(['source']);
			$table->addIndex(['sanitized_name']);
		}


		/**
		 * CIRCLES_MEMBER
		 */
		if (!$schema->hasTable('circles_member')) {
			$table = $schema->createTable('circles_member');

			$table->addColumn(
				'id', 'integer', [
					'autoincrement' => true,
					'notnull' => true,
					'length' => 4,
					'unsigned' => true,
				]
			);
			$table->addColumn(
				'single_id', 'string', [
					'notnull' => false,
					'length' => 31
				]
			);
			$table->addColumn(
				'circle_id', 'string', [
					'notnull' => true,
					'length' => 31,
				]
			);
			$table->addColumn(
				'member_id', Types::STRING, [
					'notnull' => false,
					'length' => 31,
				]
			);
			$table->addColumn(
				'user_id', 'string', [
					'notnull' => true,
					'length' => 127,
				]
			);
			$table->addColumn(
				'user_type', 'smallint', [
					'notnull' => true,
					'length' => 1,
					'default' => 1,
				]
			);
			$table->addColumn(
				'instance', 'string', [
					'default' => '',
					'length' => 255
				]
			);
			$table->addColumn(
				'invited_by', 'string', [
					'notnull' => false,
					'length' => 31,
				]
			);
			$table->addColumn(
				'level', 'smallint', [
					'notnull' => true,
					'length' => 1,
				]
			);
			$table->addColumn(
				'status', 'string', [
					'notnull' => false,
					'length' => 15,
				]
			);
			$table->addColumn(
				'note', 'text', [
					'notnull' => false
				]
			);
			$table->addColumn(
				'cached_name', 'string', [
					'notnull' => false,
					'length' => 255,
					'default' => ''
				]
			);
			$table->addColumn(
				'cached_update', 'datetime', [
					'notnull' => false,
				]
			);
			$table->addColumn(
				'contact_id', 'string', [
					'notnull' => false,
					'length' => 127,
				]
			);
			$table->addColumn(
				'contact_meta', 'text', [
					'notnull' => false
				]
			);
			$table->addColumn(
				'joined', 'datetime', [
					'notnull' => false,
				]
			);

			$table->setPrimaryKey(['id']);
			$table->addIndex(
				['circle_id', 'single_id', 'user_id', 'user_type', 'instance', 'level'],
				'circles_member_cisiuiutil'
			);
			$table->addIndex(['circle_id', 'single_id'], 'circles_member_cisi');
			$table->addIndex(['contact_id']);
		}


		/**
		 * CIRCLES_REMOTE
		 */
		if (!$schema->hasTable('circles_remote')) {
			$table = $schema->createTable('circles_remote');
			$table->addColumn(
				'id', 'integer', [
					'autoincrement' => true,
					'notnull' => true,
					'length' => 4,
					'unsigned' => true,
				]
			);
			$table->addColumn(
				'type', 'string', [
					'notnull' => true,
					'length' => 15,
					'default' => 'Unknown'
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
				'uid', 'string', [
					'notnull' => false,
					'length' => 20,
				]
			);
			$table->addColumn(
				'instance', 'string', [
					'notnull' => false,
					'length' => 127,
				]
			);
			$table->addColumn(
				'href', 'string', [
					'notnull' => false,
					'length' => 254,
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


		/**
		 * CIRCLES_EVENT
		 */
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

			$table->addUniqueIndex(['token', 'instance']);
		}


		/**
		 * CIRCLES_MEMBERSHIP
		 */
		if (!$schema->hasTable('circles_membership')) {
			$table = $schema->createTable('circles_membership');

			$table->addColumn(
				'circle_id', 'string', [
					'notnull' => true,
					'length' => 31,
				]
			);
			$table->addColumn(
				'single_id', 'string', [
					'notnull' => true,
					'length' => 31,
				]
			);
			$table->addColumn(
				'level', 'integer', [
					'notnull' => true,
					'length' => 1,
					'unsigned' => true
				]
			);
			$table->addColumn(
				'inheritance_first', 'string', [
					'notnull' => true,
					'length' => 31,
				]
			);
			$table->addColumn(
				'inheritance_last', 'string', [
					'notnull' => true,
					'length' => 31,
				]
			);
			$table->addColumn(
				'inheritance_depth', 'integer', [
					'notnull' => true,
					'length' => 2,
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
			$table->addIndex(
				['inheritance_first', 'inheritance_last', 'circle_id'], 'circles_membership_ifilci'
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


		/**
		 * CIRCLES_MOUNT
		 */
		if (!$schema->hasTable('circles_mount')) {
			$table = $schema->createTable('circles_mount');
			$table->addColumn(
				'id', 'integer', [
					'autoincrement' => true,
					'notnull' => true,
					'length' => 11,
					'unsigned' => true,
				]
			);
			$table->addColumn(
				'mount_id', 'string', [
					'notnull' => false,
					'length' => 31
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
				'token', 'string', [
					'notnull' => false,
					'length' => 63
				]
			);
			$table->addColumn(
				'parent', 'integer', [
					'notnull' => false,
					'length' => 11
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
					'length' => 64
				]
			);

			$table->setPrimaryKey(['id']);
			$table->addIndex(['circle_id', 'mount_id', 'parent', 'token'], 'circles_mount_cimipt');
		}


		/**
		 * CIRCLES_MOUNTPOINT
		 */
		if (!$schema->hasTable('circles_mountpoint')) {
			$table = $schema->createTable('circles_mountpoint');
			$table->addColumn(
				'id', 'integer', [
					'autoincrement' => true,
					'notnull' => true,
					'length' => 11,
					'unsigned' => true,
				]
			);
			$table->addColumn(
				'mount_id', 'string', [
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
				'mountpoint', 'text', [
					'notnull' => false
				]
			);
			$table->addColumn(
				'mountpoint_hash', 'string', [
					'notnull' => false,
					'length' => 64
				]
			);

			$table->setPrimaryKey(['id']);
			$table->addIndex(['mount_id', 'single_id'], 'circles_mountpoint_ms');
		}


		/**
		 * CIRCLES_SHARE_LOCK
		 */
		if (!$schema->hasTable('circles_share_lock')) {
			$table = $schema->createTable('circles_share_lock');
			$table->addColumn(
				'id', 'integer', [
					'autoincrement' => true,
					'notnull' => true,
					'length' => 4,
					'unsigned' => true,
				]
			);
			$table->addColumn(
				'item_id', 'string', [
					'notnull' => true,
					'length' => 31
				]
			);
			$table->addColumn(
				'circle_id', 'string', [
					'notnull' => true,
					'length' => 31
				]
			);
			$table->addColumn(
				'instance', 'string', [
					'notnull' => true,
					'length' => 127,
				]
			);

			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['item_id', 'circle_id']);
		}

		return $schema;
	}
}
