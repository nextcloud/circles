<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Command;

use OC\Core\Command\Base;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Service\TeamFolderPolicy;
use OCP\Teams\ITeamManager;
use OCP\Teams\Team;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Admin escape hatch to provision team spaces for existing teams.
 *
 * This bypasses the config.php gate (`circles.team_folder_auto_create`) that
 * disables Circles UI/API self-upgrade and auto-create on team creation.
 */
class CirclesTeamFolderUpgrade extends Base {
	public function __construct(
		private readonly CircleRequest $circleRequest,
		private readonly TeamFolderPolicy $policy,
		private readonly ITeamManager $teamManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this->setName('circles:team-folder:upgrade')
			->setDescription('Create a team space for an existing team (or all eligible teams)')
			->addArgument('circle_id', InputArgument::OPTIONAL, 'ID of the team to upgrade')
			->addOption('all', '', InputOption::VALUE_NONE, 'Upgrade all eligible teams without a team space');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$provider = $this->teamManager->getTeamFolderProvider();
		if ($provider === null) {
			$output->writeln('<error>No team folder provider is enabled (install/enable the Team Folders app).</error>');
			return 1;
		}

		$all = (bool)$input->getOption('all');
		$circleId = (string)($input->getArgument('circle_id') ?? '');

		if ($all === ($circleId !== '')) {
			$output->writeln('<error>Specify either a circle_id or --all.</error>');
			return 1;
		}

		$circles = $all ? $this->getEligibleCircles() : [$this->getCircleOrFail($circleId, $output)];
		if ($circles === []) {
			return $all ? 0 : 1;
		}

		$created = 0;
		$skipped = 0;
		$failed = 0;

		foreach ($circles as $circle) {
			if ($circle === null) {
				$failed++;
				continue;
			}

			if (!$this->policy->isEligibleCircle($circle)) {
				$output->writeln(sprintf('<comment>Skipping %s (%s): ineligible team type</comment>', $circle->getSingleId(), $circle->getDisplayName()));
				$skipped++;
				continue;
			}

			if ($provider->getTeamFolder($circle->getSingleId()) !== null) {
				$output->writeln(sprintf('<comment>Skipping %s (%s): team space already exists</comment>', $circle->getSingleId(), $circle->getDisplayName()));
				$skipped++;
				continue;
			}

			try {
				$folder = $provider->createTeamFolder(
					new Team(
						teamId: $circle->getSingleId(),
						displayName: $circle->getDisplayName(),
						link: null,
					),
					$this->policy->getDefaultQuota(),
				);
				$output->writeln(sprintf(
					'<info>Created team space for %s (%s) as folder #%d</info>',
					$circle->getSingleId(),
					$circle->getDisplayName(),
					$folder->getId(),
				));
				$created++;
			} catch (Throwable $e) {
				$output->writeln(sprintf(
					'<error>Failed for %s (%s): %s</error>',
					$circle->getSingleId(),
					$circle->getDisplayName(),
					$e->getMessage(),
				));
				$failed++;
			}
		}

		$output->writeln(sprintf('Done. created=%d skipped=%d failed=%d', $created, $skipped, $failed));
		return $failed > 0 ? 1 : 0;
	}

	/**
	 * @return list<Circle>
	 */
	private function getEligibleCircles(): array {
		$probe = new CircleProbe();
		$probe->includeNonVisibleCircles();
		/** @var list<Circle> $circles */
		$circles = $this->circleRequest->getCircles(null, $probe);
		return $circles;
	}

	private function getCircleOrFail(string $circleId, OutputInterface $output): ?Circle {
		try {
			return $this->circleRequest->getCircle($circleId);
		} catch (CircleNotFoundException) {
			$output->writeln(sprintf('<error>Team not found: %s</error>', $circleId));
			return null;
		}
	}
}
