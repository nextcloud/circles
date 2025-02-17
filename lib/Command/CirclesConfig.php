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
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\FederatedUserService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CirclesConfig extends Base {
	public function __construct(
		private FederatedUserService $federatedUserService,
		private CircleService $circleService,
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();
		$this->setName('circles:manage:config')
			->setDescription('edit config/type of a Circle')
			->addArgument('circle_id', InputArgument::REQUIRED, 'ID of the circle')
			->addArgument(
				'config', InputArgument::IS_ARRAY,
				'list of value to change in the configuration of the Circle'
			)
			->addOption('initiator', '', InputOption::VALUE_REQUIRED, 'set an initiator to the request', '')
			->addOption('initiator-type', '', InputOption::VALUE_REQUIRED, 'set initiator type', '0')
			->addOption(
				'super-session', '',
				InputOption::VALUE_NONE, 'use super session to bypass some condition'
			)
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

		try {
			$this->federatedUserService->commandLineInitiator(
				$input->getOption('initiator'),
				Member::parseTypeString($input->getOption('initiator-type')),
				$circleId,
				false
			);

			if ($input->getOption('super-session')) {
				$this->federatedUserService->bypassCurrentUserCondition(true);
			}

			$circle = $this->circleService->getCircle($circleId);

			if (empty($input->getArgument('config'))) {
				$output->writeln(
					json_encode(Circle::getCircleFlags($circle, Circle::FLAGS_LONG), JSON_PRETTY_PRINT)
				);

				return 0;
			}

			$new = $this->generateConfig($circle, $input->getArgument('config'));
			$outcome = $this->circleService->updateConfig($circleId, $new);
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
			$circle = $this->circleService->getCircle($circleId);
			$output->writeln(
				json_encode(
					Circle::getCircleFlags($circle, Circle::FLAGS_LONG),
					JSON_PRETTY_PRINT
				)
			);
		}

		return 0;
	}


	/**
	 * @param Circle $circle
	 * @param array $listing
	 *
	 * @return int
	 */
	private function generateConfig(Circle $circle, array $listing): int {
		$current = clone $circle;
		$valid = $this->filterValidConfig($current);
		foreach ($listing as $item) {
			$add = true;
			if (substr($item, 0, 1) === '_') {
				$add = false;
				$item = substr($item, 1);
			}

			$value = array_search(strtoupper($item), $valid);
			if (!$value) {
				throw new InvalidArgumentException(
					'Invalid config \'' . $item . '\'. Available values: '
					. implode(', ', array_values($valid)) . '. '
					. 'To disable a config, start the value with an underscore'
				);
			}

			if ($add) {
				$current->addConfig($value);
			} else {
				$current->remConfig($value);
			}
		}

		return $current->getConfig();
	}


	/**
	 * @param Circle $circle
	 *
	 * @return array
	 */
	private function filterValidConfig(Circle $circle): array {
		$listing = Circle::$DEF_CFG;
		$filters = Circle::$DEF_CFG_CORE_FILTER;
		if (!$circle->isConfig(Circle::CFG_SYSTEM)) {
			$filters = array_merge($filters, Circle::$DEF_CFG_SYSTEM_FILTER);
		}

		foreach ($filters as $filter) {
			unset($listing[$filter]);
		}

		array_walk(
			$listing,
			function (string &$v): void {
				[, $long] = explode('|', $v);
				$v = strtoupper(str_replace(' ', '', $long));
			}
		);

		return $listing;
	}
}
