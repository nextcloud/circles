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

use daita\MySmallPhpTools\Exceptions\InvalidItemException;
use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Exceptions\SignatoryException;
use OC\Core\Command\Base;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use OCA\Circles\Service\RemoteService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class CirclesSync
 *
 * @package OCA\Circles\Command
 */
class CirclesSync extends Base {


	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var RemoteService */
	private $remoteService;

	/** @var CircleService */
	private $circleService;

	/** @var MemberService */
	private $memberService;

	/** @var ConfigService */
	private $configService;


	/**
	 * CirclesSync constructor.
	 *
	 * @param FederatedUserService $federatedUserService
	 * @param RemoteService $remoteService
	 * @param CircleService $circlesService
	 * @param MemberService $membersService
	 * @param ConfigService $configService
	 */
	public function __construct(
		FederatedUserService $federatedUserService, RemoteService $remoteService,
		CircleService $circlesService, MemberService $membersService, ConfigService $configService
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
		$this->setName('circles:manage:sync')
			 ->setDescription('Sync circles and members')
			 ->addArgument('circle_id', InputArgument::OPTIONAL, 'ID of the circle', '')
			 ->addOption('instance', '', InputOption::VALUE_REQUIRED, 'Instance of the circle', '')
			 ->addOption('broadcast', '', InputOption::VALUE_NONE, 'Broadcast all circle from this instance')
			 ->addOption('all', '', InputOption::VALUE_NONE, 'Sync all local circles');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws CircleNotFoundException
	 * @throws InitiatorNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteNotFoundException
	 * @throws InvalidIdException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws InvalidItemException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->federatedUserService->bypassCurrentUserCondition(true);

		if ($input->getOption('broadcast')) {


			return 0;
		}

		$circleId = (string)$input->getArgument('circle_id');
		$instance = $input->getOption('instance');
		if ($instance === '') {
			try {
				$circle = $this->circleService->getCircle($circleId);
			} catch (CircleNotFoundException $e) {
				throw new CircleNotFoundException(
					'unknown circle, use --instance to retrieve the data from a remote instance'
				);
			}
			$instance = $circle->getInstance();
		}

		if ($this->configService->isLocalInstance($instance)) {
			throw new RemoteNotFoundException('instance is local');
		}

		$this->remoteService->syncRemoteCircle($circleId, $instance);

		return 0;
	}

}

