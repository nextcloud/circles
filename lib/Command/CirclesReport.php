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


namespace OCA\Circles\Command;

use daita\MySmallPhpTools\Exceptions\InvalidItemException;
use daita\MySmallPhpTools\Model\SimpleDataStore;
use daita\MySmallPhpTools\Traits\Nextcloud\nc22\TNC22Deserialize;
use daita\MySmallPhpTools\Traits\TArrayTools;
use OC\Core\Command\Base;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Membership;
use OCA\Circles\Model\Report;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class CirclesReport
 *
 * @package OCA\Circles\Command
 */
class CirclesReport extends Base {


	use TNC22Deserialize;
	use TArrayTools;


	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var CircleService */
	private $circleService;

	/** @var MemberService */
	private $memberService;


	/**
	 * CirclesReport constructor.
	 *
	 * @param FederatedUserService $federatedUserService
	 * @param CircleService $circleService
	 * @param MemberService $memberService
	 */
	public function __construct(
		FederatedUserService $federatedUserService,
		CircleService $circleService,
		MemberService $memberService
	) {
		parent::__construct();

		$this->federatedUserService = $federatedUserService;
		$this->circleService = $circleService;
		$this->memberService = $memberService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('circles:report')
			 ->setDescription('read and write report');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws InvalidItemException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		stream_set_blocking(STDIN, false);
		$data = file_get_contents('php://stdin');

		if ($data === '') {
			$this->generateReport($output);
		} else {
			/** @var Report $report */
			$report = $this->deserialize(json_decode($data, true), Report::class);
			$this->readReport($output, $report);
		}

		return 0;
	}


	/**
	 * @param OutputInterface $output
	 *
	 * @throws InitiatorNotFoundException
	 */
	private function generateReport(OutputInterface $output): void {
		$report = new Report();
		$this->federatedUserService->bypassCurrentUserCondition(true);

		$raw = $this->circleService->getCircles(
			null,
			null,
			new SimpleDataStore(['includeSystemCircles' => true])
		);

		$circles = [];
		foreach ($raw as $circle) {
			$circle->getMembers();

			$circles[] = $this->obfuscateCircle($circle);
		}

		$report->setCircles($circles);

		$output->writeln(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	}


	/**
	 * @param OutputInterface $output
	 * @param Report $report
	 */
	private function readReport(OutputInterface $output, Report $report) {
		echo json_encode($report);
	}


	/**
	 * @param Circle $circle
	 */
	private function obfuscateCircle(Circle $circle): Circle {
		$singleId = $this->obfuscateId($circle->getSingleId());
		$circle->setSingleId($singleId)
			   ->setName($singleId)
			   ->setDisplayName($singleId)
			   ->setDescription('')
			   ->setCreation(0);

		if ($circle->hasOwner()) {
			$circle->setOwner($this->obfuscateMember($circle->getOwner()));
		}

		return $circle;
	}


	/**
	 * @param Member $member
	 */
	private function obfuscateMember(Member $member): Member {
		$memberId = $this->obfuscateId($member->getId());
		$singleId = $this->obfuscateId($member->getSingleId());
		$circleId = $this->obfuscateId($member->getCircleId());

		$member->setSingleId($singleId)
			   ->setCircleId($circleId)
			   ->setId($memberId)
			   ->setUserId($singleId)
			   ->setDisplayName($singleId)
			   ->setDisplayUpdate(0)
			   ->setNote('')
			   ->setContactId('')
			   ->setContactMeta('')
			   ->setJoined(0);

		if ($member->hasCircle()) {
			$member->setCircle($this->obfuscateCircle($member->getCircle()));
		}

		if ($member->hasBasedOn()) {
			$member->setBasedOn($this->obfuscateCircle($member->getBasedOn()));
		}

		if ($member->hasInheritedBy()) {
			$member->setInheritedBy($this->obfuscateFederatedUser($member->getInheritedBy()));
		}

		if ($member->hasInheritanceFrom()) {
			$member->setInheritanceFrom($this->obfuscateMember($member->getInheritanceFrom()));
		}

		if ($member->hasInheritedBy()) {
			$member->setInheritedBy($this->obfuscateFederatedUser($member->getInheritedBy()));
		}

//			$arr['members'] = $this->getMembers();
//			$arr['inheritedMembers'] = $this->getInheritedMembers();
//			$arr['memberships'] = $this->getMemberships();
//			$arr['remoteInstance'] = $this->getRemoteInstance();

		return $member;
	}


	/**
	 * @param FederatedUser $federatedUser
	 *
	 * @return FederatedUser
	 */
	private function obfuscateFederatedUser(FederatedUser $federatedUser): FederatedUser {
		$singleId = $this->obfuscateId($federatedUser->getSingleId());
		$federatedUser->setSingleId($singleId)
					  ->setUserId($singleId);

		if ($federatedUser->hasBasedOn()) {
			$federatedUser->setBasedOn($this->obfuscateCircle($federatedUser->getBasedOn()));
		}

		if ($federatedUser->hasLink()) {
			$federatedUser->setLink($this->obfuscateMembership($federatedUser->getLink()));
		}

		if ($federatedUser->hasMembers()) {
		}

//			$arr['members'] = $this->getMembers();
//			$arr['inheritedMembers'] = $this->getInheritedMembers();
//			$arr['memberships'] = $this->getMemberships();
	}


	/**
	 * @param Membership $membership
	 *
	 * @return Membership
	 */
	private function obfuscateMembership(Membership $membership): Membership {


		return $membership;
	}


	/**
	 * @param string $id
	 *
	 * @return string
	 */
	private function obfuscateId(string $id): string {
		return substr($id, 0, 5) . '.' . md5(substr($id, 5));
	}


}

