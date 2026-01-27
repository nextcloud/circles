<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\BackgroundJob;

use OCA\Circles\Service\SyncService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;

class SyncGroupCirclesJob extends QueuedJob {

	public function __construct(
		ITimeFactory $time,
		private SyncService $syncService,
	) {
		parent::__construct($time);
	}

	public function run($argument) {
		$this->syncService->syncNextcloudGroups();
	}
}
