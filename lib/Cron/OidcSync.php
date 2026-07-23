<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Cron;

use OCA\Circles\ConfigLexicon;
use OCA\Circles\Service\OidcService;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;

class OidcSync extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private readonly IAppConfig $appConfig,
		private readonly OidcService $oidcService,
	) {
		parent::__construct($time);

		// run once a day
		$this->setInterval(24 * 3600);
		// delay until low-load time
		$this->setTimeSensitivity(IJob::TIME_INSENSITIVE);
		// only run one instance of this job at a time
		$this->setAllowParallelRuns(false);
	}

	protected function run($argument) {
		if (!$this->appConfig->getAppValueBool(ConfigLexicon::OIDC_ENABLED)) {
			return;
		}

		$this->oidcService->syncMemberships();
	}
}
