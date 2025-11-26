<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Command;

use InvalidArgumentException;
use OC\Core\Command\Base;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
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
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\FederatedUserService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CirclesEdit extends Base {
	public function __construct(
		private FederatedUserService $federatedUserService,
		private CircleService $circleService,
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();
		$this->setName('circles:manage:edit')
			->setDescription('edit displayName or description of a  Team')
			->addArgument('circle_id', InputArgument::REQUIRED, 'ID of the team')
			->addArgument('edit', InputArgument::REQUIRED, 'displayName or description')
			->addArgument('value', InputArgument::REQUIRED, 'new value')
			->addOption('initiator', '', InputOption::VALUE_REQUIRED, 'set an initiator to the request', '')
			->addOption('initiator-type', '', InputOption::VALUE_REQUIRED, 'set initiator type', '0')
			->addOption('status-code', '', InputOption::VALUE_NONE, 'display status code on exception');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws FederatedItemException
	 * @throws CircleNotFoundException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws MemberNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws SingleCircleNotFoundException
	 * @throws UnknownRemoteException
	 * @throws UserTypeNotFoundException
	 * @throws RequestBuilderException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$circleId = (string)$input->getArgument('circle_id');
		$edit = strtolower((string)$input->getArgument('edit'));
		$newValue = (string)$input->getArgument('value');

		try {
			$this->federatedUserService->commandLineInitiator(
				$input->getOption('initiator'),
				Member::parseTypeString($input->getOption('initiator-type')),
				$circleId,
				false
			);

			switch ($edit) {
				case 'displayname':
					$outcome = $this->circleService->updateName($circleId, $newValue);
					break;

				case 'description':
					$outcome = $this->circleService->updateDescription($circleId, $newValue);
					break;

				default:
					throw new InvalidArgumentException('edit can only be \'displayName\' or \'description\'');
			}
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
