<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\BackgroundJob;

use OCA\Circles\Service\RemoteModCircleService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;

class RemoteModSync extends QueuedJob {
	public function __construct(
		ITimeFactory $time,
		private readonly RemoteModCircleService $remoteModCircleService,
	) {
		parent::__construct($time);

		// only run one instance of this job at a time
		$this->setAllowParallelRuns(false);
	}

	protected function run($argument) {
		$this->remoteModCircleService->syncModeratorCircles();
	}
}
