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

use Exception;
use OC\Core\Command\Base;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Db\MembershipRequest;
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
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MembershipService;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Exceptions\ItemNotFoundException;
use OCA\Circles\Tools\Exceptions\UnknownTypeException;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Tools\Model\TreeNode;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCA\Circles\Tools\Traits\TConsoleTree;
use OCP\IUserManager;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CirclesMemberships
 *
 * @package OCA\Circles\Command
 */
class CirclesMemberships extends Base {
	use TArrayTools;
	use TConsoleTree;


	/** @var IUserManager */
	private $userManager;

	/** @var MembershipRequest */
	private $membershipRequest;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var CircleRequest */
	private $circleRequest;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var CircleService */
	private $circleService;

	/** @var MembershipService */
	private $membershipService;

	/** @var ConfigService */
	private $configService;


	/** @var InputInterface */
	private $input;


	/** @var array */
	private $memberships = [];


	/**
	 * CirclesMemberships constructor.
	 *
	 * @param IUserManager $userManager
	 * @param MembershipRequest $membershipRequest
	 * @param MemberRequest $memberRequest
	 * @param CircleRequest $circleRequest
	 * @param FederatedUserService $federatedUserService
	 * @param CircleService $circleService
	 * @param MembershipService $membershipService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IUserManager $userManager,
		MembershipRequest $membershipRequest,
		MemberRequest $memberRequest,
		CircleRequest $circleRequest,
		FederatedUserService $federatedUserService,
		CircleService $circleService,
		MembershipService $membershipService,
		ConfigService $configService
	) {
		parent::__construct();
		$this->userManager = $userManager;
		$this->memberRequest = $memberRequest;
		$this->membershipRequest = $membershipRequest;
		$this->circleRequest = $circleRequest;
		$this->federatedUserService = $federatedUserService;
		$this->circleService = $circleService;
		$this->membershipService = $membershipService;
		$this->configService = $configService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('circles:memberships')
			 ->setDescription('index and display memberships for local and federated users')
			 ->addArgument('userId', InputArgument::OPTIONAL, 'userId to generate memberships', '')
			 ->addOption('display-name', '', InputOption::VALUE_NONE, 'display the displayName')
//			 ->addOption('reset', '', InputOption::VALUE_NONE, 'reset memberships')
			 ->addOption('all', '', InputOption::VALUE_NONE, 'refresh memberships for all entities')
			 ->addOption(
			 	'type', '', InputOption::VALUE_REQUIRED, 'type of the user',
			 	Member::$TYPE[Member::TYPE_SINGLE]
			 );
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws CircleNotFoundException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws MemberNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws UserTypeNotFoundException
	 * @throws FederatedItemException
	 * @throws InvalidIdException
	 * @throws SingleCircleNotFoundException
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->input = $input;

		if ($input->getOption('all')) {
			$this->manageAllMemberships();

			return 0;
		}

		$userId = $input->getArgument('userId');
		if ($userId === '') {
			throw new Exception('Not enough arguments (missing: "userId").');
		}

		$type = Member::parseTypeString($input->getOption('type'));
		$federatedUser = $this->federatedUserService->getFederatedUser($userId, $type);

//		if ($this->input->getOption('reset')) {
//			$this->membershipsService->resetMemberships($federatedUser->getSingleId());
//
//			return 0;
//		}

		$output->writeln('Id: <info>' . $federatedUser->getUserId() . '</info>');
		$output->writeln('Instance: <info>' . $federatedUser->getInstance() . '</info>');
		$output->writeln('Type: <info>' . Member::$TYPE[$federatedUser->getUserType()] . '</info>');
		$output->writeln('SingleId: <info>' . $federatedUser->getSingleId() . '</info>');

		$output->writeln('');
		$output->writeln('Memberships:');

		$count = $this->membershipService->manageMemberships($federatedUser->getSingleId());
		if ($count === 0) {
			$output->writeln('(database not updated)');
		} else {
			$output->writeln('(' . $count . ' entries generated/updated in the database)');
		}

		foreach ($federatedUser->getMemberships() as $membership) {
			$this->memberships[$membership->getCircleId()] = $membership;
			$output->writeln(
				'- <info>' . $membership->getCircleId() . '</info> ('
				. Member::$DEF_LEVEL[$membership->getLevel()] . ')'
			);
		}

		$output->writeln('');

		$tree = new TreeNode(null, new SimpleDataStore(['federatedUser' => $federatedUser]));
		$this->generateTree($federatedUser->getSingleId(), $tree);

		$this->drawTree(
			$tree, [$this, 'displayLeaf'],
			[
				'height' => 3,
				'node-spacing' => 0,
				'item-spacing' => 1,
			]
		);

		return 0;
	}


	/**
	 * @param string $id
	 * @param TreeNode $tree
	 * @param array $knownIds
	 */
	private function generateTree(string $id, TreeNode $tree, array $knownIds = []) {
		if (in_array($id, $knownIds)) {
			return;
		}
		$knownIds[] = $id;

		$members = $this->memberRequest->getMembersBySingleId($id);
		foreach ($members as $member) {
			if ($member->getLevel() < Member::LEVEL_MEMBER
				|| $member->getCircle()->isConfig(Circle::CFG_SINGLE)) {
				continue;
			}

			$item = new TreeNode(
				$tree, new SimpleDataStore(
					[
						'member' => $member,
						'cycling' => in_array($member->getCircleId(), $knownIds)
					]
				)
			);
			$this->generateTree($member->getCircleId(), $item, $knownIds);
		}
	}


