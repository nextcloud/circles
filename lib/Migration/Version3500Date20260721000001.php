<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Migration;

use Closure;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Psr\Log\LoggerInterface;

/**
 * Removes legacy team-folder metadata from Circles. Groupfolders owns the
 * relationship through its `team_circle_id` column, so Circles must not
 * maintain a second copy.
 */
class Version3500Date20260721000001 extends SimpleMigrationStep {
	private const CFG_TEAM_FOLDER_LEGACY = 262144;

	public function __construct(
		private readonly IDBConnection $connection,
		private readonly LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$query = $this->connection->getQueryBuilder();
		$query->select('unique_id', 'settings', 'config')
			->from('circles_circle');
		$result = $query->executeQuery();

		$migrated = 0;
		while (($row = $result->fetch()) !== false) {
			$uniqueId = (string)$row['unique_id'];
			$rawSettings = $row['settings'] ?? null;
			$config = (int)($row['config'] ?? 0);

			$settings = [];
			if ($rawSettings !== null && $rawSettings !== '') {
				try {
					$decoded = json_decode($rawSettings, true, 512, JSON_THROW_ON_ERROR);
					if (is_array($decoded)) {
						$settings = $decoded;
					}
				} catch (\JsonException $e) {
					$this->logger->warning(
						'Could not decode circles_circle settings during team_folder_id backfill',
						['unique_id' => $uniqueId, 'exception' => $e],
					);
					continue;
				}
			}

			$hadEssentialKey = array_key_exists('team_folder_essential', $settings)
				|| array_key_exists('team_folder_id', $settings);

			if (!$hadEssentialKey && ($config & self::CFG_TEAM_FOLDER_LEGACY) === 0) {
				continue;
			}

			unset($settings['team_folder_id'], $settings['team_folder_essential']);
			$newConfig = $config & ~self::CFG_TEAM_FOLDER_LEGACY;

			$update = $this->connection->getQueryBuilder();
			$update->update('circles_circle')
				->set('settings', $update->createNamedParameter(json_encode($settings, JSON_THROW_ON_ERROR)))
				->set('config', $update->createNamedParameter($newConfig, IQueryBuilder::PARAM_INT))
				->where($update->expr()->eq('unique_id', $update->createNamedParameter($uniqueId)));
			$update->executeStatement();
			$migrated++;
		}
		$result->closeCursor();

		if ($migrated > 0) {
			$this->logger->info('Removed legacy Circles team folder metadata', [
				'migrated' => $migrated,
			]);
		}
	}
}
