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

use OC\Core\Command\Base;
use OCA\Circles\Db\DeprecatedCirclesRequest;
use OCA\Circles\Db\DeprecatedMembersRequest;
use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCP\IL10N;
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


	/** @var IL10N */
	private $l10n;

	/** @var DeprecatedCirclesRequest */
	private $circlesRequest;

	/** @var DeprecatedMembersRequest */
	private $membersRequest;


	/**
	 * MembersList constructor.
	 *
	 * @param IL10N $l10n
	 * @param DeprecatedCirclesRequest $circlesRequest
	 * @param DeprecatedMembersRequest $membersRequest
	 */
	public function __construct(IL10N $l10n, DeprecatedCirclesRequest $circlesRequest, DeprecatedMembersRequest $membersRequest) {
		parent::__construct();
		$this->l10n = $l10n;
		$this->circlesRequest = $circlesRequest;
		$this->membersRequest = $membersRequest;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:members:list')
			 ->setDescription('listing members')
			 ->addArgument('circle_id', InputArgument::REQUIRED, 'ID of the circle')
			 ->addOption('json', '', InputOption::VALUE_NONE, 'returns result as JSON');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws CircleDoesNotExistException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$circleId = $input->getArgument('circle_id');
		$json = $input->getOption('json');

		$this->circlesRequest->forceGetCircle($circleId);

		$members = $this->membersRequest->forceGetMembers($circleId);

		if ($json) {
			echo json_encode($members, JSON_PRETTY_PRINT) . "\n";

			return 0;
		}

		$output = new ConsoleOutput();
		$output = $output->section();

		$table = new Table($output);
		$table->setHeaders(['ID', 'Username', 'Instance', 'Level']);
		$table->render();
		$output->writeln('');

		$c = 0;
		foreach ($members as $member) {
			$table->appendRow(
				[
					$member->getMemberId(),
					$member->getUserId(),
					$member->getInstance(),
					$member->getLevelString(),
				]
			);
		}

		return 0;
	}

}

