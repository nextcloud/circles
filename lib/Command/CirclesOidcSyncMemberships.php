<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Command;

use OC\Core\Command\Base;
use OCA\Circles\Service\OidcService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CirclesOidcSyncMemberships extends Base {
	public function __construct(
		private readonly OidcService $oidcService,
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();
		$this->setName('circles:oidc:sync-memberships')
			->setDescription('sync circle memberships from OIDC server');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->oidcService->syncMemberships();

		$output->writeln('<info>done</info>');

		return 0;
	}
}
