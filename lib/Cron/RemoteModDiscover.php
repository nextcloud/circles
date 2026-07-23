<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Cron;

use OCA\Circles\BackgroundJob\RemoteModSync;
use OCA\Circles\ConfigLexicon;
use OCA\Circles\Service\RemoteModCircleService;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;

class RemoteModDiscover extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private readonly IAppConfig $appConfig,
		private readonly RemoteModCircleService $remoteModCircleService,
		private readonly IJobList $jobList,
	) {
		parent::__construct($time);

		// run twice a day
		$this->setInterval(12 * 3600);
		// delay until low-load time
		$this->setTimeSensitivity(IJob::TIME_INSENSITIVE);
		// only run one instance of this job at a time
		$this->setAllowParallelRuns(false);
	}

	protected function run($argument) {
		$remoteModCircleInstances = $this->appConfig->getAppValueArray(ConfigLexicon::REMOTE_MOD_CIRCLE_INSTANCES);
		if ($remoteModCircleInstances === []) {
			return;
		}

		$this->remoteModCircleService->discoverModeratorCircles();

		// once discovery is done, run RemoteModSync right after
		$this->jobList->scheduleAfter(RemoteModSync::class, time() + 60);
	}
}
