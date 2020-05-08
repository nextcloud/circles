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
 * Class MembersLevel
 *
 * @package OCA\Circles\Command
 */
class MembersLevel extends Base {


	/** @var IL10N */
	private $l10n;

	/** @var IUserManager */
	private $userManager;

	/** @var MembersRequest */
	private $membersRequest;

	/** @var MembersService */
	private $membersService;


	/**
	 * MembersLevel constructor.
	 *
	 * @param IL10N $l10n
	 * @param IUserManager $userManager
	 * @param MembersRequest $membersRequest
	 * @param MembersService $membersService
	 */
	public function __construct(
		IL10N $l10n, IUserManager $userManager, MembersRequest $membersRequest, MembersService $membersService
	) {
		parent::__construct();
		$this->l10n = $l10n;
		$this->userManager = $userManager;
		$this->membersRequest = $membersRequest;
		$this->membersService = $membersService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:members:level')
			 ->setDescription('Change level of a member')
			 ->addArgument('member_id', InputArgument::REQUIRED, 'ID of the member')
			 ->addArgument('level', InputArgument::REQUIRED, 'new level');
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
		$memberId = $input->getArgument('member_id');
		$level = $input->getArgument('level');

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

		$member = $this->membersService->getMemberById($memberId);
		$this->membersService->levelMember(
			$member->getCircleId(), $member->getUserId(), Member::TYPE_USER, $member->getInstance(), $level,
			true
		);

		$member = $this->membersRequest->forceGetMember(
			$member->getCircleId(), $member->getUserId(), Member::TYPE_USER, $member->getInstance()
		);
		echo json_encode($member, JSON_PRETTY_PRINT) . "\n";

		return 0;
	}

}

