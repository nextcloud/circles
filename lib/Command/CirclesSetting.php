<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Command;

use InvalidArgumentException;
use OC\Core\Command\Base;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotConfirmedException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Exceptions\UserTypeNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Helpers\MemberHelper;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCP\Security\IHasher;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CirclesSetting extends Base {
	public function __construct(
		private IHasher $hasher,
		private FederatedUserService $federatedUserService,
		private CircleService $circleService,
		private ConfigService $configService,
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();
		$this->setName('circles:manage:setting')
			->setDescription('edit setting for a Circle')
			->addArgument('circle_id', InputArgument::REQUIRED, 'ID of the circle')
			->addArgument('setting', InputArgument::OPTIONAL, 'setting to edit', '')
			->addArgument('value', InputArgument::OPTIONAL, 'value', '')
			->addOption('unset', '', InputOption::VALUE_NONE, 'unset the setting')
			->addOption(
				'test-password', '', InputOption::VALUE_REQUIRED,
				'test and compare password with hash (in case of static password)'
			)
			->addOption('initiator', '', InputOption::VALUE_REQUIRED, 'set an initiator to the request', '')
			->addOption('initiator-type', '', InputOption::VALUE_REQUIRED, 'set initiator type', '0')
			->addOption('status-code', '', InputOption::VALUE_NONE, 'display status code on exception');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotFoundException
	 * @throws RequestBuilderException
	 * @throws CircleNotFoundException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InitiatorNotConfirmedException
	 * @throws InvalidIdException
	 * @throws MemberNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws SingleCircleNotFoundException
	 * @throws UnknownRemoteException
	 * @throws UserTypeNotFoundException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$circleId = (string)$input->getArgument('circle_id');
		$setting = (string)$input->getArgument('setting');
		$value = (string)$input->getArgument('value');

		try {
			$this->federatedUserService->commandLineInitiator(
				$input->getOption('initiator'),
				Member::parseTypeString($input->getOption('initiator-type')),
				$circleId,
				false
			);

			if ($setting === '') {
				$circle = $this->circleService->getCircle($circleId);
				$initiatorHelper = new MemberHelper($circle->getInitiator());
				$initiatorHelper->mustBeAdmin();
				$output->writeln(json_encode($circle->getSettings(), JSON_PRETTY_PRINT));

				$testPassword = $input->getOption('test-password');
				if ($testPassword !== null) {
					$this->testPassword($output, $circle, $testPassword);
				}

				return 0;
			}

			if (!$input->getOption('unset') && $value === '') {
				throw new InvalidArgumentException('you need to specify a value');
			}

			$outcome = $this->circleService->updateSetting(
				$circleId,
				$setting,
				($input->getOption('unset')) ? null : $value,
			);
		} catch (FederatedItemException $e) {
			if ($input->getOption('status-code')) {
				throw new FederatedItemException(
					' [' . get_class($e) . ', ' . ((string)$e->getStatus()) . ']' . "\n" . $e->getMessage()
				);
			}

			throw $e;
		}

		if (strtolower($input->getOption('output')) === 'json') {
			$output->writeln(json_encode($outcome, JSON_PRETTY_PRINT));
		}

		return 0;
	}


	private function testPassword(OutputInterface $output, Circle $circle, string $testPassword): void {
		$output->writeln('');


		$output->write('Password enforced for this Circle: ');
		if (!$this->configService->enforcePasswordOnSharedFile($circle)) {
			$output->writeln('<error>no</error>');

			return;
		}
		$output->writeln('<info>yes</info>');

		$output->write('Single password is configured for this Circle: ');
		if (!$this->configService->isSinglePasswordAvailable($circle)) {
			$output->writeln('<error>no</error>');

			return;
		}
		$output->writeln('<info>yes</info>');

		$output->write('Comparing password with hashed version in database: ');
		if ($this->hasher->verify($testPassword, $circle->getSettings()['password_single'])) {
			$output->writeln('<info>ok</info>');
		} else {
			$output->writeln('<error>fail</error>');
		}
	}
}
