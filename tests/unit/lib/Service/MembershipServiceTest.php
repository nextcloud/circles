<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\Service;

use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Db\MembershipRequest;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Membership;
use OCA\Circles\Service\EventService;
use OCA\Circles\Service\MembershipService;
use OCA\Circles\Service\OutputService;
use OCA\Circles\Service\ShareWrapperService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MembershipServiceTest extends TestCase {
	private MemberRequest&MockObject $memberRequest;
	private MembershipService $service;
	/** @var string[][] */
	private array $queriedSingleIds = [];

	protected function setUp(): void {
		$this->memberRequest = $this->createMock(MemberRequest::class);
		$this->service = new MembershipService(
			$this->createMock(MembershipRequest::class),
			$this->createMock(CircleRequest::class),
			$this->memberRequest,
			$this->createMock(EventService::class),
			$this->createMock(ShareWrapperService::class),
			$this->createMock(OutputService::class),
		);
	}

	public function testUserWithoutMembershipsGetsNothing(): void {
		$this->mockGraph([]);

		self::assertSame([], $this->generate('user'));
	}

	public function testDirectMemberships(): void {
		$this->mockGraph([
			'user' => [['A', Member::LEVEL_MEMBER], ['B', Member::LEVEL_ADMIN], ['C', Member::LEVEL_OWNER]],
		]);

		$memberships = $this->generate('user');

		self::assertEqualsCanonicalizing(['A', 'B', 'C'], array_keys($memberships));
		self::assertSame(Member::LEVEL_ADMIN, $memberships['B']->getLevel());
		self::assertSame(1, $memberships['B']->getInheritanceDepth());
		self::assertSame('user', $memberships['B']->getInheritanceFirst());
		self::assertSame('B', $memberships['B']->getInheritanceLast());
		self::assertSame(['user'], $memberships['B']->getInheritancePath());
	}

	public function testNestedChainKeepsPathsDepthsAndLevelsOfEachHop(): void {
		$this->mockGraph([
			'user' => [['A', Member::LEVEL_ADMIN]],
			'A' => [['B', Member::LEVEL_MEMBER]],
			'B' => [['C', Member::LEVEL_MODERATOR]],
			'C' => [['D', Member::LEVEL_MEMBER]],
		]);

		$memberships = $this->generate('user');

		self::assertEqualsCanonicalizing(['A', 'B', 'C', 'D'], array_keys($memberships));

		$expected = [
			'A' => [Member::LEVEL_ADMIN, 1, 'user', 'A', ['user']],
			'B' => [Member::LEVEL_MEMBER, 2, 'A', 'A', ['A', 'user']],
			'C' => [Member::LEVEL_MODERATOR, 3, 'B', 'A', ['B', 'A', 'user']],
			'D' => [Member::LEVEL_MEMBER, 4, 'C', 'A', ['C', 'B', 'A', 'user']],
		];
		foreach ($expected as $circleId => [$level, $depth, $first, $last, $path]) {
			self::assertSame($level, $memberships[$circleId]->getLevel(), "level of $circleId");
			self::assertSame($depth, $memberships[$circleId]->getInheritanceDepth(), "depth of $circleId");
			self::assertSame($first, $memberships[$circleId]->getInheritanceFirst(), "first of $circleId");
			self::assertSame($last, $memberships[$circleId]->getInheritanceLast(), "last of $circleId");
			self::assertSame($path, $memberships[$circleId]->getInheritancePath(), "path of $circleId");
		}
	}

	public function testCircularAndSelfMembershipsTerminate(): void {
		$this->mockGraph([
			'user' => [['A', Member::LEVEL_MEMBER]],
			'A' => [['A', Member::LEVEL_MEMBER], ['B', Member::LEVEL_MEMBER]],
			'B' => [['A', Member::LEVEL_MEMBER]],
		]);

		$memberships = $this->generate('user');

		self::assertEqualsCanonicalizing(['A', 'B'], array_keys($memberships));
		self::assertSame(1, $memberships['A']->getInheritanceDepth());
		self::assertSame(2, $memberships['B']->getInheritanceDepth());
	}

	public function testDiamondIsExpandedOnceAndKeepsFirstPathForDescendants(): void {
		$this->mockGraph([
			'user' => [['A', Member::LEVEL_MEMBER], ['B', Member::LEVEL_MEMBER]],
			'A' => [['D', Member::LEVEL_MEMBER]],
			'B' => [['D', Member::LEVEL_MEMBER]],
			'D' => [['E', Member::LEVEL_MEMBER]],
		]);

		$memberships = $this->generate('user');

		self::assertEqualsCanonicalizing(['A', 'B', 'D', 'E'], array_keys($memberships));
		self::assertSame([['user'], ['A', 'B'], ['D'], ['E']], $this->queriedSingleIds);

		self::assertSame(2, $memberships['D']->getInheritanceDepth());
		self::assertSame('A', $memberships['D']->getInheritanceFirst());
		self::assertSame(['A', 'user'], $memberships['D']->getInheritancePath());

		self::assertSame(3, $memberships['E']->getInheritanceDepth());
		self::assertSame('D', $memberships['E']->getInheritanceFirst());
		self::assertSame('A', $memberships['E']->getInheritanceLast());
		self::assertSame(['D', 'A', 'user'], $memberships['E']->getInheritancePath());
	}

	/**
	 * @param array<string, array<array{string, int}>> $graph single id => [circle id, level] it belongs to
	 */
	private function mockGraph(array $graph): void {
		$membersOf = function (string $singleId) use ($graph): array {
			return array_map(function (array $edge) use ($singleId): Member {
				$circle = new Circle();
				$circle->setSingleId($edge[0]);

				$member = new Member();
				$member->setSingleId($singleId);
				$member->setCircleId($edge[0]);
				$member->setLevel($edge[1]);
				$member->setCircle($circle);

				return $member;
			}, $graph[$singleId] ?? []);
		};

		$this->memberRequest->method('getMembersBySingleIds')
			->willReturnCallback(function (array $singleIds) use ($membersOf): array {
				$this->queriedSingleIds[] = $singleIds;

				return array_merge(...array_map($membersOf, $singleIds));
			});
	}

	/**
	 * @return array<string, Membership>
	 */
	private function generate(string $singleId): array {
		return (new \ReflectionMethod($this->service, 'generateMemberships'))->invoke($this->service, $singleId);
	}
}
