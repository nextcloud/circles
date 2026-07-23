<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\BackgroundJob;

use Exception;
use OCA\Circles\Service\OidcService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use Psr\Log\LoggerInterface;

class OidcSyncUser extends QueuedJob {
	public function __construct(
		ITimeFactory $time,
		private readonly OidcService $oidcService,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($time);
	}

	protected function run($argument) {
		$userId = $argument['userId'] ?? null;
		if ($userId === null) {
			return;
		}

		try {
			$this->oidcService->syncMembershipsForUser($userId);
		} catch (Exception $e) {
			$this->logger->warning('could not sync OIDC memberships on login', ['userId' => $userId, 'exception' => $e]);
		}
	}
}
