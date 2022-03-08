<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Circles\Migration;

use OCA\Circles\Tools\Traits\TNCLogger;
use Exception;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MigrationService;
use OCA\Circles\Service\OutputService;
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
		ConfigService $configService
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
