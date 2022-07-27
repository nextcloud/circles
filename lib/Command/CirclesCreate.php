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
		CircleService $circleService
	) {
		parent::__construct();
		$this->federatedUserService = $federatedUserService;
		$this->circleService = $circleService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:manage:create')
			 ->setDescription('create a new circle')
			 ->addArgument('owner', InputArgument::REQUIRED, 'owner of the circle')
			 ->addArgument('name', InputArgument::REQUIRED, 'name of the circle')
			 ->addOption('personal', '', InputOption::VALUE_NONE, 'create a personal circle')
			 ->addOption('local', '', InputOption::VALUE_NONE, 'create a local circle')
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
					' [' . get_class($e) . ', ' . $e->getStatus() . ']' . "\n" . $e->getMessage()
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
