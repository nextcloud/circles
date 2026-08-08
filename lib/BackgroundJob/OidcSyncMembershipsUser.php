<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\BackgroundJob;

use OCA\Circles\ConfigLexicon;
use OCA\Circles\Service\OidcService;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;

class OidcSyncMembershipsUser extends QueuedJob {
	public function __construct(
		ITimeFactory $time,
		private readonly IAppConfig $appConfig,
		private readonly OidcService $oidcService,
	) {
		parent::__construct($time);
	}

	protected function run($argument) {
		if (!$this->appConfig->getAppValueBool(ConfigLexicon::OIDC_ENABLED)) {
			return;
		}

		$userId = $argument['userId'] ?? null;
		if ($userId === null) {
			return;
		}

		$this->oidcService->syncMembershipsForUser($userId);
	}
}
