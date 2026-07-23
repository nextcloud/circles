<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Command;

use OC\Core\Command\Base;
use OCA\Circles\Service\RemoteModCircleService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CirclesRemoteModSync extends Base {
	public function __construct(
		private readonly RemoteModCircleService $remoteModCircleService,
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();
		$this->setName('circles:remotemod:sync')
			->setDescription('ensure every discovered remote moderator circle is a member of every third-party circle');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->remoteModCircleService->syncModeratorCircles();

		$output->writeln('<info>done</info>');

		return 0;
	}
}
