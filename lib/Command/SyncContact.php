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
use OCA\Circles\Exceptions\CommandMissingArgumentException;
use OCA\Circles\Exceptions\FakeException;
use OCA\Circles\Service\DavService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class SyncContact
 *
 * @package OCA\Circles\Command
 */
class SyncContact extends Base {


	/** @var DavService */
	private $davService;


	/**
	 * Groups constructor.
	 *
	 * @param DavService $davService
	 */
	public function __construct(DavService $davService) {
		parent::__construct();

		$this->davService = $davService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:contacts:sync')
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
		$this->davService->migration();

		$output->writeln('migration done');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @throws FakeException
	 */
	private function listLinkedGroups(InputInterface $input, OutputInterface $output) {
		if ($input->getOption('list') !== true) {
			return;
		}

		throw new FakeException();
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @throws FakeException
	 */
	private function addLinkedGroups(InputInterface $input, OutputInterface $output) {
		if ($input->getOption('link') !== true) {
			return;
		}

		list($circleId, $group) = $this->getCircleIdAndGroupFromArguments($input);

		throw new FakeException();
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @throws FakeException
	 */
	private function delLinkedGroups(InputInterface $input, OutputInterface $output) {
		if ($input->getOption('unlink') !== true) {
			return;
		}

		list($circleId, $group) = $this->getCircleIdAndGroupFromArguments($input);

		throw new FakeException();
	}


	private function getCircleIdAndGroupFromArguments(InputInterface $input) {
		if ($input->getArgument('circle_id') === null
			|| $input->getArgument('group') === null) {
			throw new CommandMissingArgumentException();
//			$this->l10n->t(
//				'Missing argument: {cmd} circle_id group', ['cmd' => './occ circles:link']
//			)
		}

		return [$input->getArgument('circle_id'), $input->getArgument('group')];
	}

}

