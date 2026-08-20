<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Schema\ColumnType;
use OCP\DB\Schema\SchemaException;
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
				'id', ColumnType::Integer, [
					'autoincrement' => true,
					'notnull' => true,
					'length' => 4,
					'unsigned' => true,
				]
			);
			$table->addColumn(
				'unique_id', ColumnType::String, [
					'notnull' => true,
					'length' => 31,
				]
			);
			$table->addColumn(
				'name', ColumnType::String, [
					'notnull' => true,
					'length' => 127,
				]
			);
			$table->addColumn(
				'display_name', ColumnType::String, [
					'notnull' => false,
					'default' => '',
					'length' => 255
				]
			);
			$table->addColumn(
				'sanitized_name', ColumnType::String, [
					'notnull' => false,
					'default' => '',
					'length' => 127
				]
			);
			$table->addColumn(
				'instance', ColumnType::String, [
					'notnull' => false,
					'default' => '',
					'length' => 255
				]
			);
			$table->addColumn(
				'config', ColumnType::Integer, [
					'notnull' => false,
					'length' => 11,
					'unsigned' => true
				]
			);
			$table->addColumn(
				'source', ColumnType::Integer, [
					'notnull' => false,
					'length' => 5,
					'unsigned' => true
				]
			);
			$table->addColumn(
				'settings', ColumnType::Text, [
					'notnull' => false
				]
			);
			$table->addColumn(
				'description', ColumnType::Text, [
					'notnull' => false
				]
			);
			$table->addColumn(
				'creation', ColumnType::Datetime, [
					'notnull' => false,
				]
			);
			$table->addColumn(
				'contact_addressbook', ColumnType::Integer, [
					'notnull' => false,
					'unsigned' => true,
					'length' => 7,
				]
			);
			$table->addColumn(
				'contact_groupname', ColumnType::String, [
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
				'id', ColumnType::Integer, [
					'autoincrement' => true,
					'notnull' => true,
					'length' => 4,
					'unsigned' => true,
				]
			);
			$table->addColumn(
				'single_id', ColumnType::String, [
					'notnull' => false,
					'length' => 31
				]
			);
			$table->addColumn(
				'circle_id', ColumnType::String, [
					'notnull' => true,
					'length' => 31,
				]
			);
			$table->addColumn(
				'member_id', ColumnType::String, [
					'notnull' => false,
					'length' => 31,
				]
			);
			$table->addColumn(
				'user_id', ColumnType::String, [
					'notnull' => true,
					'length' => 127,
				]
			);
			$table->addColumn(
				'user_type', ColumnType::Smallint, [
					'notnull' => true,
					'length' => 1,
					'default' => 1,
				]
			);
			$table->addColumn(
				'instance', ColumnType::String, [
					'default' => '',
					'length' => 255
				]
			);
			$table->addColumn(
				'invited_by', ColumnType::String, [
					'notnull' => false,
					'length' => 31,
				]
			);
			$table->addColumn(
				'level', ColumnType::Smallint, [
					'notnull' => true,
					'length' => 1,
				]
			);
			$table->addColumn(
				'status', ColumnType::String, [
					'notnull' => false,
					'length' => 15,
				]
			);
			$table->addColumn(
				'note', ColumnType::Text, [
					'notnull' => false
				]
			);
			$table->addColumn(
				'cached_name', ColumnType::String, [
					'notnull' => false,
					'length' => 255,
					'default' => ''
				]
			);
			$table->addColumn(
				'cached_update', ColumnType::Datetime, [
					'notnull' => false,
				]
			);
			$table->addColumn(
				'contact_id', ColumnType::String, [
					'notnull' => false,
					'length' => 127,
				]
			);
			$table->addColumn(
				'contact_meta', ColumnType::Text, [
					'notnull' => false
				]
			);
			$table->addColumn(
				'joined', ColumnType::Datetime, [
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
				'id', ColumnType::Integer, [
					'autoincrement' => true,
					'notnull' => true,
					'length' => 4,
					'unsigned' => true,
				]
			);
			$table->addColumn(
				'type', ColumnType::String, [
					'notnull' => true,
					'length' => 15,
					'default' => 'Unknown'
				]
			);
			$table->addColumn(
				'interface', ColumnType::Integer, [
					'notnull' => true,
					'length' => 1,
					'default' => 0
				]
			);
			$table->addColumn(
				'uid', ColumnType::String, [
					'notnull' => false,
					'length' => 20,
				]
			);
			$table->addColumn(
				'instance', ColumnType::String, [
					'notnull' => false,
					'length' => 127,
				]
			);
			$table->addColumn(
				'href', ColumnType::String, [
					'notnull' => false,
					'length' => 254,
				]
			);
			$table->addColumn(
				'item', ColumnType::Text, [
					'notnull' => false,
				]
			);
			$table->addColumn(
				'creation', ColumnType::Datetime, [
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
				'token', ColumnType::String, [
					'notnull' => false,
					'length' => 63,
				]
			);
			$table->addColumn(
				'event', ColumnType::Text, [
					'notnull' => false
				]
			);
			$table->addColumn(
				'result', ColumnType::Text, [
					'notnull' => false
				]
			);
			$table->addColumn(
				'instance', ColumnType::String, [
					'length' => 255,
					'notnull' => false
				]
			);
			$table->addColumn(
				'interface', ColumnType::Integer, [
					'notnull' => true,
					'length' => 1,
					'default' => 0
				]
			);
			$table->addColumn(
				'severity', ColumnType::Integer, [
					'length' => 3,
					'notnull' => false
				]
			);
			$table->addColumn(
				'retry', ColumnType::Integer, [
					'length' => 3,
					'notnull' => false
				]
			);
			$table->addColumn(
				'status', ColumnType::Integer, [
					'length' => 3,
					'notnull' => false
				]
			);
			$table->addColumn(
				'updated', ColumnType::Datetime, [
					'notnull' => false,
				]
			);
			$table->addColumn(
				'creation', ColumnType::Bigint, [
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
				'circle_id', ColumnType::String, [
					'notnull' => true,
					'length' => 31,
				]
			);
			$table->addColumn(
				'single_id', ColumnType::String, [
					'notnull' => true,
					'length' => 31,
				]
			);
			$table->addColumn(
				'level', ColumnType::Integer, [
					'notnull' => true,
					'length' => 1,
					'unsigned' => true
				]
			);
			$table->addColumn(
				'inheritance_first', ColumnType::String, [
					'notnull' => true,
					'length' => 31,
				]
			);
			$table->addColumn(
				'inheritance_last', ColumnType::String, [
					'notnull' => true,
					'length' => 31,
				]
			);
			$table->addColumn(
				'inheritance_depth', ColumnType::Integer, [
					'notnull' => true,
					'length' => 2,
					'unsigned' => true
				]
			);
			$table->addColumn(
				'inheritance_path', ColumnType::Text, [
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
				'id', ColumnType::Integer, [
					'autoincrement' => true,
					'notnull' => true,
					'length' => 11,
					'unsigned' => true,
				]
			);
			$table->addColumn(
				'share_id', ColumnType::Integer, [
					'notnull' => false,
					'length' => 11
				]
			);
			$table->addColumn(
				'circle_id', ColumnType::String, [
					'notnull' => false,
					'length' => 31
				]
			);
			$table->addColumn(
				'single_id', ColumnType::String, [
					'notnull' => false,
					'length' => 31
				]
			);
			$table->addColumn(
				'member_id', ColumnType::String, [
					'notnull' => false,
					'length' => 31
				]
			);
			$table->addColumn(
				'token', ColumnType::String, [
					'notnull' => false,
					'length' => 31
				]
			);
			$table->addColumn(
				'password', ColumnType::String, [
					'notnull' => false,
					'length' => 31
				]
			);
			$table->addColumn(
				'accepted', ColumnType::Integer, [
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
				'id', ColumnType::Integer, [
					'autoincrement' => true,
					'notnull' => true,
					'length' => 11,
					'unsigned' => true,
				]
			);
			$table->addColumn(
				'mount_id', ColumnType::String, [
					'notnull' => false,
					'length' => 31
				]
			);
			$table->addColumn(
				'circle_id', ColumnType::String, [
					'notnull' => false,
					'length' => 31
				]
			);
			$table->addColumn(
				'single_id', ColumnType::String, [
					'notnull' => false,
					'length' => 31
				]
			);
			$table->addColumn(
				'token', ColumnType::String, [
					'notnull' => false,
					'length' => 63
				]
			);
			$table->addColumn(
				'parent', ColumnType::Integer, [
					'notnull' => false,
					'length' => 11
				]
			);
			$table->addColumn(
				'mountpoint', ColumnType::Text, [
					'notnull' => false
				]
			);
			$table->addColumn(
				'mountpoint_hash', ColumnType::String, [
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
				'id', ColumnType::Integer, [
					'autoincrement' => true,
					'notnull' => true,
					'length' => 11,
					'unsigned' => true,
				]
			);
			$table->addColumn(
				'mount_id', ColumnType::String, [
					'notnull' => false,
					'length' => 31
				]
			);
			$table->addColumn(
				'single_id', ColumnType::String, [
					'notnull' => false,
					'length' => 31
				]
			);
			$table->addColumn(
				'mountpoint', ColumnType::Text, [
					'notnull' => false
				]
			);
			$table->addColumn(
				'mountpoint_hash', ColumnType::String, [
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
				'id', ColumnType::Integer, [
					'autoincrement' => true,
					'notnull' => true,
					'length' => 4,
					'unsigned' => true,
				]
			);
			$table->addColumn(
				'item_id', ColumnType::String, [
					'notnull' => true,
					'length' => 31
				]
			);
			$table->addColumn(
				'circle_id', ColumnType::String, [
					'notnull' => true,
					'length' => 31
				]
			);
			$table->addColumn(
				'instance', ColumnType::String, [
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
