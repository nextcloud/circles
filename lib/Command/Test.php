<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Command;

use OC\Core\Command\Base;
use OCA\Circles\Api\v2\TeamSession;
use OCA\Circles\Enum\TeamApi;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CirclesCreate
 *
 * @package OCA\Circles\Command
 */
class Test extends Base {
	public function __construct(
		private readonly TeamSession $teamSession,
	) {
		parent::__construct();
		define('USING_TEAMS_API', TeamApi::V2);
	}


	protected function configure() {
		parent::configure();
		$this->setName('teams:test')
			->setDescription('create a new circle')
//			->addArgument('owner', InputArgument::REQUIRED, 'owner of the circle')
//			->addArgument('name', InputArgument::REQUIRED, 'name of the circle')
			->addOption('reset', '', InputOption::VALUE_NONE, 'reset all data');
//			->addOption('local', '', InputOption::VALUE_NONE, 'create a local circle')
//			->addOption('status-code', '', InputOption::VALUE_NONE, 'display status code on exception')
//			->addOption(
//				'type', '', InputOption::VALUE_REQUIRED, 'type of the owner',
//				Member::$TYPE[Member::TYPE_SINGLE]
//			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
//		$team = $this->teamSession->performTeamOperation()->getTeam('KRyQQW3d5OinYfT');

		if ($input->getOption('reset')) {
			$session = $this->teamSession->sessionAsSuperAdmin();
			$session->performSuperOperation()->dropAllData();
			$output->writeln('reset');
			return 0;
		}
//
		$entityOperation = $this->teamSession->performTeamEntityOperation();
		$test3 = 			$entityOperation->getFromLocalUser('test3');

		$entityOperation = $this->teamSession->performTeamEntityOperation();
		$team1 = $this->teamSession->performTeamOperation()->createTeam(
			'this is a test 001',
			$entityOperation->getFromLocalUser('test3')
		);
		$team2 = $this->teamSession->performTeamOperation()->createTeam(
			'this is a test 002',
			$team1->asEntity()
		);
		$team3 = $this->teamSession->performTeamOperation()->createTeam(
			'this is a test 003',
			$team2->asEntity()
		);
		$team4 = $this->teamSession->performTeamOperation()->createTeam(
			'this is a test 001',
			$team3->asEntity()
		);
//
//		echo json_encode($team1, JSON_PRETTY_PRINT) ."\n";
//		echo json_encode($team2, JSON_PRETTY_PRINT) ."\n";
//		echo json_encode($team3, JSON_PRETTY_PRINT) ."\n";
//		echo json_encode($team4, JSON_PRETTY_PRINT) ."\n";

//		$teamOperation = $this->teamSession->performTeamOperation();
//		$teams = $teamOperation->getTeams();

//		return 0;

		$this->teamSession->performTeamMemberOperation()->addMember(
			$team4,
			$entityOperation->getFromLocalUser('test1')
		);


		echo json_encode($this->teamSession->performTeamOperation()->getTeam($team2->getSingleId()), JSON_PRETTY_PRINT) . "\n";
		$session = $this->teamSession->sessionAsSuperAdmin();
		$membershipOperation = $session->performTeamMembershipOperation();
		$membershipOperation->syncTeamMemberships($team1->getSingleId());

		//echo json_encode($teams, JSON_PRETTY_PRINT) . "\n";

		return 0;

		$ownerId = $input->getArgument('owner');
		$name = $input->getArgument('name');

		try {
			$this->federatedUserService->bypassCurrentUserCondition(true);

			$type = Member::parseTypeString($input->getOption('type'));

			$owner = $this->federatedUserService->getFederatedUser($ownerId, $type);
			$outcome = $this->circleService->create(
				$name,
				$owner,
				$input->getOption('personal'),
				$input->getOption('local')
			);
		} catch (FederatedItemException $e) {
			if ($input->getOption('status-code')) {
				throw new FederatedItemException(
					' [' . get_class($e) . ', ' . $e->getStatus() . ']' . "\n" . $e->getMessage()
				);
			}

			throw $e;
		}

		if (strtolower($input->getOption('output')) === 'json') {
			$output->writeln(json_encode($outcome, JSON_PRETTY_PRINT));
		} elseif (strtolower($input->getOption('output')) !== 'none') {
			/** @var Circle $circle */
			$circle = $this->deserialize($outcome, Circle::class);
			$output->writeln('Id: <info>' . $circle->getSingleId() . '</info>');
			$output->writeln('Name: <info>' . $circle->getDisplayName() . '</info>');
			$output->writeln('Owner: <info>' . $circle->getOwner()->getDisplayName() . '</info>');
		}

		return 0;
	}
}
