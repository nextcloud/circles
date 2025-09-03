<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Migration;

use Closure;
use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version3200Date20250115140201 extends SimpleMigrationStep {
	public function __construct(
	) {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$modified = false;

		if (!$schema->hasTable('teams')) {
			$table = $schema->createTable('teams');
			$table->addColumn(
				'id', Types::INTEGER, [
						'autoincrement' => true,
						'notnull' => true,
						'length' => 4,
						'unsigned' => true,
					]
			);
			$table->addColumn(
				'single_id', Types::STRING, [
						 'notnull' => true,
						 'length' => 31,
					 ]
			);
			$table->addColumn(
				'display_name', Types::STRING, [
								  'notnull' => false,
								  'default' => '',
								  'length' => 255
							  ]
			);
			$table->addColumn(
				'sanitized_name', Types::STRING, [
									'notnull' => false,
									'default' => '',
									'length' => 127
								]
			);
			$table->addColumn(
				'config', Types::INTEGER, [
							'notnull' => false,
							'length' => 11,
							'default' => 0,
							'unsigned' => true
						]
			);
			$table->addColumn(
				'settings', Types::TEXT, [
							  'notnull' => false,
							  'default' => '[]',
						  ]
			);
			$table->addColumn(
				'metadata', Types::TEXT, [
						  'notnull' => false,
						  'default' => '[]',
					  ]
			);
			$table->addColumn(
				'creation', Types::INTEGER, [
							  'notnull' => false,
							  'default' => 0,
						  ]
			);

			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['single_id']);
			$table->addIndex(['display_name']);
			$table->addUniqueIndex(['sanitized_name']);
			$table->addIndex(['config']);
			$table->addIndex(['creation']);

			$modified = true;
		}

		if (!$schema->hasTable('teams_entities')) {
			$table = $schema->createTable('teams_entities');
			$table->addColumn(
				'id', Types::INTEGER, [
						'autoincrement' => true,
						'notnull' => true,
						'length' => 4,
						'unsigned' => true,
					]
			);
			$table->addColumn(
				'single_id', Types::STRING, [
							   'notnull' => true,
							   'length' => 31,
						   ]
			);
			$table->addColumn(
				'type', Types::INTEGER, [
						  'notnull' => false,
						  'length' => 4,
						  'default' => 0,
						  'unsigned' => true,
					  ]
			);
			$table->addColumn(
				'orig_id', Types::STRING, [
							 'notnull' => false,
							 'default' => '',
							 'length' => 127,
						 ]
			);
			$table->addColumn(
				'display_name', Types::STRING, [
								  'notnull' => false,
								  'default' => '',
								  'length' => 255
							  ]
			);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['single_id']);
			$table->addUniqueIndex(['type', 'orig_id']);

			$modified = true;
		}

		if (!$schema->hasTable('teams_members')) {
			$table = $schema->createTable('teams_members');
			$table->addColumn(
				'id', Types::INTEGER, [
						'autoincrement' => true,
						'notnull' => true,
						'length' => 4,
						'unsigned' => true,
					]
			);
			$table->addColumn(
				'team_single_id', Types::STRING, [
							  'notnull' => true,
							  'length' => 31,
						  ]
			);
			$table->addColumn(
				'member_single_id', Types::STRING, [
								'notnull' => true,
								'length' => 31,
							]
			);
			$table->addColumn(
				'invited_by_single_id', Types::STRING, [
								  'notnull' => true,
								  'length' => 31,
							  ]
			);
			$table->addColumn(
				'level', Types::INTEGER, [
						   'notnull' => false,
						   'default' => 0,
						   'length' => 2
					   ]
			);
			$table->addColumn(
				'metadata', Types::TEXT, [
						  'notnull' => false,
						  'default' => '[]',
					  ]
			);
			$table->addColumn(
				'creation', Types::INTEGER, [
							  'notnull' => false,
							  'default' => 0,
						  ]
			);

			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['team_single_id', 'member_single_id']);
			$table->addIndex(['level', 'creation']);

			$modified = true;
		}

		if (!$schema->hasTable('teams_memberships')) {
			$table = $schema->createTable('teams_memberships');
			$table->addColumn(
				'id', Types::INTEGER, [
						'autoincrement' => true,
						'notnull' => true,
						'length' => 4,
						'unsigned' => true,
					]
			);
			$table->addColumn(
				'single_id', Types::STRING, [
							   'notnull' => true,
							   'length' => 31,
						   ]
			);
			$table->addColumn(
				'team_single_id', Types::STRING, [
									'notnull' => true,
									'length' => 31,
								]
			);
			//inheritance_path
			$table->addColumn(
				'level', Types::INTEGER, [
						   'notnull' => false,
						   'default' => 0,
						   'length' => 2
					   ]
			);
			$table->addColumn(
				'inheritance_first', Types::STRING, [
									   'notnull' => true,
									   'length' => 31,
									   'default' => '',
								   ]
			);
			$table->addColumn(
				'inheritance_last', Types::STRING, [
									  'notnull' => true,
									  'length' => 31,
									  'default' => '',
								  ]
			);
			$table->addColumn(
				'inheritance_depth', Types::INTEGER, [
									   'notnull' => true,
									   'length' => 2,
									   'unsigned' => true,
									   'default' => 0,
								   ]
			);
			$table->addColumn(
				'inheritance_path', Types::TEXT, [
									  'notnull' => true,
									  'default' => '[]',
								  ]
			);

			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['single_id', 'team_single_id']);
			$table->addUniqueIndex(['inheritance_first', 'inheritance_last', 'team_single_id'], 'teams_mbs_iit');
			$table->addIndex(['single_id']);
			$table->addIndex(['team_single_id']);

			$modified = true;
		}



		if (!$modified) {
			return null;
		}

		return $schema;
	}
}
