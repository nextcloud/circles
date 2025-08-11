<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Migration;

use Closure;
use Doctrine\DBAL\Schema\SchemaException;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Psr\Log\LoggerInterface;

#[AddColumn('circles_mount', 'remote', ColumnType::STRING, 'store remote instance for quicker identification')]
#[AddColumn('circles_mount', 'remote_id', ColumnType::INTEGER, 'store remote share id for quicker identification')]
class Version0032Date20250623120204 extends SimpleMigrationStep {
	public function __construct(
		private LoggerInterface $logger,
	) {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		try {
			$table = $schema->getTable('circles_mount');
			if (!$table->hasColumn('remote')) {
				$table->addColumn(
					'remote', 'string',
					[
						'length' => 255,
						'notnull' => false,
						'default' => '',
					]
				);
			}
			if (!$table->hasColumn('remote_id')) {
				$table->addColumn(
					'remote_id', 'integer',
					[
						'length' => 20,
						'unsigned' => true,
						'notnull' => true,
						'default' => 0,
					]
				);
			}

			if (!$table->hasIndex('m_sid_rmt_rid')) {
				$table->addIndex(['circle_id', 'remote', 'remote_id'], 'm_sid_rmt_rid');
			}
		} catch (SchemaException $e) {
			$this->logger->warning('Could not find circles_mount', ['exception' => $e]);
			return null;
		}

		return $schema;
	}
}
