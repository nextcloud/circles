<?php declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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
use OCA\Circles\Db\CirclesRequest;
use OCA\Circles\Exceptions\ConfigNoCircleAvailableException;
use OCA\Circles\Model\Circle;
use OCP\IL10N;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class CirclesList
 *
 * @package OCA\Circles\Command
 */
class CirclesList extends Base {


	/** @var IL10N */
	private $l10n;

	/** @var CirclesRequest */
	private $circlesRequest;


	/**
	 * CirclesList constructor.
	 *
	 * @param IL10N $l10n
	 * @param CirclesRequest $circlesRequest
	 */
	public function __construct(IL10N $l10n, CirclesRequest $circlesRequest) {
		parent::__construct();
		$this->l10n = $l10n;
		$this->circlesRequest = $circlesRequest;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:manage:list')
			 ->setDescription('listing current circles')
			 ->addArgument('owner', InputArgument::OPTIONAL, 'filter by owner', '')
			 ->addOption('viewer', '', InputOption::VALUE_REQUIRED, 'set viewer', '')
			 ->addOption('json', '', InputOption::VALUE_NONE, 'returns result as JSON');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws ConfigNoCircleAvailableException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$owner = $input->getArgument('owner');
		$viewer = $input->getOption('viewer');
		$json = $input->getOption('json');

		$output = new ConsoleOutput();
		$output = $output->section();
		$circles = $this->getCircles($owner, $viewer);

		if ($json) {
			echo json_encode($circles, JSON_PRETTY_PRINT) . "\n";

			return 0;
		}

		$table = new Table($output);
		$table->setHeaders(['ID', 'Name', 'Type', 'Owner']);
		$table->render();
		$output->writeln('');

		$c = 0;
		foreach ($circles as $circle) {
			$table->appendRow(
				[
					$circle->getUniqueId(),
					$circle->getName(),
					$circle->getTypeLongString(),
					$circle->getOwner()
						   ->getUserId()
				]
			);
		}

		return 0;
	}


	/**
	 * @param string $owner
	 * @param string $viewer
	 *
	 * @return Circle[]
	 * @throws ConfigNoCircleAvailableException
	 */
	private function getCircles(string $owner, string $viewer): array {
		if ($viewer === '') {
			$circles = $this->circlesRequest->forceGetCircles($owner);
		} else {
			$circles = $this->circlesRequest->getCircles($viewer, 0, '', 0, true, $owner);
		}

		return $circles;
	}

}

