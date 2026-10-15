<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\FederatedItems;

use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\FederatedItemBadRequestException;
use OCA\Circles\Exceptions\MemberLevelException;
use OCA\Circles\FederatedItems\MemberLevel;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\EventService;
use OCA\Circles\Service\MembershipService;
use OCA\Circles\Tools\Model\SimpleDataStore;
use PHPUnit\Framework\TestCase;

class MemberLevelTest extends TestCase {
	public function testVisitorCanBePromotedToMember(): void {
		[$handler, , , , $configService] = $this->createSubject();
		$configService->method('getAppValueInt')
			->with(ConfigService::HARD_MODERATION)
			->willReturn(0);
		$event = $this->createEvent(Member::LEVEL_ADMIN, Member::LEVEL_VISITOR, Member::LEVEL_MEMBER);

		$handler->verify($event);

		$this->assertSame(Member::LEVEL_MEMBER, $event->getData()->gInt('level'));
		$this->assertSame(Member::LEVEL_MEMBER, $event->getOutcome()['level']);
	}

	public function testLocalVisitorAssignmentSucceeds(): void {
		[$handler, , , , $configService] = $this->createSubject();
		$configService->method('getAppValueInt')
			->with(ConfigService::HARD_MODERATION)
			->willReturn(0);
		$configService->method('isLocalInstance')->with('')->willReturn(true);
		$event = $this->createEvent(Member::LEVEL_ADMIN, Member::LEVEL_MEMBER, Member::LEVEL_VISITOR);

		$handler->verify($event);

		$this->assertSame(Member::LEVEL_VISITOR, $event->getData()->gInt('level'));
		$this->assertSame(Member::LEVEL_VISITOR, $event->getOutcome()['level']);
	}

	/**
	 * @dataProvider disallowedVisitorTargets
	 */
	public function testVisitorAssignmentRequiresLocalTeamAndMember(int $circleConfig, string $instance, bool $local): void {
		[$handler, $memberRequest, $membershipService, $eventService, $configService] = $this->createSubject();
		$event = $this->createEvent(
			Member::LEVEL_ADMIN,
			Member::LEVEL_MEMBER,
			Member::LEVEL_VISITOR,
			$circleConfig,
			$instance
		);

		if ($local) {
			$configService->expects($this->never())->method('isLocalInstance');
		} else {
			$configService->expects($this->once())->method('isLocalInstance')->with($instance)->willReturn(false);
		}
		$memberRequest->expects($this->never())->method('updateLevel');
		$membershipService->expects($this->never())->method('onUpdate');
		$eventService->expects($this->never())->method('memberLevelEditing');
		$this->expectException(FederatedItemBadRequestException::class);
		$this->expectExceptionMessage('Visitor requires a local team and member');

		$handler->verify($event);
	}

	public static function disallowedVisitorTargets(): array {
		return [
			'federated team' => [Circle::CFG_FEDERATED, '', true],
			'remote member' => [Circle::CFG_CIRCLE, 'remote.example', false],
		];
	}

	/**
	 * @dataProvider moderationModeCases
	 */
	public function testModerationModesUseActorAndTargetLevels(int $mode, int $targetLevel, int $requestedLevel, bool $allowed): void {
		[$handler, , , , $configService] = $this->createSubject();
		$configService->method('getAppValueInt')
			->with(ConfigService::HARD_MODERATION)
			->willReturn($mode);
		$configService->method('isLocalInstance')->willReturn(true);
		$event = $this->createEvent(Member::LEVEL_MODERATOR, $targetLevel, $requestedLevel);

		if (!$allowed) {
			$this->expectException(MemberLevelException::class);
		}

		$handler->verify($event);

		if ($allowed) {
			$this->assertSame($requestedLevel, $event->getData()->gInt('level'));
		}
	}

	public static function moderationModeCases(): array {
		return [
			'mode 0 accepts a lower role and lower target' => [0, Member::LEVEL_MEMBER, Member::LEVEL_VISITOR, true],
			'mode 1 accepts a lower role and lower target' => [1, Member::LEVEL_MEMBER, Member::LEVEL_VISITOR, true],
			'mode 2 accepts a lower role and lower target' => [2, Member::LEVEL_MEMBER, Member::LEVEL_VISITOR, true],
			'mode 0 accepts a peer target' => [0, Member::LEVEL_MODERATOR, Member::LEVEL_VISITOR, true],
			'mode 1 rejects a peer target' => [1, Member::LEVEL_MODERATOR, Member::LEVEL_VISITOR, false],
			'mode 2 rejects a peer target' => [2, Member::LEVEL_MODERATOR, Member::LEVEL_VISITOR, false],
			'mode 0 accepts an equal requested role' => [0, Member::LEVEL_MEMBER, Member::LEVEL_MODERATOR, true],
			'mode 1 accepts an equal requested role' => [1, Member::LEVEL_MEMBER, Member::LEVEL_MODERATOR, true],
			'mode 2 rejects an equal requested role' => [2, Member::LEVEL_MEMBER, Member::LEVEL_MODERATOR, false],
		];
	}

