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

use OCA\Circles\Tools\Exceptions\RequestNetworkException;
use OCA\Circles\Tools\Exceptions\SignatoryException;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCA\Circles\Tools\Traits\TStringTools;
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
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\ModelManager;
use OCA\Circles\Model\Probes\BasicProbe;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\RemoteService;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CirclesList
 *
 * @package OCA\Circles\Command
 */
class CirclesList extends Base {
	use TArrayTools;
	use TStringTools;


	/** @var ModelManager */
	private $modelManager;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var RemoteService */
	private $remoteService;

	/** @var CircleService */
	private $circleService;

	/** @var ConfigService */
	private $configService;


	/** @var InputInterface */
	private $input;


	/**
	 * CirclesList constructor.
	 *
	 * @param ModelManager $modelManager
	 * @param FederatedUserService $federatedUserService
	 * @param RemoteService $remoteService
	 * @param CircleService $circleService
	 * @param ConfigService $configService
	 */
	public function __construct(
		ModelManager $modelManager, FederatedUserService $federatedUserService, RemoteService $remoteService,
		CircleService $circleService, ConfigService $configService
	) {
		parent::__construct();
		$this->modelManager = $modelManager;
		$this->federatedUserService = $federatedUserService;
		$this->remoteService = $remoteService;
		$this->circleService = $circleService;
		$this->configService = $configService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:manage:list')
			 ->setDescription('listing current circles')
			 ->addOption('instance', '', InputOption::VALUE_REQUIRED, 'Instance of the circle', '')
			 ->addOption('initiator', '', InputOption::VALUE_REQUIRED, 'set an initiator to the request', '')
			 ->addOption('initiator-type', '', InputOption::VALUE_REQUIRED, 'set initiator type', '0')
			 ->addOption('member', '', InputOption::VALUE_REQUIRED, 'search for member', '')
			 ->addOption('def', '', InputOption::VALUE_NONE, 'display complete circle configuration')
			 ->addOption('display-name', '', InputOption::VALUE_NONE, 'display the displayName')
			 ->addOption('personal', '', InputOption::VALUE_NONE, 'include Personal Circles')
			 ->addOption('system', '', InputOption::VALUE_NONE, 'include System Circles')
			 ->addOption('hidden', '', InputOption::VALUE_NONE, 'include Hidden Circles')
			 ->addOption('backend', '', InputOption::VALUE_NONE, 'include Backend Circles')
			 ->addOption('single', '', InputOption::VALUE_NONE, 'returns only Single Circles')
			 ->addOption('all', '', InputOption::VALUE_NONE, 'include all Circles');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws CircleNotFoundException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InitiatorNotFoundException
	 * @throws InvalidIdException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws UnknownRemoteException
	 * @throws UserTypeNotFoundException
	 * @throws FederatedItemException
	 * @throws MemberNotFoundException
	 * @throws SingleCircleNotFoundException
	 * @throws RequestBuilderException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->input = $input;
		$member = $input->getOption('member');
		$instance = $input->getOption('instance');
		$initiator = $input->getOption('initiator');

		$filterMember = null;
		if ($member !== '') {
			$filterMember = $this->federatedUserService->getFederatedMember($member);
		}

		if (!$this->configService->isLocalInstance($instance)) {
			$data = ['filterMember' => $filterMember];
			if ($initiator) {
				$data['initiator'] = $this->federatedUserService->getFederatedUser(
					$initiator,
					Member::parseTypeString($input->getOption('initiator-type')),
				);
			}

			$circles = $this->remoteService->getCirclesFromInstance($instance, $data);
		} else {
			$this->federatedUserService->commandLineInitiator(
				$initiator,
				Member::parseTypeString($input->getOption('initiator-type')),
				'',
				true
			);

			$probe = new CircleProbe();
			$probe->filterHiddenCircles()
				  ->filterBackendCircles()
				  ->addDetail(BasicProbe::DETAILS_POPULATION);

			if ($input->getOption('system')) {
				$probe->includeSystemCircles();
				$probe->filterHiddenCircles(false);
			}

			$probe->includeHiddenCircles($input->getOption('hidden'));
			$probe->includeBackendCircles($input->getOption('backend'));
			$probe->includePersonalCircles($input->getOption('personal'));

			if ($input->getOption('single')) {
				$singleCircle = new Circle();
				$singleCircle->setConfig(Circle::CFG_SINGLE);
				$probe->setFilterCircle($singleCircle)
					  ->includeSingleCircles();
			}

			if ($input->getOption('all')) {
				$probe->includeSystemCircles();
				$probe->includeHiddenCircles();
				$probe->includeBackendCircles();
				$probe->includePersonalCircles();
				$probe->includeSingleCircles();
			}

			$circles = $this->circleService->getCircles($probe);
		}

		if (strtolower($input->getOption('output')) === 'json') {
			$output->writeln(json_encode($circles, JSON_PRETTY_PRINT));

			return 0;
		}

		$this->displayCircles($circles);

		return 0;
	}


	/**
	 * @param Circle[] $circles
	 */
	private function displayCircles(array $circles): void {
		$output = new ConsoleOutput();
		$output = $output->section();
		$table = new Table($output);
		$table->setHeaders(
			['Single Id', 'Name', 'Config', 'Source', 'Owner', 'Instance', 'Population']
		);
		$table->render();

		$displayName = $this->input->getOption('display-name');
		$display = ($this->input->getOption('def') ? Circle::FLAGS_LONG : Circle::FLAGS_SHORT);
		foreach ($circles as $circle) {
			$owner = $circle->getOwner();
			$table->appendRow(
				[
					$circle->getSingleId(),
					$this->cut(($displayName ? $circle->getDisplayName() : $circle->getName()), 40),
					json_encode(Circle::getCircleFlags($circle, $display)),
					Circle::$DEF_SOURCE[$circle->getSource()],
					$this->cut($displayName ? $owner->getDisplayName() : $owner->getUserId(), 40),
					$this->configService->displayInstance($owner->getInstance()),
					$circle->getPopulation() . '/'
					. $this->getInt('members_limit', $circle->getSettings(), -1)
					. ' (' . $circle->getPopulationInherited() . ')'
				]
			);
		}
	}
}
