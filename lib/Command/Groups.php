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
use OCA\Circles\Db\DeprecatedCirclesRequest;
use OCA\Circles\Exceptions\CommandMissingArgumentException;
use OCA\Circles\Exceptions\FakeException;
use OCP\IL10N;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Groups extends Base {
	/** @var IL10N */
	private $l10n;

	/** @var DeprecatedCirclesRequest */
	private $circlesRequest;

	/**
	 * Groups constructor.
	 *
	 * @param IL10N $l10n
	 * @param DeprecatedCirclesRequest $circlesRequest
	 */
	public function __construct(IL10N $l10n, DeprecatedCirclesRequest $circlesRequest) {
		parent::__construct();
		$this->l10n = $l10n;
		$this->circlesRequest = $circlesRequest;
	}


	protected function configure() {
		parent::configure();
		$this->setName('circles:groups')
			 ->setDescription('manage the linked groups')
			 ->addOption('list', 'l', InputOption::VALUE_NONE, 'list all linked group')
			 ->addOption('link', 'a', InputOption::VALUE_NONE, 'link a group to a circle')
			 ->addOption('unlink', 'd', InputOption::VALUE_NONE, 'unlink a group from a circle')
			 ->addArgument('circle_id', InputArgument::OPTIONAL, 'id of the circle')
			 ->addArgument('group', InputArgument::OPTIONAL, 'name of the group');
	}


	protected function execute(InputInterface $input, OutputInterface $output) {
		try {
			$this->listLinkedGroups($input, $output);
			$this->addLinkedGroups($input, $output);
			$this->delLinkedGroups($input, $output);
		} catch (FakeException $e) {
			$output->writeln('done');
		} catch (Exception $e) {
			$output->writeln($e->getMessage());
		}
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @throws FakeException
	 */
	private function listLinkedGroups(InputInterface $input, OutputInterface $output) {
		if ($input->getOption('list') !== true) {
			return ;
		}

		throw new FakeException();
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @throws FakeException
	 * @throws CommandMissingArgumentException
	 */
	private function addLinkedGroups(InputInterface $input, OutputInterface $output) {
		if ($input->getOption('link') !== true) {
			return;
		}

		[$circleId, $group] = $this->getCircleIdAndGroupFromArguments($input);

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

		[$circleId, $group] = $this->getCircleIdAndGroupFromArguments($input);

		throw new FakeException();
	}


	private function getCircleIdAndGroupFromArguments(InputInterface $input) {
		if ($input->getArgument('circle_id') === null
			|| $input->getArgument('group') === null) {
			throw new CommandMissingArgumentException(

			);
//			$this->l10n->t(
//				'Missing argument: {cmd} circle_id group', ['cmd' => './occ circles:link']
//			)
		}

		return [$input->getArgument('circle_id'), $input->getArgument('group')];
	}
}
