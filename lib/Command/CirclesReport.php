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

use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCA\Circles\Tools\Traits\TArrayTools;
use OC\Core\Command\Base;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\UnknownInterfaceException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Membership;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Model\Report;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\InterfaceService;
use OCA\Circles\Service\MemberService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CirclesReport
 *
 * @package OCA\Circles\Command
 */
class CirclesReport extends Base implements IInteractiveShellClient {
	use TDeserialize;
	use TArrayTools;


	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var CircleService */
	private $circleService;

	/** @var MemberService */
	private $memberService;

	/** @var InterfaceService */
	private $interfaceService;

	/** @var ConfigService */
	private $configService;


	/** @var OutputInterface */
	private $output;

	/** @var Report */
	private $report;


	/**
	 * CirclesReport constructor.
	 *
	 * @param FederatedUserService $federatedUserService
	 * @param CircleService $circleService
	 * @param MemberService $memberService
	 * @param ConfigService $configService
	 */
	public function __construct(
		FederatedUserService $federatedUserService,
		CircleService $circleService,
		MemberService $memberService,
		InterfaceService $interfaceService,
		ConfigService $configService
	) {
		parent::__construct();

		$this->federatedUserService = $federatedUserService;
		$this->circleService = $circleService;
		$this->memberService = $memberService;
		$this->interfaceService = $interfaceService;
		$this->configService = $configService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('circles:report')
			 ->setDescription('Read and write obfuscated report')
			 ->addOption('local', '', InputOption::VALUE_NONE, 'Use local report')
			 ->addOption('read', '', InputOption::VALUE_REQUIRED, 'File containing the report to read', '');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws InvalidItemException
	 * @throws InitiatorNotFoundException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		throw new \Exception('not available');

		$filename = $input->getOption('read');
		$local = $input->getOption('local');
		$this->output = $output;

		$report = null;
		if ($filename === '' || $local) {
			$report = $this->generateReport();
			if (!$local) {
				$this->output->writeln(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

				return 0;
			}
		}

		if ($filename !== '') {
			/** @var Report $report */
			$data = file_get_contents($filename);
			$report = $this->deserialize(json_decode($data, true), Report::class);
		}

		if (!is_null($report)) {
			$this->readReport($input, $report);
		}

		return 0;
	}


	/**
	 * @throws InitiatorNotFoundException
	 * @throws UnknownInterfaceException
	 */
	private function generateReport(): Report {
		$report = new Report();
		$report->setSource($this->interfaceService->getLocalInstance());
		$this->federatedUserService->bypassCurrentUserCondition(true);

		$probe = new CircleProbe();
		$probe->includeSystemCircles();
		$raw = $this->circleService->getCircles($probe);

		$circles = [];
		foreach ($raw as $circle) {
			$circle->getMembers();

			$circles[] = $this->obfuscateCircle($circle);
		}

		$report->setCircles($circles);

		return $report;
	}


	/**
	 * @param InputInterface $input
	 * @param Report $report
	 */
	private function readReport(InputInterface $input, Report $report) {
		$output = new ConsoleOutput();
		$this->output = $output->section();
		$this->report = $report;

		$interactiveShell = new NC22InteractiveShell($this, $input, $this);
		$commands = [
			'circles.list',
			'circles.delete.#circleId',
			'members.list.#circleId',
			'members.details.#memberId',
			'remoteInstance.list'
		];

		$interactiveShell->setCommands($commands);

		$interactiveShell->run();
	}


	/**
	 * @param string $source
	 * @param string $field
	 *
	 * @return string[]
	 */
	public function fillCommandList(string $source, string $field): array {
		echo $source . ' ' . $field . "\n";

		return ['abcd', 'abdde', 'erfg'];
	}


	/**
	 * @param string $command
	 */
	public function manageCommand(string $command): void {
//		echo $command . "\n";
	}


	/**
	 * @param Circle $circle
	 *
	 * @return Circle
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

		if ($circle->hasInitiator()) {
			$circle->setInitiator($this->obfuscateMember($circle->getInitiator()));
		}

		if ($circle->hasMembers()) {
			$members = [];
			foreach ($circle->getMembers() as $member) {
				$members[] = $this->obfuscateMember($member);
			}
			$circle->setMembers($members);
		}

		if ($circle->hasMemberships()) {
			$memberships = [];
			foreach ($circle->getMemberships() as $membership) {
				$memberships[] = $this->obfuscateMembership($membership);
			}
			$circle->setMemberships($memberships);
		}


		return $circle;
	}


	/**
	 * @param Member $member
	 *
	 * @return Member
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
			   ->setNotes('')
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

		if ($member->hasRemoteInstance()) {
			$member->setRemoteInstance($this->obfuscateRemoteInstance($member->getRemoteInstance()));
		}

		if ($member->hasMemberships()) {
			$memberships = [];
			foreach ($member->getMemberships() as $membership) {
				$memberships[] = $this->obfuscateMembership($membership);
			}
			$member->setMemberships($memberships);
		}

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

		// what was that for ?
//		if ($federatedUser->hasLink()) {
//			$federatedUser->setLink($this->obfuscateMembership($federatedUser->getLink()));
//		}

		if ($federatedUser->hasMemberships()) {
			$memberships = [];
			foreach ($federatedUser->getMemberships() as $membership) {
				$memberships[] = $this->obfuscateMembership($membership);
			}
			$federatedUser->setMemberships($memberships);
		}

		return $federatedUser;
	}


	/**
	 * @param Membership $membership
	 *
	 * @return Membership
	 */
	private function obfuscateMembership(Membership $membership): Membership {
		$membership->setSingleId($this->obfuscateId($membership->getSingleId()));
		$membership->setCircleId($this->obfuscateId($membership->getCircleId()));
		$membership->setInheritanceFirst($this->obfuscateId($membership->getInheritanceFirst()));
		$membership->setInheritanceLast($this->obfuscateId($membership->getInheritanceLast()));

		$path = [];
		foreach ($membership->getInheritancePath() as $item) {
			$path[] = $this->obfuscateId($item);
		}
		$membership->setInheritancePath($path);

		return $membership;
	}


	/**
	 * @param RemoteInstance $remoteInstance
	 *
	 * @return RemoteInstance
	 */
	private function obfuscateRemoteInstance(RemoteInstance $remoteInstance): RemoteInstance {
		return $remoteInstance;
	}


	/**
	 * @param string $id
	 *
	 * @return string
	 */
	private function obfuscateId(string $id): string {
		return substr($id, 0, 5) . '.' . md5(substr($id, 5));
	}


	/**
	 * @param NC22InteractiveShellSession $session
	 */
	public function onNewPrompt(NC22InteractiveShellSession $session): void {
		$prompt =
			'Circles Report [<info>' . $this->report->getSource() . '</info>]:<comment>%PATH%</comment>';

		$commands = [];
		if ($session->getData()->g('currentStatus') === 'write') {
			$commands[] = 'cancel';
			$commands[] = 'write';
			$prompt .= '<error>#</error> ';
		} else {
			$commands[] = 'edit';
			$prompt .= '$ ';
		}

		$session->setGlobalCommands($commands)
				->setPrompt($prompt);
	}


	/**
	 * @param NC22InteractiveShellSession $session
	 * @param $command
	 */
	public function onNewCommand(NC22InteractiveShellSession $session, $command): void {
		echo $session->getPath();
	}
}
