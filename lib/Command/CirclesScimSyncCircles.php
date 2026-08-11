<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Command;

use OC\Core\Command\Base;
use OCA\Circles\Service\ScimService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CirclesScimSyncCircles extends Base {
	public function __construct(
		private readonly ScimService $scimService,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this->setName('circles:scim:sync-circles')
			->setDescription('sync circles from SCIM server');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->scimService->syncCircles();

		$output->writeln('<info>done</info>');

		return 0;
	}
}
