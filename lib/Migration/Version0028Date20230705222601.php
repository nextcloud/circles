<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Migration;

use Closure;
use Doctrine\DBAL\Schema\SchemaException;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Psr\Log\LoggerInterface;

class Version0028Date20230705222601 extends SimpleMigrationStep {
	public function __construct(
		private LoggerInterface $logger,
	) {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		try {
			$table = $schema->getTable('circles_circle');
			if (!$table->hasIndex('dname')) {
				$table->addIndex(['display_name'], 'dname');
			}
		} catch (SchemaException $e) {
			$this->logger->warning('Could not find circles_circle', ['exception' => $e]);
		}

		return $schema;
	}
}
