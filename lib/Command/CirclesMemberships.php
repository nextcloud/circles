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

use daita\MySmallPhpTools\Traits\TArrayTools;
use OC\Core\Command\Base;
use OCA\Circles\Db\MembershipRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Model\CurrentUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\ModelManager;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\CurrentUserService;
use OCP\IGroupManager;
use OCP\IUserManager;
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


	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var ModelManager */
	private $modelManager;

	/** @var MembershipRequest */
	private $membershipRequest;

	/** @var CircleService */
	private $circleService;

	/** @var CurrentUserService */
	private $currentUserService;


	/**
	 * CirclesList constructor.
	 *
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param ModelManager $modelManager
	 * @param MembershipRequest $membershipRequest
	 * @param CircleService $circleService
	 * @param CurrentUserService $currentUserService
	 */
	public function __construct(
		IUserManager $userManager, IGroupManager $groupManager, ModelManager $modelManager,
		MembershipRequest $membershipRequest, CircleService $circleService,
		CurrentUserService $currentUserService
	) {
		parent::__construct();
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->modelManager = $modelManager;
		$this->membershipRequest = $membershipRequest;
		$this->circleService = $circleService;

		$this->currentUserService = $currentUserService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:memberships')
			 ->setDescription('manage memberships')
			 ->addOption('index', '', InputOption::VALUE_NONE, 'index memberships');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$userId = $input->getOption('user');

		if ($userId !== '') {
			$this->manageUser($input, $output, $userId);
		} else {
			foreach ($this->userManager->search('') as $user) {
				$this->manageUser($input, $output, $user->getUID());
			}
		}

		return 0;
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @param string $userId
	 */
	private function manageUser(InputInterface $input, OutputInterface $output, string $userId): void {
		if ($input->getOption('index')) {
			try {
				$this->indexLocalUser($userId);
			} catch (CircleNotFoundException $e) {
			}
		}
	}


	/**
	 * @param string $userId
	 *
	 * @throws CircleNotFoundException
	 */
	private function indexLocalUser(string $userId): void {
		$currentUser = new CurrentUser($userId, Member::TYPE_USER, '');
		$this->currentUserService->setCurrentUser($currentUser);

		$this->currentUserService->updateMemberships();
	}

}

