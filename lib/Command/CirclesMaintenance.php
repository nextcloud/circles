<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Command;

use Exception;
use OC\Core\Command\Base;
use OCA\Circles\Db\CoreRequestBuilder;
use OCA\Circles\Exceptions\MaintenanceException;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MaintenanceService;
use OCA\Circles\Service\OutputService;
use OCA\User_LDAP\Mapping\UserMapping;
use OCP\App\IAppManager;
use OCP\IDBConnection;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
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
	public function __construct(
		private FederatedUserService $federatedUserService,
		private CoreRequestBuilder $coreRequestBuilder,
		private MaintenanceService $maintenanceService,
		private OutputService $outputService,
		private IDBConnection $dbConnection,
		private LoggerInterface $logger,
		private IAppManager $appManager,
	) {
		parent::__construct();
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:maintenance')
			->setDescription('Clean stuff, keeps the app running')
			->addOption('refresh-display-name', '', InputOption::VALUE_REQUIRED, 'refresh single user display name', '')
			->addOption('fix-saml-users-display-name', '', InputOption::VALUE_NONE, 'retrieve users from the db table \'user_saml_users\' to fix their display-name')
			->addOption('fix-ldap-users-display-name', '',
				InputOption::VALUE_NONE, 'retrieve users from the db table \'user_ldap_users\' to fix their display-name')
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
		if (($refreshDisplayName = $input->getOption('refresh-display-name')) !== '') {
			return $this->refreshSingleDisplayName($refreshDisplayName, $output);
		}

		if ($input->getOption('fix-saml-users-display-name')) {
			$this->fixSamlDisplayName($output);
			return 0;
		}

		if ($input->getOption('fix-ldap-users-display-name')) {
			if (!$this->appManager->isEnabledForAnyone('user_ldap')) {
				$output->writeln('The "user_ldap" app is not enabled');
				return 1;
			}
			$this->fixLdapUsersDisplayName($output);
			return 0;
		}

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

			/** @var QuestionHelper $helper */
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

			/** @var QuestionHelper $helper */
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
				$this->logger->warning('issue while performing maintenance', ['level' => $i, ['exception' => $e]]);
				$output->writeln('- <error>issue while performing maintenance</error> ' . $e->getMessage() . ' (more details in logs)');
			}
		}

		$output->writeln('');
		$output->writeln('<info>done</info>');

		return 0;
	}

	/**
	 * @param string $userId
	 * @param OutputInterface $output
	 * @return int
	 * @throws Exception
	 */
	public function refreshSingleDisplayName(string $userId, OutputInterface $output): int {
		$federatedUser = $this->federatedUserService->getLocalFederatedUser($userId);
		$displayName = $this->maintenanceService->updateDisplayName($federatedUser);
		if ($displayName !== '') {
			$output->writeln('Display name of ' . $federatedUser->getSingleId() . ' updated to ' . $displayName);
		}

		return 0;
	}

	public function fixSamlDisplayName(OutputInterface $output): void {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('uid')->from('user_saml_users');

		$cursor = $qb->executeQuery();
		while ($row = $cursor->fetch()) {
			try {
				$this->refreshSingleDisplayName($row['uid'], $output);
			} catch (Exception $e) {
				$output->writeln(get_class($e) . ' while trying to update display name of ' . $row['uid']);
			}
		}
	}

	public function fixLdapUsersDisplayName(OutputInterface $output): void {
		$ldapUserMapping = Server::get(UserMapping::class);
		/** @var array<int, array{dn: string, name: string, uuid: string}> $list */
		$list = $ldapUserMapping->getList();
		foreach ($list as $user) {
			try {
				$this->refreshSingleDisplayName($user['name'], $output);
			} catch (Exception $e) {
				$output->writeln(get_class($e) . ' while trying to update display name of ' . $user['name']);
			}
		}
	}
}
