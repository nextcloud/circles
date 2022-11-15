<?php


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
use OCA\Circles\Service\DavService;
use OCA\DAV\CardDAV\CardDavBackend;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SyncContact
 *
 * @package OCA\Circles\Command
 */
class SyncContact extends Base {
	/** @var IUserManager */
	private $userManager;

	/** @var CardDavBackend */
	private $cardDavBackend;

	/** @var DavService */
	private $davService;


	/**
	 * Groups constructor.
	 *
	 * @param IUserManager $userManager
	 * @param CardDavBackend $cardDavBackend
	 * @param DavService $davService
	 */
	public function __construct(
		IUserManager $userManager, CardDavBackend $cardDavBackend, DavService $davService
	) {
		parent::__construct();

		$this->userManager = $userManager;
		$this->cardDavBackend = $cardDavBackend;
		$this->davService = $davService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:contacts:sync')
			 ->addOption('info', '', InputOption::VALUE_NONE, 'get info about contacts')
			 ->addOption('status', '', InputOption::VALUE_NONE, 'get info about sync')
			 ->setDescription('sync contacts, when using the Circles app as a backend of the Contact app');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int|void|null
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		if ($input->getOption('info')) {
			$this->displayInfo($output);

			return;
		}

		if ($input->getOption('status')) {
			$this->displayStatus($output);

			return;
		}

		$this->davService->migration();

		$output->writeln('migration done');
	}


	/**
	 * @param OutputInterface $output
	 */
	private function displayInfo(OutputInterface $output) {
		$users = $this->userManager->search('');

		$tCards = $tBooks = 0;
		$knownBooks = [];
		foreach ($users as $user) {
			$books = $this->cardDavBackend->getAddressBooksForUser('principals/users/' . $user->getUID());
			$output->writeln(
				'- User <info>' . $user->getUID() . '</info> have ' . sizeof($books) . ' address books:'
			);

			$tBooks += sizeof($books);
			foreach ($books as $book) {
				$bookId = $book['id'];
				$owner = $this->davService->getOwnerFromAddressBook($bookId);

				$cards = $this->cardDavBackend->getCards($bookId);

				if (!in_array($bookId, $knownBooks)) {
					$tCards += sizeof($cards);
				}

				$shared = '';
				if ($owner !== $user->getUID()) {
					$shared = ' (shared by <info>' . $owner . '</info>)';
				}

				$output->writeln(
					'  <comment>*</comment> book #' . $bookId . $shared . ' contains '
					. sizeof($cards)
					. ' entries'
				);

				$knownBooks[] = $bookId;
			}
		}

		$output->writeln('');
		$output->writeln('with a total of ' . $tBooks . ' address books and ' . $tCards . ' contact entries');
	}


	/**
	 * @param OutputInterface $output
	 */
	private function displayStatus(OutputInterface $output) {
		$output->writeln('not yet available');
	}
}
