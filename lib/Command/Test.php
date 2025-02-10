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
			->setDescription('create a new circle');
//			->addArgument('owner', InputArgument::REQUIRED, 'owner of the circle')
//			->addArgument('name', InputArgument::REQUIRED, 'name of the circle')
//			->addOption('personal', '', InputOption::VALUE_NONE, 'create a personal circle')
//			->addOption('local', '', InputOption::VALUE_NONE, 'create a local circle')
//			->addOption('status-code', '', InputOption::VALUE_NONE, 'display status code on exception')
//			->addOption(
//				'type', '', InputOption::VALUE_REQUIRED, 'type of the owner',
//				Member::$TYPE[Member::TYPE_SINGLE]
//			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {

//		$team = $this->teamSession->performTeamOperation()->getTeam('KRyQQW3d5OinYfT');

		$entityOperation = $this->teamSession->performTeamEntityOperation();
		$team = $this->teamSession->performTeamOperation()->createTeam('this is a test 001', $entityOperation->getFromLocalUser('test3'));

		$teamOperation = $this->teamSession->performTeamOperation();
		$teams = $teamOperation->getTeams();

		$teamOperation = $this->teamSession->performTeamMembershipOperation();

		echo json_encode($teams, JSON_PRETTY_PRINT) . "\n";

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
