<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Service;

use OCA\Circles\ConfigLexicon;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Db\MembershipRequest;
use OCA\Circles\Exceptions\InsufficientPermissionException;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\PermissionService;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class PermissionServiceTest extends TestCase {
	private IL10N&MockObject $l10n;
	private FederatedUserService&MockObject $federatedUserService;
	private ConfigService&MockObject $configService;
	private MemberRequest&MockObject $memberRequest;
	private MembershipRequest&MockObject $membershipRequest;
	private IGroupManager&MockObject $groupManager;
	private IUserSession&MockObject $userSession;
	private PermissionService $service;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(IL10N::class);
		$this->federatedUserService = $this->createMock(FederatedUserService::class);
		$this->configService = $this->createMock(ConfigService::class);
		$this->memberRequest = $this->createMock(MemberRequest::class);
		$this->membershipRequest = $this->createMock(MembershipRequest::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userSession = $this->createMock(IUserSession::class);

		$this->service = new PermissionService(
			$this->l10n,
			$this->federatedUserService,
			$this->configService,
			$this->memberRequest,
			$this->membershipRequest,
			$this->groupManager,
			$this->userSession,
		);
	}

	public function testCanUserCreateTeamsWhenNoRestrictions(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');

		$this->groupManager->method('isAdmin')->willReturn(false);
		$this->configService->method('getAppValue')
			->willReturnMap([
				[ConfigLexicon::TEAM_CREATION_ALLOWED_GROUPS, '[]'],
				[ConfigService::LIMIT_CIRCLE_CREATION, ''],
			]);

		$this->assertTrue($this->service->canUserCreateTeams($user));
	}

	public function testCanUserCreateTeamsDeniedForUnauthorizedGroup(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');

		$this->groupManager->method('isAdmin')->willReturn(false);
		$this->configService->method('getAppValue')
			->with(ConfigLexicon::TEAM_CREATION_ALLOWED_GROUPS)
			->willReturn('["team-creators"]');
		$this->groupManager->method('getUserGroupIds')
			->with($user)
			->willReturn(['users']);

		$this->assertFalse($this->service->canUserCreateTeams($user));
	}

	public function testConfirmCircleCreationDeniedForUnauthorizedGroup(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');

		$this->userSession->method('getUser')->willReturn($user);
		$this->groupManager->method('isAdmin')->willReturn(false);
		$this->configService->method('getAppValue')
			->with(ConfigLexicon::TEAM_CREATION_ALLOWED_GROUPS)
			->willReturn('["team-creators"]');
		$this->groupManager->method('getUserGroupIds')
			->with($user)
			->willReturn(['users']);
		$this->l10n->method('t')->willReturnArgument(0);

		$this->expectException(InsufficientPermissionException::class);
		$this->service->confirmCircleCreation();
	}

	public function testConfirmCircleCreationAllowedForMatchingGroup(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');

		$this->userSession->method('getUser')->willReturn($user);
		$this->groupManager->method('isAdmin')->willReturn(false);
		$this->configService->method('getAppValue')
			->willReturnMap([
				[ConfigLexicon::TEAM_CREATION_ALLOWED_GROUPS, '["team-creators"]'],
				[ConfigService::LIMIT_CIRCLE_CREATION, ''],
			]);
		$this->groupManager->method('getUserGroupIds')
			->with($user)
			->willReturn(['team-creators']);

		$this->service->confirmCircleCreation();
		$this->addToAssertionCount(1);
	}

	public function testCanUserCreateTeamsAlwaysAllowedForAdmin(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('admin');

		$this->groupManager->method('isAdmin')
			->with('admin')
			->willReturn(true);

		$this->assertTrue($this->service->canUserCreateTeams($user));
	}
}
