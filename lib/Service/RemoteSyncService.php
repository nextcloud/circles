<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Circles\Service;

use Exception;
use OCA\Circles\Db\RemoteRequest;
use OCA\Circles\Model\Federated\RemoteInstance;
use Psr\Log\LoggerInterface;

class RemoteSyncService {

	public function __construct(
		private ConfigService $configService,
		private RemoteRequest $remoteRequest,
		private TrustedServerService $trustedServerService,
		private RemoteStreamService $remoteStreamService,
		private LoggerInterface $logger,
	) {
	}

	public function syncTrustedServers(): void {
		if (!$this->configService->isFederatedTeamsEnabled()) {
			return;
		}

		$known = array_map(
			static function (RemoteInstance $instance): string {
				return $instance->getInstance();
			}, $this->remoteRequest->getFromType(RemoteInstance::TYPE_EXTERNAL)
		);

		foreach ($this->trustedServerService->getTrustedServers() as $trusted) {
			if (in_array($trusted['address'], $known, true)) {
				continue;
			}

			$this->syncRemoteInstance(
				$trusted['address'],
				RemoteInstance::TYPE_EXTERNAL,
				InterfaceService::IFACE_FRONTAL
			);
		}
	}

	public function syncRemoteInstance(string $instance, string $type, int $iface): bool {
		if ($this->configService->isLocalInstance($instance)) {
			return false;
		}

		try {
			$this->remoteStreamService->addRemoteInstance(
				$instance,
				$type,
				$iface,
				true
			);
		} catch (Exception $e) {
			$this->logger->warning('Could not sync remote instance ' . $instance, ['instance' => $instance, 'type' => $type, 'iface' => $iface, 'exception' => $e]);
			return false;
		}

		return true;
	}

}
