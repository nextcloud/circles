<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\FederatedItems;

use OCA\Circles\AuditLog\Operation;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\FederatedItems\CircleJoin;
use OCA\Circles\FederatedItems\CircleLeave;
use OCA\Circles\FederatedItems\MassiveMemberAdd;
use OCA\Circles\FederatedItems\MemberLevel;
use OCA\Circles\FederatedItems\MemberRemove;
use OCA\Circles\FederatedItems\SingleMemberAdd;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\AuditLogService;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\EventService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MaintenanceService;
use OCA\Circles\Service\MemberService;
use OCA\Circles\Service\MembershipService;
use OCA\Circles\Service\RemoteStreamService;
use OCP\AppFramework\Services\IAppConfig;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUserManager;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class MemberAuditLogTest extends TestCase {
	private ConfigService&MockObject $configService;
	private MemberService&MockObject $memberService;
	private MemberRequest&MockObject $memberRequest;
	private AuditLogService $auditLogService;
	/** @var list<CriticalActionPerformedEvent> */
	private array $auditEvents = [];

	protected function setUp(): void {
		parent::setUp();

		$this->auditEvents = [];
		$eventDispatcher = $this->createMock(IEventDispatcher::class);
		$eventDispatcher->method('dispatchTyped')
			->willReturnCallback(function (Event $event): void {
				if ($event instanceof CriticalActionPerformedEvent) {
					$this->auditEvents[] = $event;
				}
			});

		$this->configService = $this->createMock(ConfigService::class);
		$this->configService->method('isLocalInstance')
			->willReturnCallback(static fn (string $instance): bool => $instance === '');
		$this->configService->method('displayFederatedUser')
			->willReturnCallback(static fn (IFederatedUser $user): string => $user->getUserId() . ($user->getInstance() === '' ? '' : '@' . $user->getInstance()));

		$this->memberService = $this->createMock(MemberService::class);
		$this->memberRequest = $this->createMock(MemberRequest::class);
		$this->auditLogService = new AuditLogService($eventDispatcher, $this->configService);
	}

	private function createMember(string $id, string $userId, int $userType = Member::TYPE_USER, int $level = Member::LEVEL_MEMBER, string $status = Member::STATUS_MEMBER, string $instance = ''): Member {
		$member = new Member();
		$member->setId($id)
			->setUserId($userId)
			->setUserType($userType)
			->setInstance($instance)
			->setLevel($level)
			->setStatus($status);

		return $member;
	}

	private function createEvent(string $class, ?Member $member = null, string $instance = ''): FederatedEvent {
		$circle = new Circle();
		$circle->setSingleId('circle1')
			->setName('Team')
			->setDisplayName('Team')
			->setSource(Member::TYPE_CIRCLE)
			->setOwner($this->createMember('ownerMember', 'owner', level: Member::LEVEL_OWNER, instance: $instance))
			->setInitiator($this->createMember('initiatorMember', 'alice', level: Member::LEVEL_ADMIN));

		$event = new FederatedEvent($class);
		$event->setCircle($circle);
		$event->setMember($member);

		return $event;
	}

	/**
	 * @param list<array{Operation, string, array}> $expected
	 */
	private function assertAuditEvents(array $expected): void {
		$this->assertCount(count($expected), $this->auditEvents);
		foreach ($expected as $i => [$operation, $message, $parameters]) {
			$this->assertSame($operation->value, $this->auditEvents[$i]->getOperation());
			$this->assertSame($message, $this->auditEvents[$i]->getLogMessage());
			$this->assertSame($parameters, $this->auditEvents[$i]->getParameters());
		}
	}

	/**
	 * @param class-string<SingleMemberAdd> $class
	 */
	private function createMemberAdd(string $class): SingleMemberAdd {
		return new $class(
			$this->createMock(IUserManager::class),
			$this->createMock(IAppConfig::class),
			$this->memberRequest,
			$this->createMock(FederatedUserService::class),
			$this->createMock(RemoteStreamService::class),
			$this->createMock(CircleService::class),
			$this->memberService,
			$this->createMock(MembershipService::class),
			$this->createMock(EventService::class),
			$this->configService,
			$this->createMock(MaintenanceService::class),
			$this->auditLogService,
		);
	}

	public function testSingleMemberAdd(): void {
		$this->memberService->method('insertOrUpdate')->willReturn(true);

		$this->createMemberAdd(SingleMemberAdd::class)
			->manage($this->createEvent(SingleMemberAdd::class, $this->createMember('member1', 'bob')));

		$this->assertAuditEvents([[
			Operation::MemberAdded,
			'Member "%s" (%s, member %s) was added to team %s ("%s") as %s by "%s"',
			[
				'userId' => 'bob',
				'memberType' => 'user',
				'memberId' => 'member1',
				'circleId' => 'circle1',
				'name' => 'Team',
				'level' => 'Member',
				'initiator' => 'alice',
			],
		]]);
	}

	public function testSingleMemberAddInvitation(): void {
		$this->memberService->method('insertOrUpdate')->willReturn(true);
		$member = $this->createMember('member1', 'bob', Member::TYPE_USER, Member::LEVEL_NONE, Member::STATUS_INVITED, 'remote.example.com');

		$this->createMemberAdd(SingleMemberAdd::class)
			->manage($this->createEvent(SingleMemberAdd::class, $member));

		$this->assertAuditEvents([[
			Operation::MemberInvited,
			'Member "%s" (%s, member %s) was invited to team %s ("%s") by "%s"',
			[
				'userId' => 'bob@remote.example.com',
				'memberType' => 'user',
				'memberId' => 'member1',
				'circleId' => 'circle1',
				'name' => 'Team',
				'initiator' => 'alice',
			],
		]]);
	}

	public function testSingleMemberAddEmailAddressNotLogged(): void {
		$this->memberService->method('insertOrUpdate')->willReturn(true);

		$this->createMemberAdd(SingleMemberAdd::class)
			->manage($this->createEvent(SingleMemberAdd::class, $this->createMember('member1', 'bob@example.com', Member::TYPE_MAIL)));

		$this->assertCount(1, $this->auditEvents);
		$this->assertNull($this->auditEvents[0]->getParameters()['userId']);
		$this->assertSame('mail', $this->auditEvents[0]->getParameters()['memberType']);
	}

	public function testSingleMemberAddFailedNotLogged(): void {
		$this->memberService->method('insertOrUpdate')->willReturn(false);

		$this->createMemberAdd(SingleMemberAdd::class)
			->manage($this->createEvent(SingleMemberAdd::class, $this->createMember('member1', 'bob')));

		$this->assertSame([], $this->auditEvents);
	}

	public function testSingleMemberAddToGroupMirrorNotLogged(): void {
		$this->memberService->method('insertOrUpdate')->willReturn(true);

		$event = $this->createEvent(SingleMemberAdd::class, $this->createMember('member1', 'bob'));
		$event->getCircle()->setSource(Member::TYPE_GROUP);
		$this->createMemberAdd(SingleMemberAdd::class)->manage($event);

		$this->assertSame([], $this->auditEvents);
	}

	public function testMassiveMemberAdd(): void {
		$this->memberService->method('insertOrUpdate')
			->willReturnCallback(static fn (Member $member): bool => $member->getId() !== 'failed');

		$event = $this->createEvent(MassiveMemberAdd::class);
		$event->setMembers([
			$this->createMember('member1', 'bob'),
			$this->createMember('failed', 'carol'),
			$this->createMember('member2', 'group1', Member::TYPE_GROUP),
		]);
		$this->createMemberAdd(MassiveMemberAdd::class)->manage($event);

		$message = 'Member "%s" (%s, member %s) was added to team %s ("%s") as %s by "%s"';
		$this->assertAuditEvents([
			[Operation::MemberAdded, $message, ['userId' => 'bob', 'memberType' => 'user', 'memberId' => 'member1', 'circleId' => 'circle1', 'name' => 'Team', 'level' => 'Member', 'initiator' => 'alice']],
			[Operation::MemberAdded, $message, ['userId' => 'group1', 'memberType' => 'group', 'memberId' => 'member2', 'circleId' => 'circle1', 'name' => 'Team', 'level' => 'Member', 'initiator' => 'alice']],
		]);
	}

	private function createCircleJoin(): CircleJoin {
		return new CircleJoin(
			$this->createMock(IUserManager::class),
			$this->memberRequest,
			$this->createMock(FederatedUserService::class),
			$this->createMock(CircleService::class),
			$this->memberService,
			$this->createMock(MembershipService::class),
			$this->createMock(EventService::class),
			$this->configService,
			$this->auditLogService,
		);
	}

	public function testCircleJoin(): void {
		$this->memberService->method('insertOrUpdate')->willReturn(true);

		$this->createCircleJoin()->manage($this->createEvent(CircleJoin::class, $this->createMember('member1', 'bob')));

		$this->assertAuditEvents([[
			Operation::MemberJoined,
			'Member "%s" (%s, member %s) joined team %s ("%s")',
			['userId' => 'bob', 'memberType' => 'user', 'memberId' => 'member1', 'circleId' => 'circle1', 'name' => 'Team'],
		]]);
	}

	public function testCircleJoinRequest(): void {
		$this->memberService->method('insertOrUpdate')->willReturn(true);
		$member = $this->createMember('member1', 'bob', Member::TYPE_USER, Member::LEVEL_NONE, Member::STATUS_REQUEST);

		$this->createCircleJoin()->manage($this->createEvent(CircleJoin::class, $member));

		$this->assertAuditEvents([[
			Operation::MemberRequested,
			'Member "%s" (%s, member %s) requested to join team %s ("%s")',
			['userId' => 'bob', 'memberType' => 'user', 'memberId' => 'member1', 'circleId' => 'circle1', 'name' => 'Team'],
		]]);
	}

	public function testCircleJoinFailedNotLogged(): void {
		$this->memberService->method('insertOrUpdate')->willReturn(false);

		$this->createCircleJoin()->manage($this->createEvent(CircleJoin::class, $this->createMember('member1', 'bob')));

		$this->assertSame([], $this->auditEvents);
	}

	private function createCircleLeave(?CircleRequest $circleRequest = null): CircleLeave {
		return new CircleLeave(
			$this->memberRequest,
			$circleRequest ?? $this->createMock(CircleRequest::class),
			$this->createMock(MembershipService::class),
			$this->createMock(EventService::class),
			$this->configService,
			$this->auditLogService,
		);
	}

	public function testCircleLeave(): void {
		$this->memberRequest->expects($this->once())
			->method('delete');

		$this->createCircleLeave()->manage($this->createEvent(CircleLeave::class, $this->createMember('member1', 'bob')));

		$this->assertAuditEvents([[
			Operation::MemberLeft,
			'Member "%s" (%s, member %s) left team %s ("%s")',
			['userId' => 'bob', 'memberType' => 'user', 'memberId' => 'member1', 'circleId' => 'circle1', 'name' => 'Team'],
		]]);
	}

	public function testCircleLeaveOfOwner(): void {
		$this->memberRequest->method('getMemberById')
			->with('member2')
			->willReturn($this->createMember('member2', 'carol', level: Member::LEVEL_ADMIN));

		$event = $this->createEvent(CircleLeave::class, $this->createMember('ownerMember', 'owner', level: Member::LEVEL_OWNER));
		$event->getData()->s('newOwnerId', 'member2');
		$this->createCircleLeave()->manage($event);

		$this->assertAuditEvents([
			[
				Operation::MemberLeft,
				'Member "%s" (%s, member %s) left team %s ("%s")',
				['userId' => 'owner', 'memberType' => 'user', 'memberId' => 'ownerMember', 'circleId' => 'circle1', 'name' => 'Team'],
			],
			[
				Operation::MemberLevelUpdated,
				'Level of member "%s" (%s, member %s) in team %s ("%s") was changed from %s to %s because the owner left',
				['userId' => 'carol', 'memberType' => 'user', 'memberId' => 'member2', 'circleId' => 'circle1', 'name' => 'Team', 'oldLevel' => 'Admin', 'level' => 'Owner'],
			],
		]);
	}

	public function testCircleLeaveOfLastMember(): void {
		$circleRequest = $this->createMock(CircleRequest::class);
		$circleRequest->expects($this->once())
			->method('delete');

		$event = $this->createEvent(CircleLeave::class, $this->createMember('ownerMember', 'owner', level: Member::LEVEL_OWNER));
		$event->getData()->sBool('destroyCircle', true);
		$this->createCircleLeave($circleRequest)->manage($event);

		$this->assertAuditEvents([
			[
				Operation::MemberLeft,
				'Member "%s" (%s, member %s) left team %s ("%s")',
				['userId' => 'owner', 'memberType' => 'user', 'memberId' => 'ownerMember', 'circleId' => 'circle1', 'name' => 'Team'],
			],
			[
				Operation::TeamDeleted,
				'Team %s ("%s") was deleted because its last member "%s" left',
				['circleId' => 'circle1', 'name' => 'Team', 'userId' => 'owner'],
			],
		]);
	}

	public function testCircleLeaveNotLoggedOnOtherInstances(): void {
		$this->createCircleLeave()->manage($this->createEvent(CircleLeave::class, $this->createMember('member1', 'bob'), 'remote.example.com'));

		$this->assertSame([], $this->auditEvents);
	}

	public function testMemberRemove(): void {
		$this->memberRequest->expects($this->once())
			->method('delete');
		$item = new MemberRemove($this->memberRequest, $this->createMock(MembershipService::class), $this->createMock(EventService::class), $this->auditLogService);

		$item->manage($this->createEvent(MemberRemove::class, $this->createMember('member1', 'bob')));

		$this->assertAuditEvents([[
			Operation::MemberRemoved,
			'Member "%s" (%s, member %s) was removed from team %s ("%s") by "%s"',
			['userId' => 'bob', 'memberType' => 'user', 'memberId' => 'member1', 'circleId' => 'circle1', 'name' => 'Team', 'initiator' => 'alice'],
		]]);
	}

	private function createMemberLevel(): MemberLevel {
		return new MemberLevel($this->memberRequest, $this->createMock(MembershipService::class), $this->createMock(EventService::class), $this->configService, $this->auditLogService);
	}

	public function testMemberLevel(): void {
		$this->memberRequest->expects($this->once())
			->method('updateLevel');

		$event = $this->createEvent(MemberLevel::class, $this->createMember('member1', 'bob'));
		$event->getData()->sInt('level', Member::LEVEL_MODERATOR);
		$this->createMemberLevel()->manage($event);

		$this->assertAuditEvents([[
			Operation::MemberLevelUpdated,
			'Level of member "%s" (%s, member %s) in team %s ("%s") was changed from %s to %s by "%s"',
			['userId' => 'bob', 'memberType' => 'user', 'memberId' => 'member1', 'circleId' => 'circle1', 'name' => 'Team', 'oldLevel' => 'Member', 'level' => 'Moderator', 'initiator' => 'alice'],
		]]);
	}

	public function testMemberLevelOwnershipTransfer(): void {
		$this->memberRequest->expects($this->once())
			->method('promoteToOwner')
			->willReturn($this->createMember('ownerMember', 'owner', level: Member::LEVEL_ADMIN));

		$event = $this->createEvent(MemberLevel::class, $this->createMember('member1', 'bob', level: Member::LEVEL_ADMIN));
		$event->getData()->sInt('level', Member::LEVEL_OWNER);
		$this->createMemberLevel()->manage($event);

		$message = 'Level of member "%s" (%s, member %s) in team %s ("%s") was changed from %s to %s by "%s"';
		$this->assertAuditEvents([
			[Operation::MemberLevelUpdated, $message, ['userId' => 'bob', 'memberType' => 'user', 'memberId' => 'member1', 'circleId' => 'circle1', 'name' => 'Team', 'oldLevel' => 'Admin', 'level' => 'Owner', 'initiator' => 'alice']],
			[Operation::MemberLevelUpdated, $message, ['userId' => 'owner', 'memberType' => 'user', 'memberId' => 'ownerMember', 'circleId' => 'circle1', 'name' => 'Team', 'oldLevel' => 'Owner', 'level' => 'Admin', 'initiator' => 'alice']],
		]);
	}
}
