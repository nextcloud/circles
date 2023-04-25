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
use OCA\Circles\Db\CoreRequestBuilder;
use OCA\Circles\Exceptions\MaintenanceException;
use OCA\Circles\Service\MaintenanceService;
use OCA\Circles\Service\OutputService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class CirclesMaintenance
 *
 * @package OCA\Circles\Command
 */
class CirclesMaintenance extends Base {
	/** @var CoreRequestBuilder */
	private $coreRequestBuilder;

	/** @var MaintenanceService */
	private $maintenanceService;

	/** @var OutputService */
	private $outputService;

	/**
	 * CirclesMaintenance constructor.
	 *
	 * @param CoreRequestBuilder $coreRequestBuilder
	 * @param MaintenanceService $maintenanceService
	 * @param OutputService $outputService
	 */
	public function __construct(
		CoreRequestBuilder $coreRequestBuilder,
		MaintenanceService $maintenanceService,
		OutputService $outputService
	) {
		parent::__construct();

		$this->coreRequestBuilder = $coreRequestBuilder;
		$this->maintenanceService = $maintenanceService;
		$this->outputService = $outputService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:maintenance')
			 ->setDescription('Clean stuff, keeps the app running')
			 ->addOption('level', '', InputOption::VALUE_REQUIRED, 'level of maintenance', '3')
			 ->addOption(
			 	'reset', '', InputOption::VALUE_NONE, 'reset Circles; remove all data related to the App'
			 )
			 ->addOption(
			 	'clean-shares', '', InputOption::VALUE_NONE, 'remove Circles\' shares'
			 )
			 ->addOption(
			 	'uninstall', '', InputOption::VALUE_NONE,
			 	'Uninstall the apps and everything related to the app from the database'
			 )
			->addOption('force-refresh', '', InputOption::VALUE_NONE, 'enforce some refresh');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$reset = $input->getOption('reset');
		$uninstall = $input->getOption('uninstall');
		$level = (int)$input->getOption('level');

		if ($reset || $uninstall) {
			$action = $uninstall ? 'uninstall' : 'reset';

			$output->writeln('');
			$output->writeln('');
			$output->writeln(
				'<error>WARNING! You are about to delete all data related to the Circles App!</error>'
			);
			$question = new ConfirmationQuestion(
				'<comment>Do you really want to ' . $action . ' Circles ?</comment> (y/N) ', false,
				'/^(y|Y)/i'
			);

			$helper = $this->getHelper('question');
			if (!$helper->ask($input, $output, $question)) {
				$output->writeln('aborted.');

				return 0;
			}

			$output->writeln('');
			$output->writeln('<error>WARNING! This operation is not reversible.</error>');

			$question = new Question(
				'<comment>Please confirm this destructive operation by typing \'' . $action
				. '\'</comment>: ', ''
			);

			$helper = $this->getHelper('question');
			$confirmation = $helper->ask($input, $output, $question);
			if (strtolower($confirmation) !== $action) {
				$output->writeln('aborted.');

				return 0;
			}

			$this->coreRequestBuilder->cleanDatabase($input->getOption('clean-shares'));
			if ($uninstall) {
				$this->coreRequestBuilder->uninstall();
			}

			$output->writeln('<info>' . $action . ' done</info>');

			return 0;
		}

		$this->outputService->setOccOutput($output);
		$this->maintenanceService->setOccOutput($output);

		for ($i = 1; $i <= $level; $i++) {
			try {
				$this->maintenanceService->runMaintenance($i, $input->getOption('force-refresh'));
			} catch (MaintenanceException $e) {
			}
		}

		$output->writeln('');
		$output->writeln('<info>done</info>');

		return 0;
	}
}
