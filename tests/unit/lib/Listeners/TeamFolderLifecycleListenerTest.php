<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\Listeners;

use OCA\Circles\Events\CreatingCircleEvent;
use OCA\Circles\Listeners\TeamFolderLifecycleListener;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Service\TeamFolderPolicy;
use OCP\Teams\ITeamFolderProvider;
use OCP\Teams\ITeamManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class TeamFolderLifecycleListenerTest extends TestCase {
	private TeamFolderLifecycleListener $listener;
	private ITeamManager&MockObject $teamManager;
	private TeamFolderPolicy&MockObject $policy;
	private ITeamFolderProvider&MockObject $provider;

	protected function setUp(): void {
		parent::setUp();

		$this->teamManager = $this->createMock(ITeamManager::class);
		$this->policy = $this->createMock(TeamFolderPolicy::class);
		$this->provider = $this->createMock(ITeamFolderProvider::class);

		$this->listener = new TeamFolderLifecycleListener(
			$this->teamManager,
			$this->policy,
			$this->createMock(LoggerInterface::class),
		);
	}

	public function testSkipsCreateWhenWizardDidNotRequestTeamFolder(): void {
		$event = $this->creatingEvent(false);

		$this->policy->expects($this->never())
			->method('shouldCreateTeamFolder');
		$this->teamManager->expects($this->never())
			->method('getTeamFolderProvider');

		$this->listener->handle($event);
	}

	public function testCreatesTeamFolderWhenRequested(): void {
		$event = $this->creatingEvent(true);

		$this->policy->expects($this->once())
			->method('shouldCreateTeamFolder')
			->with($this->anything())
			->willReturn(true);
		$this->teamManager->method('getTeamFolderProvider')->willReturn($this->provider);
		$this->policy->method('getDefaultQuota')->willReturn(0);
		$this->provider->expects($this->once())
			->method('createTeamFolder');

		$this->listener->handle($event);
	}

	public function testDefaultsToCreateWhenParamIsMissing(): void {
		$event = $this->creatingEvent(null);

		$this->policy->expects($this->once())
			->method('shouldCreateTeamFolder')
			->willReturn(true);
		$this->teamManager->method('getTeamFolderProvider')->willReturn($this->provider);
		$this->policy->method('getDefaultQuota')->willReturn(0);
		$this->provider->expects($this->once())
			->method('createTeamFolder');

		$this->listener->handle($event);
	}

	private function creatingEvent(?bool $createTeamFolder): CreatingCircleEvent {
		$circle = $this->createMock(Circle::class);
		$circle->method('getSingleId')->willReturn('team1');
		$circle->method('getDisplayName')->willReturn('Design');

		$federatedEvent = new FederatedEvent();
		$federatedEvent->setCircle($circle);
		if ($createTeamFolder !== null) {
			$federatedEvent->getParams()->sBool(TeamFolderPolicy::PARAM_CREATE_TEAM_FOLDER, $createTeamFolder);
		}

		return new CreatingCircleEvent($federatedEvent);
	}
}
