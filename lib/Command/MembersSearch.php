<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
		$table->render();

		foreach ($result as $entry) {
			if (!$result instanceof IFederatedUser) {
				continue;
			}
			$table->appendRow(
				[
					$entry->getSingleId(),
					$entry->getUserId(),
					Member::$TYPE[$entry->getUserType()],
					$this->configService->displayInstance($entry->getInstance())
				]
			);
		}
	}
}
