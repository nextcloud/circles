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
use OCA\Circles\Service\EventService;
use OCA\Circles\Service\MembershipService;
use OCA\Circles\Service\OutputService;
use OCA\Circles\Service\ShareWrapperService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MembershipServiceTest extends TestCase {
	private MemberRequest&MockObject $memberRequest;
	private MembershipRequest&MockObject $membershipRequest;
	private ShareWrapperService&MockObject $shareWrapperService;
	private MembershipService $service;

	protected function setUp(): void {
		$this->memberRequest = $this->createMock(MemberRequest::class);
		$this->membershipRequest = $this->createMock(MembershipRequest::class);
		$this->shareWrapperService = $this->createMock(ShareWrapperService::class);
		$this->service = new MembershipService(
			$this->membershipRequest,
			$this->createMock(CircleRequest::class),
			$this->memberRequest,
			$this->createMock(EventService::class),
			$this->shareWrapperService,
			$this->createMock(OutputService::class),
		);
	}

	public function testNewMembershipClearsSharedWithCache(): void {
		$this->mockGraph([
			'user' => [['A', Member::LEVEL_MEMBER]],
		]);
		$this->membershipRequest->expects(self::once())->method('insert');

		$cleared = [];
		$this->shareWrapperService->method('clearCacheList')
			->willReturnCallback(function (array $clear) use (&$cleared): void {
				$cleared = array_merge($cleared, $clear);
			});

		self::assertSame(1, $this->service->manageMemberships('user'));
		self::assertSame(['user'], $cleared);
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

		$this->memberRequest->method('getMembersBySingleId')
			->willReturnCallback($membersOf);
	}
}
