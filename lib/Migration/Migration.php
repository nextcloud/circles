<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Migration;

use Exception;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MigrationService;
use OCA\Circles\Service\OutputService;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class Migration
 *
 * @package OCA\Circles\Migration
 */
class Migration implements IRepairStep {
	use TNCLogger;


	/** @var MigrationService */
	private $migrationService;

	private $outputService;

	/** @var ConfigService */
	private $configService;


	/**
	 * Migration constructor.
	 *
	 * @param MigrationService $migrationService
	 * @param OutputService $outputService
	 * @param ConfigService $configService
	 */
	public function __construct(
		MigrationService $migrationService,
		OutputService $outputService,
		ConfigService $configService,
	) {
		$this->migrationService = $migrationService;
		$this->outputService = $outputService;
		$this->configService = $configService;
	}


	/**
	 * @return string
	 */
	public function getName(): string {
		return 'Upgrading Circles App';
	}


	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {
		if ($this->configService->getAppValueBool(ConfigService::MIGRATION_BYPASS)) {
			return;
		}

		$this->outputService->setMigrationOutput($output);

		try {
			$this->migrationService->migration();
		} catch (Exception $e) {
			$this->e($e);
		}
	}
}
