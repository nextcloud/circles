<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\Attributes\AddIndex;
use OCP\Migration\Attributes\DropColumn;
use OCP\Migration\Attributes\IndexType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

#[AddIndex('circles_team_favorites', IndexType::INDEX, 'restore the circle cleanup index')]
#[DropColumn(table: 'circles_team_favorites', name: 'created')]
class Version360000Date20261016134000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('circles_team_favorites')) {
			return null;
		}

		$table = $schema->getTable('circles_team_favorites');
		if ($table->hasColumn('created')) {
			$table->dropColumn('created');
		}

		$hasCircleIndex = false;
		foreach ($table->getIndexes() as $index) {
			if ($index->getColumns() === ['circle_id']) {
				$hasCircleIndex = true;
				break;
			}
		}
		if (!$hasCircleIndex) {
			$table->addIndex(['circle_id']);
		}

		return $schema;
	}
}