	/**
	 * @dataProvider nonModeratorInitiators
	 */
	public function testNonModeratorAndPendingInitiatorsCannotChangeRoles(int $level, string $status): void {
		[$handler, , , , $configService] = $this->createSubject();
		$configService->method('getAppValueInt')->willReturn(0);
		$configService->method('isLocalInstance')->willReturn(true);
		$event = $this->createEvent($level, Member::LEVEL_MEMBER, Member::LEVEL_VISITOR, 0, '', $status);

		$this->expectException(MemberLevelException::class);
		$handler->verify($event);
	}

	public static function nonModeratorInitiators(): array {
		return [
			'visitor' => [Member::LEVEL_VISITOR, Member::STATUS_MEMBER],
			'member' => [Member::LEVEL_MEMBER, Member::STATUS_MEMBER],
			'pending' => [Member::LEVEL_NONE, Member::STATUS_REQUEST],
		];
	}

	/**
	 * @dataProvider invalidLevels
	 */
	public function testUnknownAndNoneLevelsAreRejected(int $level): void {
		[$handler] = $this->createSubject();
		$event = $this->createEvent(Member::LEVEL_ADMIN, Member::LEVEL_MEMBER, $level);

		$this->expectException(FederatedItemBadRequestException::class);
		$this->expectExceptionMessage('invalid level');
		$handler->verify($event);
	}

	public static function invalidLevels(): array {
		$levels = [0, -1, 3, 5, 6, 7, 10];

		return array_combine(
			array_map(static fn (int $level): string => (string)$level, $levels),
			array_map(static fn (int $level): array => [$level], $levels)
		);
	}

	public function testCurrentOwnerCannotBeDemoted(): void {
		[$handler, , , , $configService] = $this->createSubject();
		$configService->method('getAppValueInt')->willReturn(0);
		$event = $this->createEvent(Member::LEVEL_OWNER, Member::LEVEL_OWNER, Member::LEVEL_MEMBER);

		$this->expectException(MemberLevelException::class);
		$handler->verify($event);
	}

	public function testOwnerCanPromoteExistingVisitorToOwner(): void {
		[$handler, , , , $configService] = $this->createSubject();
		$event = $this->createEvent(Member::LEVEL_OWNER, Member::LEVEL_VISITOR, Member::LEVEL_OWNER);
		$configService->method('isLocalInstance')->willReturn(true);

		$handler->verify($event);

		$this->assertSame(Member::LEVEL_OWNER, $event->getData()->gInt('level'));
		$this->assertSame(Member::LEVEL_OWNER, $event->getOutcome()['level']);
	}

	public function testManagePreservesUnknownLevelAndInvalidatesMembershipCache(): void {
		[$handler, $memberRequest, $membershipService, $eventService] = $this->createSubject();
		$member = (new Member())
			->setId('target')
			->setSingleId('target-single')
			->setLevel(Member::LEVEL_MEMBER);
		$event = (new FederatedEvent())
			->setMember($member)
			->setData(new SimpleDataStore(['level' => 73]));

		$memberRequest->expects($this->once())
			->method('updateLevel')
			->with($this->callback(static fn (Member $updated): bool => $updated->getLevel() === 73));
		$membershipService->expects($this->once())->method('onUpdate')->with('target-single');
		$eventService->expects($this->once())->method('memberLevelEditing')->with($event);

		$handler->manage($event);

		$this->assertSame(Member::LEVEL_MEMBER, $event->getMember()->getLevel());
	}

	private function createSubject(): array {
		$memberRequest = $this->createMock(MemberRequest::class);
		$membershipService = $this->createMock(MembershipService::class);
		$eventService = $this->createMock(EventService::class);
		$configService = $this->createMock(ConfigService::class);

		return [
			new MemberLevel($memberRequest, $membershipService, $eventService, $configService),
			$memberRequest,
			$membershipService,
			$eventService,
			$configService,
		];
	}

	private function createEvent(
		int $initiatorLevel,
		int $targetLevel,
		int $requestedLevel,
		int $circleConfig = Circle::CFG_CIRCLE,
		string $targetInstance = '',
		string $initiatorStatus = Member::STATUS_MEMBER,
	): FederatedEvent {
		$initiator = (new Member())
			->setId('initiator')
			->setSingleId('initiator-single')
			->setLevel($initiatorLevel)
			->setStatus($initiatorStatus);
		$target = (new Member())
			->setId('target')
			->setSingleId('target-single')
			->setInstance($targetInstance)
			->setLevel($targetLevel)
			->setStatus(Member::STATUS_MEMBER);
		$circle = (new Circle())
			->setSingleId('circle-single')
			->setConfig($circleConfig)
			->setInitiator($initiator);

		return (new FederatedEvent())
			->setCircle($circle)
			->setMember($target)
			->setParams(new SimpleDataStore(['level' => $requestedLevel]));
	}
}
