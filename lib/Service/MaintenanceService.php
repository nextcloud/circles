<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
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


namespace OCA\Circles\Service;

use ArtificialOwl\MySmallPhpTools\Model\SimpleDataStore;
use ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc22\TNC22Logger;
use Exception;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\MaintenanceException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCP\IUserManager;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MaintenanceService
 *
 * @package OCA\Circles\Service
 */
class MaintenanceService {
	use TNC22Logger;


	public const TIMEOUT = 18000;


	/** @var IUserManager */
	private $userManager;

	/** @var CircleRequest */
	private $circleRequest;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var SyncService */
	private $syncService;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var EventWrapperService */
	private $eventWrapperService;

	/** @var CircleService */
	private $circleService;

	/** @var ConfigService */
	private $configService;


	/** @var OutputInterface */
	private $output;


	/**
	 * MaintenanceService constructor.
	 *
	 * @param IUserManager $userManager
	 * @param CircleRequest $circleRequest
	 * @param MemberRequest $memberRequest
	 * @param SyncService $syncService
	 * @param FederatedUserService $federatedUserService
	 * @param EventWrapperService $eventWrapperService
	 * @param CircleService $circleService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IUserManager $userManager,
		CircleRequest $circleRequest,
		MemberRequest $memberRequest,
		SyncService $syncService,
		FederatedUserService $federatedUserService,
		EventWrapperService $eventWrapperService,
		CircleService $circleService,
		ConfigService $configService
	) {
		$this->userManager = $userManager;
		$this->circleRequest = $circleRequest;
		$this->memberRequest = $memberRequest;
		$this->syncService = $syncService;
		$this->federatedUserService = $federatedUserService;
		$this->eventWrapperService = $eventWrapperService;
		$this->circleService = $circleService;
		$this->configService = $configService;
	}


	/**
	 * @param OutputInterface $output
	 */
	public function setOccOutput(OutputInterface $output): void {
		$this->output = $output;
	}


	/**
	 * level=1 -> run every minute
	 * level=2 -> run every 5 minutes
	 * level=3 -> run every hour
	 * level=4 -> run every day
	 * level=5 -> run every week
	 *
	 * @param int $level
	 *
	 * @throws MaintenanceException
	 */
	public function runMaintenance(int $level): void {
		$this->federatedUserService->bypassCurrentUserCondition(true);

		$this->lockMaintenanceRun();
		$this->debug('running maintenance (' . $level . ')');

		switch ($level) {
			case 1:
				$this->runMaintenance1();
				break;
			case 2:
				$this->runMaintenance2();
				break;
			case 3:
				$this->runMaintenance3();
				break;
			case 4:
				$this->runMaintenance4();
				break;
			case 5:
				$this->runMaintenance5();
				break;
		}

		$this->configService->setAppValue(ConfigService::MAINTENANCE_RUN, '0');
	}


	/**
	 * @throws MaintenanceException
	 */
	private function lockMaintenanceRun(): void {
		$run = $this->configService->getAppValueInt(ConfigService::MAINTENANCE_RUN);
		if ($run > time() - self::TIMEOUT) {
			throw new MaintenanceException('maintenance already running');
		}

		$this->configService->setAppValue(ConfigService::MAINTENANCE_RUN, (string)time());
	}


	/**
	 * every minute
	 */
	private function runMaintenance1(): void {
		try {
			$this->output('remove circles with no owner');
			$this->removeCirclesWithNoOwner();
		} catch (Exception $e) {
		}
	}


	/**
	 * every 10 minutes
	 */
	private function runMaintenance2(): void {
		try {
			$this->output('remove members with no circles');
			$this->removeMembersWithNoCircles();
		} catch (Exception $e) {
		}

		try {
			$this->output('retry failed FederatedEvents (asap)');
			$this->eventWrapperService->retry(EventWrapperService::RETRY_ASAP);
		} catch (Exception $e) {
		}
	}


	/**
	 * every hour
	 */
	private function runMaintenance3(): void {
		try {
			$this->output('retry failed FederatedEvents (hourly)');
			$this->eventWrapperService->retry(EventWrapperService::RETRY_HOURLY);
		} catch (Exception $e) {
		}
	}


	/**
	 * every day
	 */
	private function runMaintenance4(): void {
		try {
			$this->output('retry failed FederatedEvents (daily)');
			$this->eventWrapperService->retry(EventWrapperService::RETRY_DAILY);
		} catch (Exception $e) {
		}
		try {
			// TODO: waiting for confirmation of a good migration before cleaning orphan shares
//			$this->output('remove deprecated shares');
//		$this->removeDeprecatedShares();
		} catch (Exception $e) {
		}

		try {
			$this->output('synchronizing local entities');
			$this->syncService->sync();
		} catch (Exception $e) {
		}
	}

	/**
	 * every week
	 */
	private function runMaintenance5(): void {
//		try {
//			$this->output('refresh displayNames older than 7d');
//			//	$this->refreshOldDisplayNames();
//			$this->output('refresh DisplayNames');
//			$this->refreshDisplayName();
//		} catch (Exception $e) {
//		}
	}


	/**
	 * @throws InitiatorNotFoundException
	 * @throws RequestBuilderException
	 */
	private function removeCirclesWithNoOwner(): void {
		$circles = $this->circleService->getCircles();
		foreach ($circles as $circle) {
			if (!$circle->hasOwner()) {
				$this->circleRequest->delete($circle);
			}
		}
	}


	/**
	 *
	 */
	private function removeMembersWithNoCircles(): void {
//		$members = $this->membersRequest->forceGetAllMembers();
//
//		foreach ($members as $member) {
//			try {
//				$this->circlesRequest->forceGetCircle($member->getCircleId());
//			} catch (CircleDoesNotExistException $e) {
//				$this->membersRequest->removeMember($member);
//			}
//		}
	}


	private function removeDeprecatedShares(): void {
//		$circles = array_map(
//			function(DeprecatedCircle $circle) {
//				return $circle->getUniqueId();
//			}, $this->circlesRequest->forceGetCircles()
//		);
//
//		$shares = array_unique(
//			array_map(
//				function($share) {
//					return $share['share_with'];
//				}, $this->fileSharesRequest->getShares()
//			)
//		);
//
//		foreach ($shares as $share) {
//			if (!in_array($share, $circles)) {
//				$this->fileSharesRequest->removeSharesToCircleId($share);
//			}
//		}
	}


	/**
	 * @throws RequestBuilderException
	 * @throws InitiatorNotFoundException
	 */
	private function refreshDisplayName(): void {
		$params = new SimpleDataStore(['includeSystemCircles' => true]);
		$circleFilter = new Circle();
		$circleFilter->setConfig(Circle::CFG_SINGLE);
		$circles = $this->circleService->getCircles($circleFilter, null, $params);

		foreach ($circles as $circle) {
			$owner = $circle->getOwner();
			if ($owner->getUserType() === Member::TYPE_USER) {
				$user = $this->userManager->get($owner->getUserId());
				$this->memberRequest->updateDisplayName($owner->getSingleId(), $user->getDisplayName());
				$this->circleRequest->updateDisplayName($owner->getSingleId(), $user->getDisplayName());
			}
		}
	}

	/**
	 * @param string $message
	 */
	private function output(string $message): void {
		if (!is_null($this->output)) {
			$this->output->writeln('- ' . $message);
		}
	}
}
