<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Command;

use OC\Core\Command\Base;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SearchResult;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\SearchService;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MembersSearch
 *
 * @package OCA\Circles\Command
 */
class MembersSearch extends Base {
	/** @var SearchService */
	private $searchService;

	/** @var ConfigService */
	private $configService;


	/**
	 * MembersSearch constructor.
	 *
	 * @param SearchService $searchService
	 * @param ConfigService $configService
	 */
	public function __construct(SearchService $searchService, ConfigService $configService) {
		parent::__construct();
		$this->searchService = $searchService;
		$this->configService = $configService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:members:search')
			->setDescription('Change the level of a member from a Circle')
			->addArgument('term', InputArgument::REQUIRED, 'term to search')
			->addOption('initiator', '', InputOption::VALUE_REQUIRED, 'set an initiator to the request', '')
			->addOption('status-code', '', InputOption::VALUE_NONE, 'display status code on exception');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$result = $this->searchService->search($input->getArgument('needle'));

		if (strtolower($input->getOption('output')) === 'json') {
			$output->writeln(json_encode($result, JSON_PRETTY_PRINT));
		}

		$this->displaySearchResult($result);

		return 0;
	}


	/**
	 * @param list<IFederatedUser|SearchResult> $result
	 */
	private function displaySearchResult(array $result) {
		$output = new ConsoleOutput();
		$output = $output->section();
		$table = new Table($output);
		$table->setHeaders(['SingleId', 'UserId', 'UserType', 'Instance']);

		$rows = [];
		foreach ($result as $entry) {
			if (!$result instanceof IFederatedUser) {
				continue;
			}
			$rows[] = [
				$entry->getSingleId(),
				$entry->getUserId(),
				Member::$TYPE[$entry->getUserType()],
				$this->configService->displayInstance($entry->getInstance())
			];
		}

		$table->setRows($rows);
		$table->render();
	}
}
