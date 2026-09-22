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
#[DropTable(table: 'circle_gsshares')]
#[DropTable(table: 'circle_gsshares_mp')]
#[DropTable(table: 'circle_groups')]
#[DropTable(table: 'circle_shares')]
#[DropTable(table: 'circle_links')]
#[DropTable(table: 'circle_remotes')]
#[DropTable(table: 'circle_circles')]
#[DropTable(table: 'circle_members')]
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

		if ($schema->hasTable('circle_gsshares')) {
			$schema->dropTable('circle_gsshares');
		}

		if ($schema->hasTable('circle_gsshares_mp')) {
			$schema->dropTable('circle_gsshares_mp');
		}

		if ($schema->hasTable('circle_groups')) {
			$schema->dropTable('circle_groups');
		}

		if ($schema->hasTable('circle_shares')) {
			$schema->dropTable('circle_shares');
		}

		if ($schema->hasTable('circle_links')) {
			$schema->dropTable('circle_links');
		}

		if ($schema->hasTable('circle_remotes')) {
			$schema->dropTable('circle_remotes');
		}

		if ($schema->hasTable('circle_circles')) {
			$schema->dropTable('circle_circles');
		}

		if ($schema->hasTable('circle_members')) {
			$schema->dropTable('circle_members');
		}

		return $schema;
	}
}
