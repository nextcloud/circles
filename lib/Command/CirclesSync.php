<?php

declare(strict_types=1);


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
use OCA\Circles\Exceptions\ContactAddressBookNotFoundException;
use OCA\Circles\Exceptions\ContactFormatException;
use OCA\Circles\Exceptions\ContactNotFoundException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MigrationException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MigrationService;
use OCA\Circles\Service\OutputService;
use OCA\Circles\Service\SyncService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CirclesSync
 *
 * @package OCA\Circles\Command
 */
class CirclesSync extends Base {
	/** @var SyncService */
	private $syncService;

	/** @var MigrationService */
	private $migrationService;

	/** @var OutputService */
	private $outputService;

	/** @var ConfigService */
	private $configService;


	/**
	 * CirclesSync constructor.
	 *
	 * @param SyncService $syncService
	 * @param OutputService $outputService
	 * @param ConfigService $configService
	 */
	public function __construct(
		SyncService $syncService,
		MigrationService $migrationService,
		OutputService $outputService,
		ConfigService $configService
	) {
		parent::__construct();

		$this->syncService = $syncService;
		$this->migrationService = $migrationService;
		$this->outputService = $outputService;
		$this->configService = $configService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('circles:sync')
			 ->setDescription('Sync Circles and Members')
			 ->addOption('migration', '', InputOption::VALUE_NONE, 'Migrate from Circles 0.21.0')
			 ->addOption('force', '', InputOption::VALUE_NONE, 'Force migration')
			 ->addOption('force-run', '', InputOption::VALUE_NONE, 'Force migration run')
			 ->addOption('apps', '', InputOption::VALUE_NONE, 'Sync Apps')
			 ->addOption('users', '', InputOption::VALUE_NONE, 'Sync Nextcloud Account')
			 ->addOption('groups', '', InputOption::VALUE_NONE, 'Sync Nextcloud Groups')
			 ->addOption('contacts', '', InputOption::VALUE_NONE, 'Sync Contacts')
			 ->addOption('remotes', '', InputOption::VALUE_NONE, 'Sync Remotes')
			 ->addOption('global-scale', '', InputOption::VALUE_NONE, 'Sync GlobalScale');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws ContactAddressBookNotFoundException
	 * @throws ContactFormatException
	 * @throws ContactNotFoundException
	 * @throws FederatedUserException
	 * @throws InvalidIdException
	 * @throws MigrationException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->outputService->setOccOutput($output);

		if ($input->getOption('migration')) {
			if ($input->getOption('force-run')) {
				$this->configService->setAppValue(ConfigService::MIGRATION_RUN, '0');
			}

			$this->migrationService->migration($input->getOption('force'));

			$output->writeln('');
			$output->writeln('Migration done');

			return 0;
		}

		$sync = $this->filterSync($input);
		$this->syncService->sync($sync);

		$output->writeln('');
		$output->writeln('Sync done');

		return 0;
	}


	private function filterSync(InputInterface $input): int {
		$sync = 0;
		if ($input->getOption('apps')) {
			$sync += SyncService::SYNC_APPS;
		}
		if ($input->getOption('users')) {
			$sync += SyncService::SYNC_USERS;
		}
		if ($input->getOption('groups')) {
			$sync += SyncService::SYNC_GROUPS;
		}
		if ($input->getOption('global-scale')) {
			$sync += SyncService::SYNC_GLOBALSCALE;
		}
		if ($input->getOption('remotes')) {
			$sync += SyncService::SYNC_REMOTES;
		}
		if ($input->getOption('contacts')) {
			$sync += SyncService::SYNC_CONTACTS;
		}
		if ($sync === 0) {
			$sync = SyncService::SYNC_ALL;
		}

		return $sync;
	}
}
