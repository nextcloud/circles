<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Service;

use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedEventService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use OCA\Circles\Service\PermissionService;
use OCA\Circles\Service\RemoteStreamService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ICacheFactory;
use OCP\IL10N;
use OCP\Security\IHasher;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CircleServiceTest extends TestCase {
	protected CircleService $circleService;
	protected ConfigService&MockObject $configService;
	protected MemberRequest&MockObject $memberRequest;

	protected function setUp(): void {
		parent::setUp();

		$this->configService = $this->createMock(ConfigService::class);
		$this->memberRequest = $this->createMock(MemberRequest::class);

		$this->circleService = new CircleService(
			$this->createMock(IL10N::class),
			$this->createMock(IHasher::class),
			$this->createMock(ICacheFactory::class),
			$this->createMock(CircleRequest::class),
			$this->memberRequest,
			$this->createMock(RemoteStreamService::class),
			$this->createMock(FederatedUserService::class),
			$this->createMock(FederatedEventService::class),
			$this->createMock(MemberService::class),
			$this->createMock(PermissionService::class),
			$this->configService,
			$this->createMock(IEventDispatcher::class),
		);
	}

	private function circleFullDataProvider(): array {
		return [
			[-1, -1, [$this->createMock(Member::class)], false],
			[0, -1, [$this->createMock(Member::class)], true],
			[1, -1, [$this->createMock(Member::class)], true],
			[2, -1, [$this->createMock(Member::class)], false],
			[-1, 0, [$this->createMock(Member::class)], true],
			[-1, 1, [$this->createMock(Member::class)], true],
			[-1, 2, [$this->createMock(Member::class)], false],
			[0, 2, [$this->createMock(Member::class)], true],
			[1, 2, [$this->createMock(Member::class)], true],
			[2, 0, [$this->createMock(Member::class)], true],
			[2, 1, [$this->createMock(Member::class)], true],
			[2, 2, [$this->createMock(Member::class)], false],
		];
	}

	/**
	 * @dataProvider circleFullDataProvider
	 */
	public function testIsCircleFull(int $instanceLimit, int $circleLimit, array $members, bool $expectResult) {
		$circle = $this->createMock(Circle::class);
		$circle->method('getSettings')->willReturn(['members_limit' => $circleLimit]);

		$this->memberRequest->method('getMembers')->willReturn($members);

		$this->configService->method('getAppValueInt')->willReturn($instanceLimit);

		$this->assertSame($this->circleService->isCircleFull($circle), $expectResult);
	}
}
