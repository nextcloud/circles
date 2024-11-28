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
use OCP\Migration\Attributes\AddIndex;
use OCP\Migration\Attributes\IndexType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Psr\Log\LoggerInterface;

#[AddIndex('circles_mountpoint', IndexType::UNIQUE, 'add uniqueness to mountpoint per user')]
class Version0031Date20241105133904 extends SimpleMigrationStep {
	public function __construct(
		private LoggerInterface $logger,
	) {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		try {
			$table = $schema->getTable('circles_mountpoint');
			if (!$table->hasIndex('mp_sid_hash')) {
				$table->addUniqueIndex(['single_id', 'mountpoint_hash'], 'mp_sid_hash');
			}
		} catch (SchemaException $e) {
			$this->logger->warning('Could not find circles_mountpoint', ['exception' => $e]);
		}

		return $schema;
	}
}
