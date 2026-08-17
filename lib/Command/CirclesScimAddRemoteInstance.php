<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Command;

use OC\Core\Command\Base;
use OCA\Circles\ConfigLexicon;
use OCA\Circles\Db\RemoteRequest;
use OCP\AppFramework\Services\IAppConfig;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CirclesScimAddRemoteInstance extends Base {
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly RemoteRequest $remoteRequest,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this->setName('circles:scim:add-remote-instance')
			->setDescription('trust a remote instance to add members on SCIM circles')
			->addArgument('instance', InputArgument::REQUIRED, 'remote instance to add');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$instance = $input->getArgument('instance');

		$knownInstances = array_map(
			fn ($remote) => $remote->getInstance(),
			$this->remoteRequest->getKnownInstances()
		);

		if (!in_array($instance, $knownInstances, true)) {
			$output->writeln("<error>instance not found or not trusted, make sure it's listed under 'occ circles:remote' with a type other than 'Unknown'</error>");

			return 1;
		}

		$instances = $this->appConfig->getAppValueArray(ConfigLexicon::SCIM_REMOTE_INSTANCES);
		if (in_array($instance, $instances, true)) {
			$output->writeln('<comment>instance already added</comment>');

			return 0;
		}

		$instances[] = $instance;
		$this->appConfig->setAppValueArray(ConfigLexicon::SCIM_REMOTE_INSTANCES, $instances);

		$output->writeln('<info>done</info>');

		return 0;
	}
}
