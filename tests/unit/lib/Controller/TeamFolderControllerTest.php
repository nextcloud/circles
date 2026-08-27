<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\Controller;

use OCA\Circles\Controller\TeamFolderController;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Model\Circle;
use OCA\Circles\Service\PermissionService;
use OCA\Circles\Service\TeamFolderPolicy;
use OCP\AppFramework\OCS\OCSException;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Teams\ITeamManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TeamFolderControllerTest extends TestCase {
	private TeamFolderController $controller;
	private TeamFolderPolicy&MockObject $policy;
	private ITeamManager&MockObject $teamManager;
	private CircleRequest&MockObject $circleRequest;
	private PermissionService&MockObject $permissionService;
	private IUserSession&MockObject $userSession;

	protected function setUp(): void {
		parent::setUp();

		$this->policy = $this->createMock(TeamFolderPolicy::class);
		$this->teamManager = $this->createMock(ITeamManager::class);
		$this->circleRequest = $this->createMock(CircleRequest::class);
		$this->permissionService = $this->createMock(PermissionService::class);
		$this->userSession = $this->createMock(IUserSession::class);

		$this->controller = new TeamFolderController(
			'circles',
			$this->createMock(IRequest::class),
			$this->teamManager,
			$this->policy,
			$this->circleRequest,
			$this->permissionService,
			$this->userSession,
		);
	}

	public function testUpgradeTeamFolderForbiddenWhenProvisioningDisabled(): void {
		$this->policy->method('isTeamFolderProvisioningEnabled')->willReturn(false);

		$this->expectException(OCSException::class);
		$this->expectExceptionMessage('Team space provisioning is disabled');

		$this->controller->upgradeTeamFolder('team1');
	}

	public function testUpgradeTeamFolderForbiddenForIneligibleCircle(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('admin');
		$this->userSession->method('getUser')->willReturn($user);

		$circle = $this->createMock(Circle::class);
		$this->policy->method('isTeamFolderProvisioningEnabled')->willReturn(true);
		$this->policy->method('isEligibleCircle')->with($circle)->willReturn(false);
		$this->circleRequest->method('getCircle')->with('team1')->willReturn($circle);

		$this->expectException(OCSException::class);
		$this->expectExceptionMessage('This team cannot have a team space');

		$this->controller->upgradeTeamFolder('team1');
	}
}
