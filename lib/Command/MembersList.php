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

use OC\Core\Command\Base;
use OC\User\NoUserException;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\ViewerNotFoundException;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\CurrentUserService;
use OCA\Circles\Service\MemberService;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class MembersList
 *
 * @package OCA\Circles\Command
 */
class MembersList extends Base {


	/** @var CurrentUserService */
	private $currentUserService;

	/** @var CircleService */
	private $circleService;

	/** @var MemberService */
	private $memberService;

	/** @var ConfigService */
	private $configService;


	/**
	 * MembersList constructor.
	 *
	 * @param CurrentUserService $currentUserService
	 * @param CircleService $circleService
	 * @param MemberService $memberService
	 * @param ConfigService $configService
	 */
	public function __construct(
		CurrentUserService $currentUserService, CircleService $circleService, MemberService $memberService,
		ConfigService $configService
	) {
		parent::__construct();
		$this->currentUserService = $currentUserService;
		$this->circleService = $circleService;
		$this->memberService = $memberService;
		$this->configService = $configService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:members:list')
			 ->setDescription('listing members')
			 ->addArgument('circle_id', InputArgument::REQUIRED, 'ID of the circle')
			 ->addOption('json', '', InputOption::VALUE_NONE, 'returns result as JSON')
			 ->addOption('viewer', '', InputOption::VALUE_REQUIRED, 'add a viewer to the request', '');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws CircleNotFoundException
	 * @throws ViewerNotFoundException
	 * @throws NoUserException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$circleId = $input->getArgument('circle_id');
		$json = $input->getOption('json');

		$this->currentUserService->commandLineViewer($input->getOption('viewer'), true);
		$this->circleService->getCircle($circleId);
		$members = $this->memberService->getMembers($circleId);

		if ($json) {
			echo json_encode($members, JSON_PRETTY_PRINT) . "\n";

			return 0;
		}

		$output = new ConsoleOutput();
		$output = $output->section();

		$table = new Table($output);
		$table->setHeaders(['ID', 'Username', 'Instance', 'Level']);
		$table->render();

		$local = $this->configService->getLocalInstance();
		foreach ($members as $member) {
			$table->appendRow(
				[
					$member->getId(),
					$member->getUserId(),
					($member->getInstance() === $local) ? '' : $member->getInstance(),
					Member::$DEF_LEVEL[$member->getLevel()]
				]
			);
		}

		return 0;
	}

}

