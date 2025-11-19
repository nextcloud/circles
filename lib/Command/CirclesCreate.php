<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Command;

use OC\Core\Command\Base;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Traits\TDeserialize;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CirclesCreate
 *
 * @package OCA\Circles\Command
 */
class CirclesCreate extends Base {
	use TDeserialize;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var CircleService */
	private $circleService;


	/**
	 * CirclesCreate constructor.
	 *
	 * @param FederatedUserService $federatedUserService
	 * @param CircleService $circleService
	 */
	public function __construct(
		FederatedUserService $federatedUserService,
		CircleService $circleService,
	) {
		parent::__construct();
		$this->federatedUserService = $federatedUserService;
		$this->circleService = $circleService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:manage:create')
			->setDescription('create a new team')
			->addArgument('owner', InputArgument::REQUIRED, 'owner of the team')
			->addArgument('name', InputArgument::REQUIRED, 'name of the team')
			->addOption('personal', '', InputOption::VALUE_NONE, 'create a personal team')
			->addOption('local', '', InputOption::VALUE_NONE, 'create a local team')
			->addOption('status-code', '', InputOption::VALUE_NONE, 'display status code on exception')
			->addOption(
				'type', '', InputOption::VALUE_REQUIRED, 'type of the owner',
				Member::$TYPE[Member::TYPE_SINGLE]
			);
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws FederatedItemException
	 * @throws InitiatorNotFoundException
	 * @throws SingleCircleNotFoundException
	 * @throws RequestBuilderException
	 * @throws InvalidItemException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$ownerId = $input->getArgument('owner');
		$name = $input->getArgument('name');

		try {
			$this->federatedUserService->bypassCurrentUserCondition(true);

			$type = Member::parseTypeString($input->getOption('type'));

			$owner = $this->federatedUserService->getFederatedUser($ownerId, $type);
			$outcome = $this->circleService->create(
				$name,
				$owner,
				$input->getOption('personal'),
				$input->getOption('local')
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
		} elseif (strtolower($input->getOption('output')) !== 'none') {
			/** @var Circle $circle */
			$circle = $this->deserialize($outcome, Circle::class);
			$output->writeln('Id: <info>' . $circle->getSingleId() . '</info>');
			$output->writeln('Name: <info>' . $circle->getDisplayName() . '</info>');
			$output->writeln('Owner: <info>' . $circle->getOwner()->getDisplayName() . '</info>');
		}

		return 0;
	}
}
