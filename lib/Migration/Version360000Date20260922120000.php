<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Schema\SchemaException;
use OCP\Migration\Attributes\DropTable;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

#[DropTable(table: 'circles_share_lock')]
class Version360000Date20260922120000 extends SimpleMigrationStep {
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

		if ($schema->hasTable('circles_share_lock')) {
			$schema->dropTable('circles_share_lock');
		}

		return $schema;
	}
}
