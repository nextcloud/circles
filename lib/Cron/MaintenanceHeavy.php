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

use OCA\Circles\Service\MaintenanceService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;

class MaintenanceHeavy extends TimedJob {
	private MaintenanceService $maintenanceService;


	/**
	 * @param ITimeFactory $time
	 * @param MaintenanceService $maintenanceService
	 */
	public function __construct(ITimeFactory $time, MaintenanceService $maintenanceService) {
		parent::__construct($time);

		$this->setInterval(24 * 3600);
		$this->setTimeSensitivity(IJob::TIME_INSENSITIVE);

		$this->maintenanceService = $maintenanceService;
	}


	/**
	 * @param $argument
	 */
	protected function run($argument) {
		$this->maintenanceService->runMaintenances(true);
	}
}
