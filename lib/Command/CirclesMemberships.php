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
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Exceptions\UserTypeNotFoundException;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\FederatedUserService;
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


	/** @var array */
	private $knownId = [];


	/**
	 * CirclesList constructor.
	 *
	 * @param IUserManager $userManager
	 * @param MembershipRequest $membershipRequest
	 * @param MemberRequest $memberRequest
	 * @param FederatedUserService $federatedUserService
	 */
	public function __construct(
		IUserManager $userManager,
		MembershipRequest $membershipRequest,
		MemberRequest $memberRequest,
		FederatedUserService $federatedUserService
	) {
		parent::__construct();
		$this->userManager = $userManager;
		$this->memberRequest = $memberRequest;
		$this->membershipRequest = $membershipRequest;
		$this->federatedUserService = $federatedUserService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:memberships')
			 ->setDescription('index and display memberships for local and federated users')
			 ->addArgument('userId', InputArgument::OPTIONAL, 'userId to generate memberships', '')
			 ->addOption('all', '', InputOption::VALUE_NONE, 'index all local users')
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
	 * @throws InvalidItemException
	 * @throws UserTypeNotFoundException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$all = $input->getOption('all');
		$userId = $input->getArgument('userId');

		if (!$all && $userId === '') {
			$output->writeln('<error>specify a user, or use --all</error>');

			return 0;
		}

		$type = Member::parseTypeString($input->getOption('type'));
		$federatedUser = $this->federatedUserService->getFederatedUser($userId, (int)$type);

		$output->writeln('UserId: <info>' . $federatedUser->getUserId() . '</info>');
		$output->writeln('Instance: <info>' . $federatedUser->getInstance() . '</info>');
		$output->writeln('UserType: <info>' . Member::$DEF_TYPE[$federatedUser->getUserType()] . '</info>');
		$output->writeln('SingleId: <info>' . $federatedUser->getSingleId() . '</info>');
		$output->writeln('');

		$tree = new NC21TreeNode(null, new SimpleDataStore(['federatedUser' => $federatedUser]));
		$this->generateMemberships($federatedUser->getSingleId(), $tree);
		$this->drawTree($tree, [$this, 'displayLeaf'], 3);

		return 0;
	}


	/**
	 * @param string $id
	 * @param NC21TreeNode $tree
	 * @param array $knownIds
	 */
	private function generateMemberships(string $id, NC21TreeNode $tree, array $knownIds = []) {
		$members = $this->memberRequest->getMembersBySingleId($id);
		foreach ($members as $member) {
			$item = new NC21TreeNode(
				$tree, new SimpleDataStore(
						 [
							 'member'  => $member,
							 'cycling' => in_array($member->getCircleId(), $knownIds)
						 ]
					 )
			);
			if (in_array($member->getCircleId(), $knownIds)) {
				continue;
			}
			$knownIds[] = $id;
			$this->generateMemberships($member->getCircleId(), $item, $knownIds);
			$knownIds = [];
		}
	}


	/**
	 * @param SimpleDataStore $data
	 * @param int $line
	 *
	 * @return string
	 */
	public function displayLeaf(SimpleDataStore $data, int $line): string {
		if ($line === 2) {
			return '';
		}

		if ($line === 3) {
			$cycle = '';
			if ($data->gBool('cycling')) {
				$cycle = ' (loop detected)';
			}
			return $cycle;
		}
		try {

			if ($data->hasKey('federatedUser')) {
				/** @var FederatedUser $federatedUser */
				$federatedUser = $data->gObj('federatedUser', FederatedUser::class);

				return '<info>' . $federatedUser->getSingleId() . '</info>';
			}

			if ($data->hasKey('member')) {
				/** @var Member $member */
				$member = $data->gObj('member', Member::class);

				return ' <info>' . $member->getCircleId() . '</info>';
			}
		} catch (InvalidItemException | ItemNotFoundException | UnknownTypeException $e) {
		}

		return '';
	}

}

