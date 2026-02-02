<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Circles\Service;

use OCA\Federation\TrustedServers;
use Psr\Log\LoggerInterface;

/**
 * @psalm-suppress UndefinedClass
 */
class TrustedServerService {
	public function __construct(
		private readonly TrustedServers $trustedServers,
		private readonly LoggerInterface $logger,
	) {
	}

	/**
	 * @return list<array{id: int, url: string, url_hash: string, shared_secret: ?string, status: int, sync_token: ?string, address: string}>
	 */
	public function getTrustedServers(): array {
		$trustedServers = [];
		try {
			foreach ($this->trustedServers->getServers() as $server) {
				if (($server['status'] ?? 0) === TrustedServers::STATUS_OK &&
					str_starts_with($server['url'], 'https://')) {
					$server['address'] = substr($server['url'], 8);
					$trustedServers[] = $server;
				}
			}
		} catch (\Exception $e) {
			$this->logger->warning('Could not get trusted servers', ['exception' => $e]);
		}

		return $trustedServers;
	}
}
