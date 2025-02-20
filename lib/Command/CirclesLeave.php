<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Command;

use OC\Core\Command\Base;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CirclesLeave
 *
 * @package OCA\Circles\Command
 */
class CirclesLeave extends Base {
	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var CircleService */
	private $circleService;

	/** @var ConfigService */
	private $configService;


	/**
	 * CirclesLeave constructor.
	 *
	 * @param FederatedUserService $federatedUserService
	 * @param CircleService $circlesService
	 * @param ConfigService $configService
	 */
	public function __construct(
		FederatedUserService $federatedUserService, CircleService $circlesService,
		ConfigService $configService,
	) {
		parent::__construct();

		$this->federatedUserService = $federatedUserService;
		$this->circleService = $circlesService;
		$this->configService = $configService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('circles:manage:leave')
			->setDescription('simulate a user joining a Circle')
			->addArgument('circle_id', InputArgument::REQUIRED, 'ID of the circle')
			->addArgument('initiator', InputArgument::REQUIRED, 'initiator to the request')
			->addOption('type', '', InputOption::VALUE_REQUIRED, 'set initiator type', '0')
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
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$circleId = (string)$input->getArgument('circle_id');

		try {
			$this->federatedUserService->commandLineInitiator(
				$input->getArgument('initiator'),
				Member::parseTypeString($input->getOption('type')),
				'',
				false
			);

			$outcome = $this->circleService->circleLeave($circleId);
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
}
