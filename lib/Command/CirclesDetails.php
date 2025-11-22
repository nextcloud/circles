<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Command;

use OC\Core\Command\Base;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
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
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use OCA\Circles\Service\RemoteService;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CirclesDetails
 *
 * @package OCA\Circles\Command
 */
class CirclesDetails extends Base {
	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var RemoteService */
	private $remoteService;

	/** @var MemberService */
	private $memberService;

	/** @var CircleService */
	private $circleService;

	/** @var ConfigService */
	private $configService;


	/**
	 * CirclesDetails constructor.
	 *
	 * @param FederatedUserService $federatedUserService
	 * @param RemoteService $remoteService
	 * @param CircleService $circlesService
	 * @param MemberService $membersService
	 * @param ConfigService $configService
	 */
	public function __construct(
		FederatedUserService $federatedUserService, RemoteService $remoteService,
		CircleService $circlesService, MemberService $membersService, ConfigService $configService,
	) {
		parent::__construct();
		$this->federatedUserService = $federatedUserService;
		$this->remoteService = $remoteService;
		$this->circleService = $circlesService;
		$this->memberService = $membersService;
		$this->configService = $configService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('circles:manage:details')
			->setDescription('get details about a team by its ID')
			->addArgument('circle_id', InputArgument::REQUIRED, 'ID of the team')
			->addOption('instance', '', InputOption::VALUE_REQUIRED, 'Instance of the team', '')
			->addOption('initiator', '', InputOption::VALUE_REQUIRED, 'set an initiator to the request', '')
			->addOption('initiator-type', '', InputOption::VALUE_REQUIRED, 'set initiator type', '0')
			->addOption('status-code', '', InputOption::VALUE_NONE, 'display status code on exception');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws CircleNotFoundException
	 * @throws FederatedItemException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InitiatorNotFoundException
	 * @throws InvalidItemException
	 * @throws MemberNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws UserTypeNotFoundException
	 * @throws InvalidIdException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$circleId = (string)$input->getArgument('circle_id');
		$instance = $input->getOption('instance');

		try {
			if ($instance !== '') {
				$circle = $this->remoteService->getCircleFromInstance(
					$circleId,
					$instance,
					[
						'initiator' => $input->getOption('initiator'),
						'initiatorType' => Member::parseTypeString($input->getOption('initiator-type'))
					]
				);
			} else {
				try {
					$this->federatedUserService->commandLineInitiator(
						$input->getOption('initiator'),
						Member::parseTypeString($input->getOption('initiator-type')),
						$circleId,
						true
					);


					$probe = new CircleProbe();
					$probe->includeNonVisibleCircles();

					$circle = $this->circleService->getCircle($circleId, $probe);
				} catch (CircleNotFoundException $e) {
					throw new CircleNotFoundException(
						'unknown circle, use --instance to retrieve the data from a remote instance'
					);
				}
			}
		} catch (FederatedItemException $e) {
			if ($input->getOption('status-code')) {
				throw new FederatedItemException(
					' [' . get_class($e) . ', ' . ((string)$e->getStatus()) . ']' . "\n" . $e->getMessage()
				);
			}

			throw $e;
		}

		$output->writeln(json_encode($circle, JSON_PRETTY_PRINT));

		return 0;
	}
}
