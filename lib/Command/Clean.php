<?php
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
use OCA\Circles\Db\CoreRequestBuilder;
use OCA\Circles\Service\CleanService;
use OCA\Circles\Service\ConfigService;
use OCP\IDBConnection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class Clean extends Base {


	/** @var IDBConnection */
	private $dbConnection;

	/** @var CoreRequestBuilder */
	private $coreQueryBuilder;

	/** @var CleanService */
	private $cleanService;

	/** @var ConfigService */
	private $configService;

	/**
	 * Clean constructor.
	 *
	 * @param IDBConnection $connection
	 * @param CoreRequestBuilder $coreQueryBuilder
	 * @param CleanService $cleanService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IDBConnection $connection, CoreRequestBuilder $coreQueryBuilder, CleanService $cleanService,
		ConfigService $configService
	) {
		parent::__construct();
		$this->dbConnection = $connection;
		$this->coreQueryBuilder = $coreQueryBuilder;
		$this->cleanService = $cleanService;
		$this->configService = $configService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:clean')
			 ->setDescription('remove all extra data from database')
			 ->addOption('all', '', InputOption::VALUE_NONE, 'remove all data from the app')
			 ->addOption(
				 'uninstall', '', InputOption::VALUE_NONE,
				 'Uninstall the apps and everything related to the app from the database'
			 );
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$all = $input->getOption('all');
		$uninstall = $input->getOption('uninstall');

		if ($all || $uninstall) {
			$this->coreQueryBuilder->cleanDatabase();
			if ($uninstall) {
				$this->coreQueryBuilder->uninstall();
			}

			return 0;
		}

		$this->cleanService->clean();
		$output->writeln('done');

		return 0;
	}

}



