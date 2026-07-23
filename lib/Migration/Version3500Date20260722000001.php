<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Remove the obsolete mirrored Groupfolders link.
 *
 * Groupfolders' unique `group_folders.team_circle_id` is the authoritative
 * relationship. Keeping a second identifier in Circles required cross-app
 * database writes and could become inconsistent.
 */
class Version3500Date20260722000001 extends SimpleMigrationStep {
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$table = $schema->getTable('circles_circle');

		if ($table->hasIndex('circles_team_folder')) {
			$table->dropIndex('circles_team_folder');
		}
		if ($table->hasColumn('team_folder_id')) {
			$table->dropColumn('team_folder_id');
		}

		return $schema;
	}
}
