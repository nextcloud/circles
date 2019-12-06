<?php

declare(strict_types=1);

namespace OCA\Circles\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version0017Date20191205153154 extends SimpleMigrationStep {

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
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('circles_circles')) {
			$table = $schema->createTable('circles_circles');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
				'unsigned' => true,
			]);
			$table->addColumn('unique_id', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('name', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('description', 'string', [
				'notnull' => false,
				'length' => 4000,
			]);
			$table->addColumn('settings', 'string', [
				'notnull' => false,
				'length' => 4000,
			]);
			$table->addColumn('type', 'smallint', [
				'notnull' => true,
				'length' => 2,
			]);
			$table->addColumn('creation', 'datetime', [
				'notnull' => false,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('circles_members')) {
			$table = $schema->createTable('circles_members');
			$table->addColumn('circle_id', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('user_id', 'string', [
				'notnull' => true,
				'length' => 128,
			]);
			$table->addColumn('user_type', 'smallint', [
				'notnull' => true,
				'length' => 1,
				'default' => 1,
			]);
			$table->addColumn('level', 'smallint', [
				'notnull' => true,
				'length' => 1,
			]);
			$table->addColumn('status', 'string', [
				'notnull' => false,
				'length' => 15,
			]);
			$table->addColumn('note', 'string', [
				'notnull' => false,
				'length' => 255,
			]);
			$table->addColumn('joined', 'datetime', [
				'notnull' => false,
			]);
			$table->setPrimaryKey(['circle_id', 'user_id']);
		}

		if (!$schema->hasTable('circles_groups')) {
			$table = $schema->createTable('circles_groups');
			$table->addColumn('circle_id', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('group_id', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('level', 'smallint', [
				'notnull' => true,
				'length' => 1,
			]);
			$table->addColumn('note', 'string', [
				'notnull' => false,
				'length' => 255,
			]);
			$table->addColumn('joined', 'datetime', [
				'notnull' => false,
			]);
			$table->setPrimaryKey(['circle_id', 'group_id']);
		}

		if (!$schema->hasTable('circles_clouds')) {
			$table = $schema->createTable('circles_clouds');
			$table->addColumn('cloud_id', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('address', 'string', [
				'notnull' => true,
				'length' => 128,
			]);
			$table->addColumn('status', 'smallint', [
				'notnull' => true,
				'length' => 1,
			]);
			$table->addColumn('note', 'string', [
				'notnull' => false,
				'length' => 255,
			]);
			$table->addColumn('created', 'datetime', [
				'notnull' => false,
			]);
			$table->setPrimaryKey(['cloud_id']);
		}

		if (!$schema->hasTable('circles_shares')) {
			$table = $schema->createTable('circles_shares');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
				'unsigned' => true,
			]);
			$table->addColumn('unique_id', 'string', [
				'notnull' => false,
				'length' => 32,
			]);
			$table->addColumn('circle_id', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('source', 'string', [
				'notnull' => true,
				'length' => 15,
			]);
			$table->addColumn('type', 'string', [
				'notnull' => true,
				'length' => 15,
			]);
			$table->addColumn('author', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('cloud_id', 'string', [
				'notnull' => false,
				'length' => 128,
				'default' => 'null',
			]);
			$table->addColumn('headers', 'string', [
				'notnull' => false,
				'length' => 4000,
			]);
			$table->addColumn('payload', 'string', [
				'notnull' => true,
				'length' => 4000,
			]);
			$table->addColumn('creation', 'datetime', [
				'notnull' => false,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('circles_links')) {
			$table = $schema->createTable('circles_links');
			$table->addColumn('id', 'smallint', [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 3,
				'unsigned' => true,
			]);
			$table->addColumn('status', 'smallint', [
				'notnull' => true,
				'length' => 1,
			]);
			$table->addColumn('circle_id', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('unique_id', 'string', [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('address', 'string', [
				'notnull' => true,
				'length' => 128,
			]);
			$table->addColumn('token', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('key', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('creation', 'datetime', [
				'notnull' => false,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('circles_tokens')) {
			$table = $schema->createTable('circles_tokens');
			$table->addColumn('circle_id', 'string', [
				'notnull' => true,
				'length' => 31,
			]);
			$table->addColumn('user_id', 'string', [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('share_id', 'bigint', [
				'notnull' => true,
				'length' => 14,
			]);
			$table->addColumn('token', 'string', [
				'notnull' => true,
				'length' => 31,
			]);
			$table->addColumn('password', 'string', [
				'notnull' => true,
				'length' => 127,
			]);
			$table->setPrimaryKey(['circle_id', 'user_id', 'share_id']);
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
