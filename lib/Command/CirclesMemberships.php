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
use daita\MySmallPhpTools\Exceptions\ItemNotFoundException;
use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Exceptions\SignatoryException;
use daita\MySmallPhpTools\Exceptions\UnknownTypeException;
use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21TreeNode;
use daita\MySmallPhpTools\Model\SimpleDataStore;
use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21ConsoleTree;
use daita\MySmallPhpTools\Traits\TArrayTools;
use OC\Core\Command\Base;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Db\MembershipRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Exceptions\UserTypeNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MembershipService;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class CirclesMembershipsIndex
 *
 * @package OCA\Circles\Command
 */
class CirclesMemberships extends Base {


	use TArrayTools;
	use TNC21ConsoleTree;


	/** @var IUserManager */
	private $userManager;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var MembershipRequest */
	private $membershipRequest;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var MembershipService */
	private $membershipsService;

	/** @var ConfigService */
	private $configService;


	/** @var array */
	private $memberships = [];


	/**
	 * CirclesList constructor.
	 *
	 * @param IUserManager $userManager
	 * @param MembershipRequest $membershipRequest
	 * @param MemberRequest $memberRequest
	 * @param MembershipService $membershipsService
	 * @param FederatedUserService $federatedUserService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IUserManager $userManager,
		MembershipRequest $membershipRequest,
		MemberRequest $memberRequest,
		MembershipService $membershipsService,
		FederatedUserService $federatedUserService,
		ConfigService $configService
	) {
		parent::__construct();
		$this->userManager = $userManager;
		$this->memberRequest = $memberRequest;
		$this->membershipRequest = $membershipRequest;
		$this->membershipsService = $membershipsService;
		$this->federatedUserService = $federatedUserService;
		$this->configService = $configService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('circles:memberships')
			 ->setDescription('index and display memberships for local and federated users')
			 ->addArgument('userId', InputArgument::REQUIRED, 'userId to generate memberships')
			 ->addOption(
				 'type', '', InputOption::VALUE_REQUIRED, 'type of the user',
				 Member::$DEF_TYPE[Member::TYPE_USER]
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
	 * @throws InvalidItemException
	 * @throws MemberNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws UnknownRemoteException
	 * @throws UserTypeNotFoundException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$userId = $input->getArgument('userId');

		$type = Member::parseTypeString($input->getOption('type'));
		$federatedUser = $this->federatedUserService->getFederatedUser($userId, (int)$type);

		$output->writeln('Id: <info>' . $federatedUser->getUserId() . '</info>');
		$output->writeln('Instance: <info>' . $federatedUser->getInstance() . '</info>');
		$output->writeln('Type: <info>' . Member::$DEF_TYPE[$federatedUser->getUserType()] . '</info>');
		$output->writeln('SingleId: <info>' . $federatedUser->getSingleId() . '</info>');

		$output->writeln('');
		$output->writeln('Memberships:');
		$count = $this->membershipsService->onMemberUpdate($federatedUser);
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

		$tree = new NC21TreeNode(null, new SimpleDataStore(['federatedUser' => $federatedUser]));
		$this->generateTree($federatedUser->getSingleId(), $tree);

		$this->drawTree(
			$tree, [$this, 'displayLeaf'],
			[
				'height'       => 3,
				'node-spacing' => 0,
				'item-spacing' => 1,
			]
		);

		return 0;
	}


	/**
	 * @param string $id
	 * @param NC21TreeNode $tree
	 * @param array $knownIds
	 */
	private function generateTree(string $id, NC21TreeNode $tree, array $knownIds = []) {
		if (in_array($id, $knownIds)) {
			return;
		}
		$knownIds[] = $id;

		$members = $this->memberRequest->getMembersBySingleId($id);
		foreach ($members as $member) {
			if ($member->getCircle()->isConfig(Circle::CFG_SINGLE)) {
				continue;
			}

			$item = new NC21TreeNode(
				$tree, new SimpleDataStore(
						 [
							 'member'  => $member,
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
					$line .= '<info>' . $circle->getId() . '</info>';
					if (!$this->configService->isLocalInstance($circle->getInstance())) {
						$line .= '@' . $circle->getInstance();
					}
					$line .= ' (' . $circle->getName() . ')';
					$line .= ' <info>Level</info>: ' . Member::$DEF_LEVEL[$member->getLevel()];

					$knownMembership = $this->memberships[$member->getCircleId()];
					if ($member->getLevel() !== $knownMembership->getLevel()) {
						$line .= ' (' . Member::$DEF_LEVEL[$knownMembership->getLevel()] . ')';
					}
				}

				if ($lineNumber === 2) {
					$owner = $circle->getOwner();
					$line .= '<info>Owner</info>: ' . $owner->getUserId() . '@' . $owner->getInstance() . ' ';
					$type = implode(", ", Circle::getCircleTypes($circle, Circle::TYPES_LONG));
					$line .= ($type === '') ? '' : '<info>Config</info>: ' . $type;
				}

				return $line;
			}
		} catch (InvalidItemException | ItemNotFoundException | UnknownTypeException $e) {
		}

		return '';
	}

}

