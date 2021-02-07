<?php

declare(strict_types=1);


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
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class MembersLevel
 *
 * @package OCA\Circles\Command
 */
class MembersLevel extends Base {


	private $memberRequest;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var MemberService */
	private $memberService;


	/**
	 * MembersLevel constructor.
	 *
	 * @param MemberRequest $memberRequest
	 * @param FederatedUserService $federatedUserService
	 * @param MemberService $memberService
	 */
	public function __construct(
		MemberRequest $memberRequest, FederatedUserService $federatedUserService, MemberService $memberService
	) {
		parent::__construct();

		$this->memberRequest = $memberRequest;
		$this->federatedUserService = $federatedUserService;
		$this->memberService = $memberService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:members:level')
			 ->setDescription('Change the level of a member from a Circle')
			 ->addArgument('member_id', InputArgument::REQUIRED, 'ID of the member from the Circle')
			 ->addOption('initiator', '', InputOption::VALUE_REQUIRED, 'set an initiator to the request', '')
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

		$member = $this->memberRequest->getMember($memberId);
		$this->federatedUserService->commandLineInitiator(
			$input->getOption('initiator'), $member->getCircleId()
		);

		$level = Member::parseLevelString($input->getArgument('level'));
		$outcome = $this->memberService->memberLevel($memberId, $level);

		echo json_encode($outcome, JSON_PRETTY_PRINT) . "\n";

		return 0;
	}

}

