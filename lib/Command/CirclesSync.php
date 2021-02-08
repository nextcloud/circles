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

use Exception;
use OC\Core\Command\Base;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
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

	/** @var MemberService */
	private $memberService;

	/** @var CircleService */
	private $circleService;

	/** @var ConfigService */
	private $configService;


	/**
	 * CirclesSync constructor.
	 *
	 * @param FederatedUserService $federatedUserService
	 * @param CircleService $circlesService
	 * @param MemberService $membersService
	 * @param ConfigService $configService
	 */
	public function __construct(
		FederatedUserService $federatedUserService, CircleService $circlesService,
		MemberService $membersService, ConfigService $configService
	) {
		parent::__construct();
		$this->federatedUserService = $federatedUserService;
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
			 ->addOption('instance', '', InputOption::VALUE_REQUIRED, ' Instance of the circle', '')
			 ->addOption('all', '', InputOption::VALUE_NONE, 'Sync all local circles');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->federatedUserService->bypassCurrentUserCondition(true);

//		if ($input->getOption('all')) {
//			$circles = [];
//			foreach ($circles as $circle) {
//				//$this->syncCircle($circle->getId());
//			}
//		} else {
//			if ($circleId === '') {
//				throw new Exception('missing circle_id or use --all option');
//			}

		$circleId = $input->getArgument('circle_id');
		$instance = $input->getOption('instance');
		if ($instance !== '') {
//			$circle = $this->circleService->getCircle($circleId);
			$this->circleService->syncRemoteCircle($circleId, $instance);
		}


//		$circles = $this->circleService->getCirclesToSync();
//		foreach ($circles as $circle) {
//			$this->memberService->updateCachedFromCircle($circle);
//		}
//
//		try {
//			$this->gsUpstreamService->synchronize($circles);
//		} catch (GSStatusException $e) {
//		}

		return 0;
	}


}

