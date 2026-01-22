<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Migration;

use OCA\Circles\BackgroundJob\SyncGroupCirclesJob;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class SyncGroupCircles
 *
 * @package OCA\Circles\Migration
 */
class SyncGroupCircles implements IRepairStep {

	public function __construct(
		private IJobList $jobList,
	) {
	}

	public function getName(): string {
		return 'Sync groups with their circles';
	}

	public function run(IOutput $output): void {
		$this->jobList->add(SyncGroupCirclesJob::class);
	}
}
