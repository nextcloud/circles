<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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


namespace OCA\Circles\Cron;

use ArtificialOwl\MySmallPhpTools\Model\SimpleDataStore;
use OC\BackgroundJob\TimedJob;
use OCA\Circles\Exceptions\MaintenanceException;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MaintenanceService;

/**
 * Class Maintenance
 *
 * @package OCA\Cicles\Cron
 */
class Maintenance extends TimedJob {


	/** @var MaintenanceService */
	private $maintenanceService;

	/** @var ConfigService */
	private $configService;


	public static $DELAY =
		[
			1 => 60,    // every minute
			2 => 300,   // every 5 minutes
			3 => 3600,  // every hour
			4 => 75400, // every day
			5 => 432000 // evey week
		];

	/**
	 * Cache constructor.
	 */
	public function __construct(
		MaintenanceService $maintenanceService,
		ConfigService $configService
	) {
		$this->setInterval(10);

		$this->maintenanceService = $maintenanceService;
		$this->configService = $configService;
	}


	/**
	 * @param $argument
	 */
	protected function run($argument) {
		$this->runMaintenances();
	}


	/**
	 *
	 */
	private function runMaintenances(): void {
		$last = new SimpleDataStore();
		$last->json($this->configService->getAppValue(ConfigService::MAINTENANCE_UPDATE));

		$last->sInt('maximum', $this->maximumLevelBasedOnTime(($last->gInt('5') === 0)));
		for ($i = 5; $i > 0; $i--) {
			if ($this->canRunLevel($i, $last)) {
				try {
					$this->maintenanceService->runMaintenance($i);
				} catch (MaintenanceException $e) {
					continue;
				}
				$last->sInt((string)$i, time());
			}
		}

		$this->configService->setAppValue(ConfigService::MAINTENANCE_UPDATE, json_encode($last));
	}


	/**
	 * @param bool $force
	 *
	 * @return int
	 */
	private function maximumLevelBasedOnTime(bool $force = false): int {
		$currentHour = (int)date('H');
		$currentDay = (int)date('N');
		$isWeekEnd = ($currentDay >= 6);

		if ($currentHour > 2 && $currentHour < 5 && ($isWeekEnd || $force)) {
			return 5;
		}

		if ($currentHour > 1 && $currentHour < 6) {
			return 4;
		}

		return 3;
	}


	private function canRunLevel(int $level, SimpleDataStore $last): bool {
		if ($last->gInt('maximum') < $level) {
			return false;
		}

		$now = time();
		$timeLastRun = $last->gInt((string)$level);
		if ($timeLastRun === 0) {
			return true;
		}

		return ($timeLastRun + self::$DELAY[$level] < $now);
	}
}
