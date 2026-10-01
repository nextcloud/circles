<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\Attributes\ModifyColumn;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use OCP\Share\IShare;
use Override;

#[ModifyColumn(table: 'circles_token', name: 'share_id')]
#[ModifyColumn(table: 'circles_token', name: 'circle_id')]
#[ModifyColumn(table: 'circles_token', name: 'single_id')]
#[ModifyColumn(table: 'circles_token', name: 'member_id')]
#[ModifyColumn(table: 'circles_token', name: 'token')]
#[ModifyColumn(table: 'circles_token', name: 'password')]
#[ModifyColumn(table: 'circles_token', name: 'accepted')]
class Version360000Date20260922130000 extends SimpleMigrationStep {
	private const DEFAULTS = [
		'share_id' => 0,
		'circle_id' => '',
		'single_id' => '',
		'member_id' => '',
		'token' => '',
		'password' => '',
		'accepted' => IShare::STATUS_PENDING,
	];

	public function __construct(
		private readonly IDBConnection $connection,
	) {
	}

	/**
	 * Backfill any existing NULL values before the NOT NULL constraint is added in
	 * changeSchema(), since ALTER COLUMN ... NOT NULL does not do this on its own.
	 */
	#[Override]
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		if (!$this->connection->tableExists('circles_token')) {
			return;
		}

		foreach (self::DEFAULTS as $column => $default) {
			$qb = $this->connection->getQueryBuilder();
			$qb->update('circles_token')
				->set($column, $qb->createNamedParameter($default))
				->where($qb->expr()->isNull($column));

			$qb->executeStatement();
		}
	}

	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('circles_token')) {
			return null;
		}

		$table = $schema->getTable('circles_token');
		foreach (self::DEFAULTS as $column => $default) {
			$table->modifyColumn($column, [
				'notnull' => true,
				'default' => $default,
			]);
		}

		return $schema;
	}
}
