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

use Exception;
use OC\User\NoUserException;
use OCA\Circles\Db\AccountsRequest;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Db\ShareWrapperRequest;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\MaintenanceException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Model\ShareWrapper;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCP\IGroupManager;
use OCP\IUserManager;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MaintenanceService
 *
 * @package OCA\Circles\Service
 */
class MaintenanceService {
	use TNCLogger;

	public const TIMEOUT = 18000;

	public static $DELAY =
		[
			1 => 60,    // every minute
			2 => 300,   // every 5 minutes
			3 => 3600,  // every hour
			4 => 75400, // every day
			5 => 432000 // evey week
		];


	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var AccountsRequest */
	private $accountRequest;

	/** @var CircleRequest */
	private $circleRequest;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var ShareWrapperRequest */
	private $shareWrapperRequest;

	/** @var SyncService */
	private $syncService;

	/** @var FederatedUserService */
	private $federatedUserService;

	private ShareWrapperService $shareWrapperService;

	/** @var MembershipService */
	private $membershipService;

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
	 * @param IGroupManager $groupManager
	 * @param CircleRequest $circleRequest
	 * @param MemberRequest $memberRequest
	 * @param ShareWrapperRequest $shareWrapperRequest
	 * @param SyncService $syncService
	 * @param FederatedUserService $federatedUserService
	 * @param ShareWrapperService $shareWrapperService
	 * @param MembershipService $membershipService
	 * @param EventWrapperService $eventWrapperService
	 * @param CircleService $circleService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IUserManager $userManager,
		IGroupManager $groupManager,
		CircleRequest $circleRequest,
		AccountsRequest $accountRequest,
		MemberRequest $memberRequest,
		ShareWrapperRequest $shareWrapperRequest,
		SyncService $syncService,
		FederatedUserService $federatedUserService,
		ShareWrapperService $shareWrapperService,
		MembershipService $membershipService,
		EventWrapperService $eventWrapperService,
		CircleService $circleService,
		ConfigService $configService
	) {
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->circleRequest = $circleRequest;
		$this->accountRequest = $accountRequest;
		$this->memberRequest = $memberRequest;
		$this->shareWrapperRequest = $shareWrapperRequest;
		$this->syncService = $syncService;
		$this->federatedUserService = $federatedUserService;
		$this->shareWrapperService = $shareWrapperService;
		$this->eventWrapperService = $eventWrapperService;
		$this->membershipService = $membershipService;
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
	public function runMaintenance(int $level, bool $forceRefresh = false): void {
		$this->federatedUserService->bypassCurrentUserCondition(true);

		$this->lockMaintenanceRun();
		$this->debug('running maintenance (' . $level . ')');

		switch ($level) {
			case 1:
				$this->runMaintenance1($forceRefresh);
				break;
			case 2:
				$this->runMaintenance2($forceRefresh);
				break;
			case 3:
				$this->runMaintenance3($forceRefresh);
				break;
			case 4:
				$this->runMaintenance4($forceRefresh);
				break;
			case 5:
				$this->runMaintenance5($forceRefresh);
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
	private function runMaintenance1(bool $forceRefresh = false): void {
		try {
			$this->output('Remove circles with no owner');
			$this->removeCirclesWithNoOwner();
		} catch (Exception $e) {
		}
	}


	/**
	 * every 10 minutes
	 */
	private function runMaintenance2(bool $forceRefresh = false): void {
		try {
			$this->output('Remove members with no circles');
			$this->removeMembersWithNoCircles();
		} catch (Exception $e) {
		}

		try {
			$this->output('Retry failed FederatedEvents (asap)');
			$this->eventWrapperService->retry(EventWrapperService::RETRY_ASAP);
		} catch (Exception $e) {
		}
	}


	/**
	 * every hour
	 */
	private function runMaintenance3(bool $forceRefresh = false): void {
		try {
			$this->output('Retry failed FederatedEvents (hourly)');
			$this->eventWrapperService->retry(EventWrapperService::RETRY_HOURLY);
		} catch (Exception $e) {
		}
	}


	/**
	 * every day
	 */
	private function runMaintenance4(bool $forceRefresh = false): void {
		try {
			$this->output('Retry failed FederatedEvents (daily)');
			$this->eventWrapperService->retry(EventWrapperService::RETRY_DAILY);
		} catch (Exception $e) {
		}

		try {
			// TODO: waiting for confirmation of a good migration before cleaning orphan shares
			if ($this->configService->getAppValueBool(ConfigService::MIGRATION_22_CONFIRMED)) {
				$this->output('Remove deprecated shares');
				$this->removeDeprecatedShares();
			}
		} catch (Exception $e) {
		}

		try {
			$this->output('Synchronizing local entities');
			$this->syncService->sync();
		} catch (Exception $e) {
		}
	}

	/**
	 * every week
	 */
	private function runMaintenance5(bool $forceRefresh = false): void {
		try {
			$this->output('Update memberships');
			$this->updateAllMemberships();
		} catch (Exception $e) {
		}

		try {
			$this->output('refresh members\' display name');
			$this->refreshDisplayName($forceRefresh);
		} catch (Exception $e) {
		}

		try {
			// Can be removed in NC27.
			$this->output('Remove orphan shares');
			$this->removeOrphanShares();
		} catch (Exception $e) {
		}

		try {
			// Can be removed in NC27.
			$this->output('fix sub-circle display name');
			$this->fixSubCirclesDisplayName();
		} catch (Exception $e) {
		}
	}


	/**
	 * @throws InitiatorNotFoundException
	 * @throws RequestBuilderException
	 */
	private function removeCirclesWithNoOwner(): void {
		$probe = new CircleProbe();
		$probe->includeSystemCircles()
			  ->includeSingleCircles()
			  ->includePersonalCircles();
		$circles = $this->circleService->getCircles($probe);
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


	private function removeOrphanShares(): void {
		$this->shareWrapperRequest->removeOrphanShares();
	}


	/**
	 * @throws RequestBuilderException
	 */
	private function removeDeprecatedShares(): void {
		$probe = new CircleProbe();
		$probe->includePersonalCircles()
			  ->includeSingleCircles()
			  ->includeSystemCircles();

		$circles = array_map(
			function (Circle $circle) {
				return $circle->getSingleId();
			}, $this->circleRequest->getCircles(null, $probe)
		);

		$shares = array_unique(
			array_map(
				function (ShareWrapper $share) {
					return $share->getSharedWith();
				}, $this->shareWrapperRequest->getShares()
			)
		);

		foreach ($shares as $share) {
			if (!in_array($share, $circles)) {
				$this->shareWrapperService->deleteAllSharesToCircle($share);
			}
		}
	}


	/**
	 * @throws InitiatorNotFoundException
	 * @throws RequestBuilderException
	 */
	private function updateAllMemberships(): void {
		$probe = new CircleProbe();
		$probe->includeSystemCircles()
			  ->includeSingleCircles()
			  ->includePersonalCircles();

		foreach ($this->circleService->getCircles($probe) as $circle) {
			$this->membershipService->manageMemberships($circle->getSingleId());
		}
	}

	/**
	 * @throws RequestBuilderException
	 * @throws InitiatorNotFoundException
	 */
	private function refreshDisplayName(bool $forceRefresh = false): void {
		$circleFilter = new Circle();
		$circleFilter->setConfig(Circle::CFG_SINGLE);

		$probe = new CircleProbe();
		$probe->includeSingleCircles()
			  ->setFilterCircle($circleFilter)
			  ->mustBeOwner();

		$circles = $this->circleService->getCircles($probe);

		foreach ($circles as $circle) {
			$owner = $circle->getOwner();
			if (!$forceRefresh && $owner->getDisplayUpdate() > (time() - 691200)) {
				continue; // ignore update done in the last 8 days.
			}

			$this->updateDisplayName($owner);
		}
	}

	/**
	 * @param IFederatedUser $federatedUser
	 *
	 * @return string
	 * @throws NoUserException
	 */
	public function updateDisplayName(IFederatedUser $federatedUser): string {
		if ($federatedUser->getUserType() !== Member::TYPE_USER) {
			return '';
		}

		$user = $this->userManager->get($federatedUser->getUserId());
		if ($user === null) {
			throw new NoUserException();
		}

		$displayName = $user->getDisplayName();
		if ($displayName !== '') {
			$this->memberRequest->updateDisplayName($federatedUser->getSingleId(), $displayName);
			$this->circleRequest->updateDisplayName($federatedUser->getSingleId(), $displayName);
		}

		return $displayName;
	}


	/**
	 * @throws RequestBuilderException
	 * @throws InitiatorNotFoundException
	 */
	private function fixSubCirclesDisplayName(): void {
		$probe = new CircleProbe();
		$probe->includeSingleCircles();

		$circles = $this->circleService->getCircles($probe);

		foreach ($circles as $circle) {
			$this->memberRequest->updateDisplayName($circle->getSingleId(), $circle->getDisplayName());
		}
	}


	/**
	 * should only be called from a BackgroundJob
	 *
	 * @param bool $heavy - set to true to run heavy maintenance process.
	 */
	public function runMaintenances(bool $heavy = false): void {
		$last = new SimpleDataStore();
		$last->json($this->configService->getAppValue(ConfigService::MAINTENANCE_UPDATE));

		$maxLevel = ($heavy) ? 5 : 3;
		for ($i = $maxLevel; $i > 0; $i--) {
			if ($this->canRunLevel($i, $last)) {
				try {
					$this->runMaintenance($i);
				} catch (MaintenanceException $e) {
					continue;
				}
				$last->sInt((string)$i, time());
			}
		}

		$this->configService->setAppValue(ConfigService::MAINTENANCE_UPDATE, json_encode($last));
	}


	/**
	 * @param int $level
	 * @param SimpleDataStore $last
	 *
	 * @return bool
	 */
	private function canRunLevel(int $level, SimpleDataStore $last): bool {
		$now = time();
		$timeLastRun = $last->gInt((string)$level);
		if ($timeLastRun === 0) {
			return true;
		}

		return ($timeLastRun + self::$DELAY[$level] < $now);
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
