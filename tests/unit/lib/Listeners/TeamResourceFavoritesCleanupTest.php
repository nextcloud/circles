<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\Listeners;

use OCA\Circles\Events\DestroyingCircleEvent;
use OCA\Circles\Events\MembershipsRemovedEvent;
use OCA\Circles\Listeners\TeamResourceFavoritesCleanup;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Membership;
use OCA\Circles\Service\FederatedUserService;
use OCP\Config\IUserConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class TeamResourceFavoritesCleanupTest extends TestCase {
	private IUserConfig&MockObject $userConfig;
	private FederatedUserService&MockObject $federatedUserService;
	private TeamResourceFavoritesCleanup $listener;

	protected function setUp(): void {
		$this->userConfig = $this->createMock(IUserConfig::class);
		$this->federatedUserService = $this->createMock(FederatedUserService::class);
		$this->listener = new TeamResourceFavoritesCleanup(
			$this->userConfig,
			$this->federatedUserService,
			$this->createMock(LoggerInterface::class),
		);
	}

	public function testDestroyedTeamDropsTheKeyForAllUsers(): void {
		$circle = $this->createMock(Circle::class);
		$circle->method('getSingleId')->willReturn('team-a');
		$event = $this->createMock(DestroyingCircleEvent::class);
		$event->method('getCircle')->willReturn($circle);

		$this->userConfig->expects(self::once())->method('deleteKey')->with('circles', 'teamsFavoriteResources_team-a');

		$this->listener->handle($event);
	}

	public function testRemovedMembershipDropsTheLocalUsersKey(): void {
		$membership = $this->createMock(Membership::class);
		$membership->method('getSingleId')->willReturn('single-alice');
		$membership->method('getCircleId')->willReturn('team-a');
		$user = $this->createMock(FederatedUser::class);
		$user->method('getUserType')->willReturn(Member::TYPE_USER);
		$user->method('isLocal')->willReturn(true);
		$user->method('getUserId')->willReturn('alice');
		$this->federatedUserService->method('getFederatedUser')->with('single-alice')->willReturn($user);

		$this->userConfig->expects(self::once())->method('deleteUserConfig')->with('alice', 'circles', 'teamsFavoriteResources_team-a');

		$this->listener->handle(new MembershipsRemovedEvent([$membership]));
	}

	public function testRemovedMembershipOfANestedTeamIsIgnored(): void {
		$membership = $this->createMock(Membership::class);
		$membership->method('getSingleId')->willReturn('single-team-b');
		$team = $this->createMock(FederatedUser::class);
		$team->method('getUserType')->willReturn(Member::TYPE_CIRCLE);
		$this->federatedUserService->method('getFederatedUser')->willReturn($team);

		$this->userConfig->expects(self::never())->method('deleteUserConfig');

		$this->listener->handle(new MembershipsRemovedEvent([$membership]));
	}
}
