<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\FederatedItems;

use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\FederatedItems\CircleLeave;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\EventService;
use OCA\Circles\Service\MembershipService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CircleLeaveTest extends TestCase {
	public function testMemberWinsOverVisitorInBothOrders(): void {
		$visitor = $this->createMember(Member::LEVEL_VISITOR, 1);
		$member = $this->createMember(Member::LEVEL_MEMBER, 10);

		foreach ([[$visitor, $member], [$member, $visitor]] as $candidates) {
			$this->assertSame($member, $this->selectNewOwner($candidates));
		}
	}

	public function testModeratorAndAdminOutrankVisitorAndMember(): void {
		$visitor = $this->createMember(Member::LEVEL_VISITOR, 1);
		$member = $this->createMember(Member::LEVEL_MEMBER, 2);
		$moderator = $this->createMember(Member::LEVEL_MODERATOR, 3);
		$admin = $this->createMember(Member::LEVEL_ADMIN, 4);

		$this->assertSame($moderator, $this->selectNewOwner([$visitor, $member, $moderator]));
		$this->assertSame($admin, $this->selectNewOwner([$visitor, $member, $moderator, $admin]));
	}

	public function testEqualRoleKeepsEarlierJoinedMember(): void {
		$later = $this->createMember(Member::LEVEL_MEMBER, 20);
		$earlier = $this->createMember(Member::LEVEL_MEMBER, 10);

		$this->assertSame($earlier, $this->selectNewOwner([$later, $earlier]));
	}

	public function testEqualRolePrefersLocalMemberWhenJoinedDatesMatch(): void {
		$remote = $this->createMember(Member::LEVEL_MEMBER, 10, 'remote.example');
		$local = $this->createMember(Member::LEVEL_MEMBER, 10, 'local.example');

		$this->assertSame($local, $this->selectNewOwner([$remote, $local], ['local.example']));
	}

	public function testVisitorCanSucceedWhenItIsTheOnlyRemainingMember(): void {
		$owner = $this->createMember(Member::LEVEL_OWNER, 1);
		$visitor = $this->createMember(Member::LEVEL_VISITOR, 2);

		$this->assertSame($visitor, $this->selectNewOwner([$owner, $visitor]));
	}

	public function testUnknownRoleRanksBelowVisitorAndMember(): void {
		$unknown = $this->createMember(73, 1);
		$visitor = $this->createMember(Member::LEVEL_VISITOR, 2);
		$member = $this->createMember(Member::LEVEL_MEMBER, 3);

		$this->assertSame($visitor, $this->selectNewOwner([$unknown, $visitor]));
		$this->assertSame($member, $this->selectNewOwner([$unknown, $member]));
	}

	public function testNoCandidateStillThrows(): void {
		$this->expectException(MemberNotFoundException::class);
		$this->selectNewOwner([$this->createMember(Member::LEVEL_OWNER, 1)]);
	}

	private function selectNewOwner(array $members, array $localInstances = []): Member {
		$memberRequest = $this->createMock(MemberRequest::class);
		$memberRequest->method('getMembers')->with('circle-single')->willReturn($members);
		$configService = $this->createMock(ConfigService::class);
		$configService->method('isLocalInstance')->willReturnCallback(
			static fn (string $instance): bool => in_array($instance, $localInstances, true)
		);
		$handler = new CircleLeave(
			$memberRequest,
			$this->createMock(CircleRequest::class),
			$this->createMock(MembershipService::class),
			$this->createMock(EventService::class),
			$configService,
		);
		$method = (new ReflectionClass(CircleLeave::class))->getMethod('selectNewOwner');

		return $method->invoke($handler, (new Circle())->setSingleId('circle-single'));
	}

	private function createMember(int $level, int $joined, string $instance = ''): Member {
		return (new Member())
			->setId('member-' . $level . '-' . $joined . '-' . $instance)
			->setSingleId('single-' . $level . '-' . $joined . '-' . $instance)
			->setLevel($level)
			->setJoined($joined)
			->setInstance($instance);
	}
}
