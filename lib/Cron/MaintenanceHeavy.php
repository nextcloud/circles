<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
