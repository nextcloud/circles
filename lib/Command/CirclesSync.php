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
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MigrationTo22Exception;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
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

	/**
	 * CirclesSync constructor.
	 *
	 * @param SyncService $syncService
	 */
	public function __construct(SyncService $syncService) {
		parent::__construct();
		$this->syncService = $syncService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('circles:sync')
			 ->setDescription('Sync Circles and Members')
			 ->addOption('migration', '', InputOption::VALUE_NONE, 'Migrate from Circles 0.21.0')
			 ->addOption('users', '', InputOption::VALUE_NONE, 'Sync Nextcloud Users')
			 ->addOption('user', '', InputOption::VALUE_REQUIRED, 'Sync only a specific Nextcloud User', '')
			 ->addOption('groups', '', InputOption::VALUE_NONE, 'Sync Nextcloud Groups')
			 ->addOption('group', '', InputOption::VALUE_REQUIRED, 'Sync only a specific Nextcloud Group', '')
			 ->addOption('contacts', '', InputOption::VALUE_NONE, 'Sync Contacts')
			 ->addOption('remotes', '', InputOption::VALUE_NONE, 'Sync Remotes')
			 ->addOption('remote', '', InputOption::VALUE_NONE, 'Sync only a specific Remote')
			 ->addOption('global-scale', '', InputOption::VALUE_NONE, 'Sync GlobalScale');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws MigrationTo22Exception
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws SingleCircleNotFoundException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {

		$options = $input->getOptions();
		unset($options['output']);
		if (empty(array_filter($options))) {
			$this->syncService->syncAll();
			$output->writeln('- Sync done');

			return 0;
		}

		if ($input->getOption('migration')) {
			// TODO: lock using setAppValue() to avoid duplicate process
			if (!$this->syncService->migration()) {
				throw new MigrationTo22Exception('Migration already performed successfully');
			}
			$output->writeln('- Migration went smoothly, enjoy using Circles 22!');
		}

		if ($input->getOption('users')) {
			$this->syncService->syncNextcloudUsers();
			$output->writeln('- Nextcloud Users synced');
		}

		if (($userId = $input->getOption('user')) !== '') {
			$federatedUser = $this->syncService->syncNextcloudUser($userId);
			$output->writeln(
				'- Nextcloud User <info>' . $userId . '</info>/<info>' . $federatedUser->getSingleId()
				. '</info> synced'
			);
		}

		if ($input->getOption('groups')) {
			$this->syncService->syncNextcloudGroups();
			$output->writeln('- Nextcloud Groups synced');
		}

		if (($groupId = $input->getOption('group')) !== '') {
			$circle = $this->syncService->syncNextcloudGroup($groupId);
			$output->writeln(
				'- Nextcloud Group <info>' . $groupId . '</info>/<info>' . $circle->getId()
				. '</info> synced'
			);
		}


//


//			echo json_encode(array_filter($options), JSON_PRETTY_PRINT) . "\n";
//			$output->writeln(json_encode($result), JSON_PRETTY_PRINT);

//		if ($input->getOption('broadcast')) {
//
//			return 0;
//		}
//
//		$circleId = (string)$input->getArgument('circle_id');
//		$instance = $input->getOption('instance');
//		if ($instance === '') {
//			try {
//				$circle = $this->circleService->getCircle($circleId);
//			} catch (CircleNotFoundException $e) {
//				throw new CircleNotFoundException(
//					'unknown circle, use --instance to retrieve the data from a remote instance'
//				);
//			}
//			$instance = $circle->getInstance();
//		}
//
//		if ($this->configService->isLocalInstance($instance)) {
//			throw new RemoteNotFoundException('Circle is local');
//		}
//
//		$this->remoteService->syncRemoteCircle($circleId, $instance);

		return 0;
	}

}

