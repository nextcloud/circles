<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Command;

use OC\Core\Command\Base;
use OCA\Circles\ConfigLexicon;
use OCA\Circles\Service\ScimService;
use OCP\AppFramework\Services\IAppConfig;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CirclesScimRemoveRemoteInstance extends Base {
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly ScimService $scimService,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this->setName('circles:scim:remove-remote-instance')
			->setDescription('stop trusting a remote instance, removing members it added on SCIM circles')
			->addArgument('instance', InputArgument::REQUIRED, 'remote instance to remove');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$instance = $input->getArgument('instance');

		$instances = $this->appConfig->getAppValueArray(ConfigLexicon::SCIM_REMOTE_INSTANCES);

		$key = array_search($instance, $instances, true);
		if ($key === false) {
			$output->writeln("<comment>instance not found, run 'occ config:app:get circles scim_remote_instances' to see current entries</comment>");

			return 0;
		}

		unset($instances[$key]);
		$this->appConfig->setAppValueArray(ConfigLexicon::SCIM_REMOTE_INSTANCES, array_values($instances));

		$this->scimService->removeRemoteInstanceMembers($instance);

		$output->writeln('<info>done</info>');

		return 0;
	}
}
