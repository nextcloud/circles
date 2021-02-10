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

use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Exceptions\SignatoryException;
use OC\Core\Command\Base;
use OC\User\NoUserException;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use OCA\Circles\Service\RemoteService;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class MembersList
 *
 * @package OCA\Circles\Command
 */
class MembersList extends Base {


	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var RemoteService */
	private $remoteService;

	/** @var MemberService */
	private $memberService;

	/** @var ConfigService */
	private $configService;


	/**
	 * MembersList constructor.
	 *
	 * @param FederatedUserService $federatedUserService
	 * @param RemoteService $remoteService
	 * @param MemberService $memberService
	 * @param ConfigService $configService
	 */
	public function __construct(
		FederatedUserService $federatedUserService, RemoteService $remoteService,
		MemberService $memberService, ConfigService $configService
	) {
		parent::__construct();
		$this->federatedUserService = $federatedUserService;
		$this->remoteService = $remoteService;
		$this->memberService = $memberService;
		$this->configService = $configService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:members:list')
			 ->setDescription('listing Members from a Circle')
			 ->addArgument('circle_id', InputArgument::REQUIRED, 'ID of the circle')
			 ->addOption('instance', '', InputOption::VALUE_REQUIRED, 'Instance of the circle', '')
			 ->addOption('initiator', '', InputOption::VALUE_REQUIRED, 'set an initiator to the request', '')
			 ->addOption('json', '', InputOption::VALUE_NONE, 'returns result as JSON');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws CircleNotFoundException
	 * @throws InitiatorNotFoundException
	 * @throws NoUserException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$circleId = $input->getArgument('circle_id');
		$instance = $input->getOption('instance');
		$initiator = $input->getOption('initiator');

		if ($instance !== '' && !$this->configService->isLocalInstance($instance)) {
			$data = [];
			if ($initiator) {
				$data['initiator'] = $this->federatedUserService->createFederatedUserTypeUser($initiator);
			}

			$members = $this->remoteService->getMembersFromInstance($circleId, $instance, $data);
		} else {
			$this->federatedUserService->commandLineInitiator($initiator, $circleId, true);
			$members = $this->memberService->getMembers($circleId);
		}


		if ($input->getOption('json')) {
			echo json_encode($members, JSON_PRETTY_PRINT) . "\n";

			return 0;
		}

		$output = new ConsoleOutput();
		$output = $output->section();

		$table = new Table($output);
		$table->setHeaders(['ID', 'Single ID', 'Username', 'Instance', 'Level']);
		$table->render();

		$local = $this->configService->getLocalInstance();
		foreach ($members as $member) {
			$table->appendRow(
				[
					$member->getId(),
					$member->getSingleId(),
					$member->getUserId(),
					($member->getInstance() === $local) ? '' : $member->getInstance(),
					Member::$DEF_LEVEL[$member->getLevel()]
				]
			);
		}

		return 0;
	}

}

