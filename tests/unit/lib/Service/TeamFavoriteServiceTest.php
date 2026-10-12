<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\Service;

use OCA\Circles\Repository\TeamFavoriteRepository;
use OCA\Circles\Service\PermissionService;
use OCA\Circles\Service\TeamFavoriteService;
use OCP\AppFramework\OCS\OCSException;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Lock\ILockingProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TeamFavoriteServiceTest extends TestCase {
	private TeamFavoriteRepository&MockObject $request;
	private PermissionService&MockObject $permissions;
	private ILockingProvider&MockObject $locks;
	private TeamFavoriteService $service;

	protected function setUp(): void {
		$this->request = $this->createMock(TeamFavoriteRepository::class);
		$this->permissions = $this->createMock(PermissionService::class);
		$this->locks = $this->createMock(ILockingProvider::class);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$this->service = new TeamFavoriteService($this->request, $this->permissions, $session, $this->locks);
	}

	public function testReadOnlyReturnsAccessibleFavorites(): void {
		$this->request->expects(self::once())->method('getCircleIds')->with('alice', true)->willReturn(['B']);
		$this->request->expects(self::never())->method('remove');
		self::assertSame(['B'], $this->service->getFavoriteCircleIds());
	}

	public function testReorderPrunesFormerMembershipAndReturnsCanonicalOrder(): void {
		$this->request->method('getCircleIds')->willReturnOnConsecutiveCalls(['gone', 'A', 'B'], ['A', 'B'], ['B', 'A']);
		$this->request->expects(self::once())->method('remove')->with('alice', 'gone');
		$this->request->expects(self::once())->method('replaceOrder')->with('alice', ['B', 'A']);
		$this->permissions->expects(self::never())->method('userMustBeMember');
		$this->expectLockReleased();
		self::assertSame(['B', 'A'], $this->service->reorder(['B', 'A'], ['A', 'B']));
	}

	public function testStaleOrderIsRejectedWithoutOverwritingAnotherTab(): void {
		$this->request->method('getCircleIds')->willReturn(['A', 'B', 'C']);
		$this->request->expects(self::never())->method('replaceOrder');
		$this->expectLockReleased();
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(409);
		$this->service->reorder(['B', 'A'], ['A', 'B']);
	}

	public function testChangedOrderWithSameFavoritesIsRejected(): void {
		$this->request->method('getCircleIds')->willReturn(['B', 'A']);
		$this->request->expects(self::never())->method('replaceOrder');
		$this->expectLockReleased();
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(409);
		$this->service->reorder(['B', 'A'], ['A', 'B']);
	}

	public function testDuplicateIdsAreRejectedBeforeAccessingDatabase(): void {
		$this->request->expects(self::never())->method('getCircleIds');
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(400);
		$this->service->reorder(['A', 'A'], ['A', 'B']);
	}

	public function testUnchangedOrderDoesNotWrite(): void {
		$this->request->method('getCircleIds')->willReturn(['A']);
		$this->request->expects(self::never())->method('replaceOrder');
		$this->expectLockReleased();
		self::assertSame(['A'], $this->service->reorder(['A'], ['A']));
	}

	public function testAlreadyFavoriteIsIdempotent(): void {
		$this->request->method('getCircleIds')->willReturn(['A']);
		$this->request->expects(self::never())->method('add');
		$this->expectLockReleased();
		self::assertSame(['A'], $this->service->setFavorite('A', true));
	}

	public function testLockIsReleasedOnDatabaseFailure(): void {
		$this->request->method('getCircleIds')->willReturn(['A', 'B']);
		$this->request->method('replaceOrder')->willThrowException(new \RuntimeException('database unavailable'));
		$this->expectLockReleased();
		$this->expectException(\RuntimeException::class);
		$this->service->reorder(['B', 'A'], ['A', 'B']);
	}

	private function expectLockReleased(): void {
		$lock = 'circles:team-favorites:' . hash('sha256', 'alice');
		$this->locks->expects(self::once())->method('acquireLock')->with($lock, ILockingProvider::LOCK_EXCLUSIVE);
		$this->locks->expects(self::once())->method('releaseLock')->with($lock, ILockingProvider::LOCK_EXCLUSIVE);
	}
}
