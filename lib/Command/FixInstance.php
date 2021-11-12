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

use OC\Core\Command\Base;
use OCA\Circles\Db\MembersRequest;
use OCA\Circles\Model\Member;
use OCP\IDBConnection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;


/**
 *
 */
class FixInstance extends Base {

	/** @var IDBConnection */
	protected $connection;

	/** @var MembersRequest */
	private $membersRequest;


	/** @var InputInterface */
	private $input;

	/** @var OutputInterface */
	private $output;


	/**
	 * @param MembersRequest $membersRequest
	 * @param IDBConnection $connection
	 */
	public function __construct(
		MembersRequest $membersRequest,
		IDBConnection $connection
	) {
		parent::__construct();

		$this->membersRequest = $membersRequest;
		$this->connection = $connection;
	}

	protected function configure() {
		parent::configure();
		$this->setName('circles:fix:instance-alias')
			 ->setDescription('fix Instance aliases issue.')
			 ->addOption('fix', '', InputOption::VALUE_NONE, 'fix for real');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;
		$faulties = $this->getFaultyMembers();

		$output->writeln('Found ' . sizeof($faulties) . ' faulty entries');

		foreach ($faulties as $faulty) {
			$output->writeln('');
			$output->writeln(
				'> <info>' . $faulty->getUserId() . '</info> in <info>' . $faulty->getCircleId()
				. '</info> with instance=<info>' . $faulty->getInstance() . '</info>'
			);

			$dupes = $this->getDuplicates($faulty);
			if (sizeof($dupes) === 0) {
				$this->fixInstance($faulty);
			} else {
				$this->deleteDupe($faulty, $dupes);
			}
		}

		return 0;
	}


	/**
	 * @return Member[]
	 */
	private function getFaultyMembers() {
		$qb = $this->membersRequest->getMembersSelectSql();

		$expr = $qb->expr();
		$qb->andWhere(
			$expr->neq('instance', $qb->createNamedParameter('')),
			$expr->neq($qb->createFunction('POSITION(\'@\' IN instance)'), $qb->createNamedParameter(0))
		);

		$members = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$members[] = $this->membersRequest->parseMembersSelectSql($data);
		}
		$cursor->closeCursor();

		return $members;
	}


	/**
	 * @param Member $faulty
	 *
	 * @return array
	 * @throws \OCP\DB\Exception
	 */
	private function getDuplicates(Member $faulty) {
		$qb = $this->membersRequest->getMembersSelectSql();

		$expr = $qb->expr();
		$qb->andWhere(
			$expr->neq('instance', $qb->createNamedParameter($faulty->getInstance())),
			$expr->eq('circle_id', $qb->createNamedParameter($faulty->getCircleId())),
			$expr->eq('user_type', $qb->createNamedParameter($faulty->getType())),
			$expr->eq('user_id', $qb->createNamedParameter($faulty->getUserId()))
		);

		$dupes = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$dupes[] = $this->membersRequest->parseMembersSelectSql($data);
		}
		$cursor->closeCursor();

		return $dupes;
	}


	private function fixInstance(Member $faulty) {
		[, $fixed] = explode('@', $faulty->getInstance(), 2);
		$this->output->writeln('  - found no dupe, fixing instance to <info>' . $fixed . '</info>');

		$question = new ConfirmationQuestion(
			'<comment>Do you really want to ?</comment> (y/N) ', false,
			'/^(y|Y)/i'
		);

		$helper = $this->getHelper('question');
		if (!$helper->ask($this->input, $this->output, $question)) {
			$this->output->writeln('aborted.');

			return;
		}

		$qb = $this->membersRequest->getMembersUpdateSql(
			$faulty->getCircleId(),
			$faulty->getUserId(),
			$faulty->getInstance(),
			$faulty->getType()
		);
		$qb->set('instance', $qb->createNamedParameter($fixed));

		if ($this->input->getOption('fix')) {
			$qb->execute();
		}
	}


	/**
	 * @param Member $faulty
	 * @param Member[] $dupes
	 */
	private function deleteDupe(Member $faulty, $dupes) {
		if (sizeof($dupes) > 1) {
			$this->output->writeln('  - <error>2 many dupes, please fix manually</error>');

			return;
		}

		$dupe = array_shift($dupes);

		$removeFaulty = false;
		if ($dupe->getInstance() === '') {
			$this->confirmDeleteDupe($faulty, $dupe);

			return;
		}

		[, $fixed] = explode('@', $faulty->getInstance(), 2);
		if ($dupe->getInstance() === $fixed) {
			$this->confirmDeleteDupe($faulty, $dupe, $fixed);

			return;
		}

		$this->output->writeln(
			'  - <error>could not identify instance ' . $dupe->getInstance() . ', please fix manually</error>'
		);
	}


	private function confirmDeleteDupe(Member $faulty, Member $dupe, $fixed = '') {
		if ($fixed === '') {
			$msg = 'dupe is local';
		} else {
			$msg = 'dupe instance is <info>' . $dupe->getInstance() . '</info>';
		}

		$this->output->writeln(
			'  - ' . $msg . '. removing faulty with instance=<info>' . $faulty->getInstance() . '</info>'
		);

		$question = new ConfirmationQuestion(
			'<comment>Do you really want to ?</comment> (y/N) ', false,
			'/^(y|Y)/i'
		);

		$helper = $this->getHelper('question');
		if (!$helper->ask($this->input, $this->output, $question)) {
			$this->output->writeln('aborted.');

			return;
		}

		$qb = $this->membersRequest->getMembersDeleteSql();
		$expr = $qb->expr();
		$qb->andWhere(
			$expr->eq('instance', $qb->createNamedParameter($faulty->getInstance())),
			$expr->eq('circle_id', $qb->createNamedParameter($faulty->getCircleId())),
			$expr->eq('user_type', $qb->createNamedParameter($faulty->getType())),
			$expr->eq('user_id', $qb->createNamedParameter($faulty->getUserId()))
		);

		if ($this->input->getOption('fix')) {
			$qb->execute();
		}
	}
}