	/**
	 * @param SimpleDataStore $data
	 * @param int $lineNumber
	 *
	 * @return string
	 * @throws OwnerNotFoundException
	 */
	public function displayLeaf(SimpleDataStore $data, int $lineNumber): string {
		if ($lineNumber === 3) {
			return ($data->gBool('cycling')) ? '<comment>(loop detected)</comment>' : '';
		}

		try {
			$line = '';
			if ($data->hasKey('federatedUser')) {
				/** @var FederatedUser $federatedUser */
				$federatedUser = $data->gObj('federatedUser', FederatedUser::class);

				if ($lineNumber === 2) {
					return '';
				}
				$line .= '<info>' . $federatedUser->getSingleId() . '</info>';
				if (!$this->configService->isLocalInstance($federatedUser->getInstance())) {
					$line .= '@' . $federatedUser->getInstance();
				}

				return $line;
			}

			if ($data->hasKey('member')) {
				/** @var Member $member */
				$member = $data->gObj('member', Member::class);
				$circle = $member->getCircle();

				if ($lineNumber === 1) {
					$line .= '<info>' . $circle->getSingleId() . '</info>';
					if (!$this->configService->isLocalInstance($circle->getInstance())) {
						$line .= '@' . $circle->getInstance();
					}
					$line .= ' (' . ($this->input->getOption('display-name') ?
							$circle->getDisplayName() : $circle->getName()) . ')';
					$line .= ' <info>MemberId</info>: ' . $member->getId();
					$line .= ' <info>Level</info>: ' . Member::$DEF_LEVEL[$member->getLevel()];

					$knownMembership = $this->memberships[$member->getCircleId()];
					if ($member->getLevel() !== $knownMembership->getLevel()) {
						$line .= ' (' . Member::$DEF_LEVEL[$knownMembership->getLevel()] . ')';
					}
				}
				if ($lineNumber === 2) {
					$owner = $circle->getOwner();
					$line .= '<info>Owner</info>: ' . $owner->getUserId() . '@' . $owner->getInstance() . ' ';
					if ($owner->hasBasedOn()) {
						$line .= '(' . Circle::$DEF_SOURCE[$owner->getBasedOn()->getSource()] . ') ';
					}
					$type = implode(", ", Circle::getCircleFlags($circle, Circle::FLAGS_LONG));
					$line .= ($type === '') ? '' : '<info>Config</info>: ' . $type;
				}

				return $line;
			}
		} catch (InvalidItemException | ItemNotFoundException | UnknownTypeException $e) {
		}

		return '';
	}


	/**
	 * @throws FederatedUserNotFoundException
	 * @throws InitiatorNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RequestBuilderException
	 */
	private function manageAllMemberships() {
//		if ($this->input->getOption('reset')) {
//			$this->membershipsService->resetMemberships('', true);
//
//			return;
//		}

		$this->federatedUserService->bypassCurrentUserCondition(true);

		$probe = new CircleProbe();
		$probe->includeSystemCircles()
			  ->includeSingleCircles()
			  ->includePersonalCircles();
		$circles = $this->circleService->getCircles($probe);

		$output = new ConsoleOutput();
		$output = $output->section();
		$table = new Table($output);
		$table->setHeaders(['Circle Id', 'Name', 'Source', 'Owner', 'Instance', 'Updated', 'Memberships']);
		$table->render();

		$count = 0;
		foreach ($circles as $circle) {
			$owner = $circle->getOwner();

			$updated = $this->membershipService->manageMemberships($circle->getSingleId());
			$count += $updated;
			$federatedUser = $this->circleRequest->getFederatedUserBySingleId($circle->getSingleId());
			$table->appendRow(
				[
					$circle->getSingleId(),
					$circle->getDisplayName(),
					($circle->getSource() > 0) ? Circle::$DEF_SOURCE[$circle->getSource()] : '',
					$owner->getUserId(),
					$this->configService->displayInstance($owner->getInstance()),
					$updated,
					sizeof($federatedUser->getMemberships())
				]
			);
		}

		$output->writeln($count . ' memberships updated');
	}
}
