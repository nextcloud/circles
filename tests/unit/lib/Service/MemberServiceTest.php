<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\Service;

use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\InsufficientPermissionException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Probes\MemberProbe;
use OCA\Circles\Service\FederatedEventService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use OCA\Circles\Service\MembershipService;
use OCA\Circles\Service\RemoteStreamService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUserManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MemberServiceTest extends TestCase {
	private CircleRequest&MockObject $circleRequest;
	private MemberRequest&MockObject $memberRequest;
	private FederatedUserService&MockObject $federatedUserService;
	private MemberService $service;

	protected function setUp(): void {
		$this->circleRequest = $this->createMock(CircleRequest::class);
		$this->memberRequest = $this->createMock(MemberRequest::class);
		$this->federatedUserService = $this->createMock(FederatedUserService::class);
		$this->service = new MemberService(
			$this->circleRequest,
			$this->memberRequest,
			$this->federatedUserService,
			$this->createMock(MembershipService::class),
			$this->createMock(FederatedEventService::class),
			$this->createMock(RemoteStreamService::class),
			$this->createMock(IEventDispatcher::class),
			$this->createMock(IUserManager::class),
		);
	}

	public function testRestrictedMembersAreNotQueried(): void {
		$circle = new Circle();
		$circle->setInitiator((new Member())->setLevel(Member::LEVEL_MEMBER));
		$circle->setSettings([Circle::SETTING_MEMBERS_VIEW_PERMISSION_LEVEL => '8']);
		$this->circleRequest->method('getCircle')->willReturn($circle);
		$this->memberRequest->expects($this->never())->method('getMembers');

		$this->expectException(InsufficientPermissionException::class);
		$this->service->getMembers('team', true, 5, 'alice', Member::LEVEL_ADMIN);
	}

	public function testPermittedMemberReadPreservesFilters(): void {
		$user = new FederatedUser();
		$this->federatedUserService->method('getCurrentEntity')->willReturn($user);
		$this->federatedUserService->method('getCurrentUser')->willReturn($user);
		$circle = new Circle();
		$circle->setInitiator((new Member())->setLevel(Member::LEVEL_MODERATOR));
		$circle->setSettings([Circle::SETTING_MEMBERS_VIEW_PERMISSION_LEVEL => '4']);
		$this->circleRequest->expects($this->once())->method('getCircle')
			->with('team', $user)->willReturn($circle);
		$members = [new Member()];
		$this->memberRequest->expects($this->once())->method('getMembers')
			->with('team', $user, $this->isInstanceOf(MemberProbe::class), 5, true, 'alice', Member::LEVEL_ADMIN)
			->willReturn($members);

		$this->assertSame($members, $this->service->getMembers('team', true, 5, 'alice', Member::LEVEL_ADMIN));
	}

	public static function trustedSessions(): array {
		return [
			'super session' => [true, false, false],
			'server admin' => [false, true, false],
			'server federation' => [false, false, true],
		];
	}

	#[DataProvider('trustedSessions')]
	public function testTrustedSessionsPreserveInternalAccess(bool $super, bool $admin, bool $remote): void {
		$this->federatedUserService->method('canBypassCurrentUserCondition')->willReturn($super);
		$this->federatedUserService->method('isInitiatedByAdmin')->willReturn($admin);
		$this->federatedUserService->method('hasRemoteInstance')->willReturn($remote);
		if ($remote) {
			$this->federatedUserService->method('getRemoteInstance')->willReturn($this->createMock(RemoteInstance::class));
		}
		$this->circleRequest->expects($this->never())->method('getCircle');
		$this->memberRequest->method('getMembers')->willReturn([]);

		$this->assertSame([], $this->service->getMembers('team'));
	}

	public function testFederatedUserCannotBypassVisibility(): void {
		$this->federatedUserService->method('hasRemoteInstance')->willReturn(true);
		$this->federatedUserService->method('hasCurrentEntity')->willReturn(true);
		$circle = new Circle();
		$circle->setInitiator((new Member())->setLevel(Member::LEVEL_MEMBER));
		$circle->setSettings([Circle::SETTING_MEMBERS_VIEW_PERMISSION_LEVEL => '4']);
		$this->circleRequest->method('getCircle')->willReturn($circle);

		$this->expectException(InsufficientPermissionException::class);
		$this->service->assertCanViewMembers('team');
	}
}
