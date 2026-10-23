<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Service;

use OCA\Circles\AuditLog\Operation;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\AuditLogService;
use OCA\Circles\Service\ConfigService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AuditLogServiceTest extends TestCase {
	private IEventDispatcher&MockObject $eventDispatcher;
	private ConfigService&MockObject $configService;
	private AuditLogService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->configService = $this->createMock(ConfigService::class);
		$this->configService->method('isLocalInstance')
			->willReturnCallback(static fn (string $instance): bool => $instance === '' || $instance === 'cloud.example.com');
		$this->configService->method('displayFederatedUser')
			->willReturnCallback(static fn (IFederatedUser $user): string => $user->getUserId() . '@' . $user->getInstance());

		$this->service = new AuditLogService($this->eventDispatcher, $this->configService);
	}

	private function createCircle(string $instance, int $source = Member::TYPE_CIRCLE): Circle {
		$owner = new Member();
		$owner->setUserId('owner')
			->setUserType(Member::TYPE_USER)
			->setInstance($instance);

		$circle = new Circle();
		$circle->setSingleId('circle1')
			->setSource($source)
			->setOwner($owner);

		return $circle;
	}

	public function testLog(): void {
		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(function (CriticalActionPerformedEvent $event): bool {
				$this->assertSame('Team %s was deleted', $event->getLogMessage());
				$this->assertSame(['circleId' => 'circle1'], $event->getParameters());
				$this->assertFalse($event->getObfuscateParameters());
				$this->assertSame(Operation::TeamDeleted->value, $event->getOperation());
				return true;
			}));

		$this->service->log(Operation::TeamDeleted, 'Team %s was deleted', ['circleId' => 'circle1']);
	}

	public function testLogTeamChangeOnOwningInstance(): void {
		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->isInstanceOf(CriticalActionPerformedEvent::class));

		$this->service->logTeamChange($this->createCircle('cloud.example.com'), Operation::TeamDeleted, 'Team %s was deleted', ['circleId' => 'circle1']);
	}

	public function testLogTeamChangeSkipsTeamOwnedByAnotherInstance(): void {
		$this->eventDispatcher->expects($this->never())
			->method('dispatchTyped');

		$this->service->logTeamChange($this->createCircle('remote.example.com'), Operation::TeamDeleted, 'Team %s was deleted', ['circleId' => 'circle1']);
	}

	public function testLogTeamChangeSkipsGroupMirror(): void {
		$this->eventDispatcher->expects($this->never())
			->method('dispatchTyped');

		$this->service->logTeamChange($this->createCircle('', Member::TYPE_GROUP), Operation::TeamDeleted, 'Team %s was deleted', ['circleId' => 'circle1']);
	}

	public function testLogTeamChangeWithoutOwner(): void {
		$circle = new Circle();
		$circle->setSingleId('circle1');

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped');

		$this->service->logTeamChange($circle, Operation::TeamDeleted, 'Team %s was deleted', ['circleId' => 'circle1']);
	}

	public static function dataGetUserId(): array {
		return [
			[Member::TYPE_USER, 'alice@cloud.example.com'],
			[Member::TYPE_GROUP, 'alice@cloud.example.com'],
			[Member::TYPE_CIRCLE, 'alice@cloud.example.com'],
			[Member::TYPE_APP, 'alice@cloud.example.com'],
			[Member::TYPE_MAIL, null],
			[Member::TYPE_CONTACT, null],
		];
	}

	#[DataProvider('dataGetUserId')]
	public function testGetUserId(int $userType, ?string $expected): void {
		$member = new Member();
		$member->setUserId('alice')
			->setUserType($userType)
			->setInstance('cloud.example.com');

		$this->assertSame($expected, $this->service->getUserId($member));
	}

	public function testGetInitiator(): void {
		$initiator = new Member();
		$initiator->setUserId('alice')
			->setUserType(Member::TYPE_USER)
			->setInstance('cloud.example.com');
		$circle = new Circle();
		$circle->setInitiator($initiator);

		$this->assertSame('alice@cloud.example.com', $this->service->getInitiator($circle));
		$this->assertNull($this->service->getInitiator(new Circle()));
	}

	public function testGetTeamName(): void {
		$circle = new Circle();
		$circle->setName('name');
		$this->assertSame('name', $this->service->getTeamName($circle));

		$circle->setDisplayName('Display name');
		$this->assertSame('Display name', $this->service->getTeamName($circle));
	}
}
