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


use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class MaintenanceService
 *
 * @package OCA\Circles\Service
 */
class MaintenanceService {


	/** @var CircleRequest */
	private $circleRequest;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var EventWrapperService */
	private $eventWrapperService;

	/** @var CircleService */
	private $circleService;


	/** @var OutputInterface */
	private $output;


	/**
	 * MaintenanceService constructor.
	 *
	 * @param FederatedUserService $federatedUserService
	 * @param EventWrapperService $eventWrapperService
	 * @param CircleService $circleService
	 */
	public function __construct(
		CircleRequest $circleRequest,
		FederatedUserService $federatedUserService,
		EventWrapperService $eventWrapperService,
		CircleService $circleService
	) {
		$this->circleRequest = $circleRequest;
		$this->federatedUserService = $federatedUserService;
		$this->eventWrapperService = $eventWrapperService;
		$this->circleService = $circleService;
	}


	/**
	 * @param OutputInterface $output
	 */
	public function setOccOutput(OutputInterface $output): void {
		$this->output = $output;
	}


	/**
	 *
	 */
	public function runMaintenance(): void {
		$this->federatedUserService->bypassCurrentUserCondition(true);

		$this->output('remove circles with no owner');
		$this->removeCirclesWithNoOwner();

		$this->output('remove members with no circles');
		$this->removeMembersWithNoCircles();

		$this->output('remove deprecated shares');
//		$this->removeDeprecatedShares();

		$this->output('retry failed FederatedEvents');
		$this->eventWrapperService->retry();
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
	 * @param string $message
	 */
	private function output(string $message): void {
		if (!is_null($this->output)) {
			$this->output->writeln('- ' . $message);
		}
	}

}

