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
use OCA\Circles\Db\MembersRequest;
use OCA\Circles\Exceptions\MemberDoesNotExistException;
use OCP\IL10N;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class MembersDetails
 *
 * @package OCA\Circles\Command
 */
class MembersDetails extends Base {


	/** @var IL10N */
	private $l10n;

	/** @var MembersRequest */
	private $membersRequest;


	/**
	 * MembersDetails constructor.
	 *
	 * @param IL10N $l10n
	 * @param MembersRequest $membersRequest
	 */
	public function __construct(IL10N $l10n, MembersRequest $membersRequest) {
		parent::__construct();
		$this->l10n = $l10n;
		$this->membersRequest = $membersRequest;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:members:details')
			 ->setDescription('get details about a member by its ID')
			 ->addArgument('member_id', InputArgument::REQUIRED, 'ID of the member');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws MemberDoesNotExistException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$memberId = $input->getArgument('member_id');

		$member = $this->membersRequest->forceGetMemberById($memberId);
		echo json_encode($member, JSON_PRETTY_PRINT) . "\n";

		return 0;
	}

}

