<?php declare(strict_types=1);


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
use OC\User\NoUserException;
use OCA\Circles\Db\MembersRequest;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\MembersService;
use OCP\IL10N;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class MembersCreate
 *
 * @package OCA\Circles\Command
 */
class MembersCreate extends Base {


	/** @var IL10N */
	private $l10n;

	/** @var IUserManager */
	private $userManager;

	/** @var MembersService */
	private $membersService;

	/** @var MembersRequest */
	private $membersRequest;


	/**
	 * MembersCreate constructor.
	 *
	 * @param IL10N $l10n
	 * @param IUserManager $userManager
	 * @param MembersService $membersService
	 * @param MembersRequest $membersRequest
	 */
	public function __construct(
		IL10N $l10n, IUserManager $userManager, MembersService $membersService, MembersRequest $membersRequest
	) {
		parent::__construct();
		$this->l10n = $l10n;
		$this->userManager = $userManager;
		$this->membersService = $membersService;
		$this->membersRequest = $membersRequest;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:members:create')
			 ->setDescription('create a new member')
			 ->addArgument('circle_id', InputArgument::REQUIRED, 'ID of the circle')
			 ->addArgument('user', InputArgument::REQUIRED, 'username of the member')
			 ->addArgument('level', InputArgument::OPTIONAL, 'level of the member', 'member');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws NoUserException
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$circleId = $input->getArgument('circle_id');
		$userId = $input->getArgument('user');
		$level = $input->getArgument('level');

		$user = $this->userManager->get($userId);
		if ($user === null) {
			throw new NoUserException('user does not exist');
		}
		$userId = $user->getUID();

		$levels = [
			'member'    => Member::LEVEL_MEMBER,
			'moderator' => Member::LEVEL_MODERATOR,
			'admin'     => Member::LEVEL_ADMIN,
			'owner'     => Member::LEVEL_OWNER
		];

		if (!key_exists(strtolower($level), $levels)) {
			throw new Exception('unknown level: ' . json_encode(array_keys($levels)));
		}

		$level = $levels[strtolower($level)];

		$this->membersService->addMember($circleId, $userId, Member::TYPE_USER, true);
		$this->membersService->levelMember($circleId, $userId, Member::TYPE_USER, $level, true);

		$member = $this->membersRequest->forceGetMember($circleId, $userId, Member::TYPE_USER);
		echo json_encode($member, JSON_PRETTY_PRINT) . "\n";

		return 0;
	}

}

