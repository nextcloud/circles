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
use OCA\Circles\Entity\CircleInvitation;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedEventDSyncException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\FederatedItems\CircleConfig;
use OCA\Circles\FederatedItems\CircleCreate;
use OCA\Circles\FederatedItems\CircleCreateInvitation;
use OCA\Circles\FederatedItems\CircleDestroy;
use OCA\Circles\FederatedItems\CircleEdit;
use OCA\Circles\FederatedItems\CircleRevokeInvitation;
use OCA\Circles\FederatedItems\CircleSetting;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Member;
use OCA\Circles\Repository\CircleInvitationRepository;
use OCA\Circles\Service\AuditLogService;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\EventService;
use OCA\Circles\Service\MaintenanceService;
use OCA\Circles\Service\MembershipService;
use OCA\Circles\Service\PermissionService;
use OCA\Circles\Service\ShareTokenService;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CircleAuditLogTest extends TestCase {
	private ConfigService&MockObject $configService;
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

		$this->auditLogService = new AuditLogService($eventDispatcher, $this->configService);
	}

	private function createEvent(string $class, string $instance = ''): FederatedEvent {
		$owner = new Member();
		$owner->setId('ownerMember')
			->setUserId('owner')
			->setUserType(Member::TYPE_USER)
			->setInstance($instance)
			->setLevel(Member::LEVEL_OWNER);

		$initiator = new Member();
		$initiator->setId('initiatorMember')
			->setUserId('alice')
			->setUserType(Member::TYPE_USER)
			->setInstance('')
			->setLevel(Member::LEVEL_ADMIN);

		$circle = new Circle();
		$circle->setSingleId('circle1')
			->setName('Team')
			->setDisplayName('Team')
			->setSource(Member::TYPE_CIRCLE)
			->setConfig(Circle::CFG_OPEN)
			->setOwner($owner)
			->setInitiator($initiator);

		$event = new FederatedEvent($class);
		$event->setCircle($circle);

		return $event;
	}

	private function assertAuditEvent(Operation $operation, string $message, array $parameters): void {
		$this->assertCount(1, $this->auditEvents);
		$this->assertSame($operation->value, $this->auditEvents[0]->getOperation());
		$this->assertSame($message, $this->auditEvents[0]->getLogMessage());
		$this->assertSame($parameters, $this->auditEvents[0]->getParameters());
	}

	private function createCircleCreate(CircleRequest $circleRequest): CircleCreate {
		$memberRequest = $this->createMock(MemberRequest::class);
		$memberRequest->method('getMemberById')
			->willThrowException(new MemberNotFoundException());

		return new CircleCreate(
			$circleRequest,
			$memberRequest,
			$this->createMock(CircleService::class),
			$this->createMock(MembershipService::class),
			$this->createMock(MaintenanceService::class),
			$this->createMock(EventService::class),
			$this->auditLogService,
		);
	}

	public function testCircleCreate(): void {
		$circleRequest = $this->createMock(CircleRequest::class);
		$circleRequest->method('getCircle')
			->willThrowException(new CircleNotFoundException());
		$circleRequest->expects($this->once())
			->method('save');

		$this->createCircleCreate($circleRequest)->manage($this->createEvent(CircleCreate::class));

		$this->assertAuditEvent(Operation::TeamCreated, 'Team %s ("%s") was created with owner "%s" by "%s"', [
			'circleId' => 'circle1',
			'name' => 'Team',
			'owner' => 'owner',
			'initiator' => 'alice',
		]);
	}

	public function testCircleCreateNotLoggedWhenTeamExists(): void {
		$circleRequest = $this->createMock(CircleRequest::class);
		$circleRequest->method('getCircle')
			->willReturn(new Circle());
		$circleRequest->expects($this->never())
			->method('save');

		try {
			$this->createCircleCreate($circleRequest)->manage($this->createEvent(CircleCreate::class));
			$this->fail('Expected FederatedEventDSyncException');
		} catch (FederatedEventDSyncException) {
		}

		$this->assertSame([], $this->auditEvents);
	}

	public function testCircleCreateNotLoggedForGroupMirror(): void {
		$circleRequest = $this->createMock(CircleRequest::class);
		$circleRequest->method('getCircle')
			->willThrowException(new CircleNotFoundException());

		$event = $this->createEvent(CircleCreate::class);
		$event->getCircle()->setSource(Member::TYPE_GROUP);
		$this->createCircleCreate($circleRequest)->manage($event);

		$this->assertSame([], $this->auditEvents);
	}

	private function createCircleDestroy(CircleRequest $circleRequest): CircleDestroy {
		return new CircleDestroy(
			$circleRequest,
			$this->createMock(MemberRequest::class),
			$this->createMock(EventService::class),
			$this->createMock(MembershipService::class),
			$this->auditLogService,
		);
	}

	public function testCircleDestroy(): void {
		$circleRequest = $this->createMock(CircleRequest::class);
		$circleRequest->expects($this->once())
			->method('delete');

		$this->createCircleDestroy($circleRequest)->manage($this->createEvent(CircleDestroy::class));

		$this->assertAuditEvent(Operation::TeamDeleted, 'Team %s ("%s") was deleted by "%s"', [
			'circleId' => 'circle1',
			'name' => 'Team',
			'initiator' => 'alice',
		]);
	}

	public function testCircleDestroyNotLoggedOnOtherInstances(): void {
		$this->createCircleDestroy($this->createMock(CircleRequest::class))
			->manage($this->createEvent(CircleDestroy::class, 'remote.example.com'));

		$this->assertSame([], $this->auditEvents);
	}

	private function createCircleEdit(): CircleEdit {
		return new CircleEdit(
			$this->createMock(CircleRequest::class),
			$this->createMock(MemberRequest::class),
			$this->createMock(CircleService::class),
			$this->createMock(EventService::class),
			$this->auditLogService,
		);
	}

	public function testCircleEditRename(): void {
		$event = $this->createEvent(CircleEdit::class);
		$event->getData()->s('displayName', 'New name');

		$this->createCircleEdit()->manage($event);

		$this->assertAuditEvent(Operation::TeamRenamed, 'Team %s was renamed from "%s" to "%s" by "%s"', [
			'circleId' => 'circle1',
			'oldName' => 'Team',
			'name' => 'New name',
			'initiator' => 'alice',
		]);
	}

	public function testCircleEditDescriptionNotLogged(): void {
		$event = $this->createEvent(CircleEdit::class);
		$event->getData()->s('description', 'Some description');

		$this->createCircleEdit()->manage($event);

		$this->assertSame([], $this->auditEvents);
	}

	public function testCircleConfig(): void {
		$circleRequest = $this->createMock(CircleRequest::class);
		$circleRequest->expects($this->once())
			->method('updateConfig');
		$item = new CircleConfig(
			$circleRequest,
			$this->createMock(PermissionService::class),
			$this->configService,
			$this->auditLogService,
		);

		$event = $this->createEvent(CircleConfig::class);
		$event->getData()->sInt('config', Circle::CFG_OPEN | Circle::CFG_VISIBLE);
		$item->manage($event);

		$this->assertAuditEvent(Operation::TeamConfigUpdated, 'Configuration of team %s ("%s") was changed from %s to %s by "%s"', [
			'circleId' => 'circle1',
			'name' => 'Team',
			'oldConfig' => Circle::CFG_OPEN,
			'config' => Circle::CFG_OPEN | Circle::CFG_VISIBLE,
			'initiator' => 'alice',
		]);
	}

	public function testCircleConfigUnchangedNotLogged(): void {
		$item = new CircleConfig(
			$this->createMock(CircleRequest::class),
			$this->createMock(PermissionService::class),
			$this->configService,
			$this->auditLogService,
		);

		$event = $this->createEvent(CircleConfig::class);
		$event->getData()->sInt('config', Circle::CFG_OPEN);
		$item->manage($event);

		$this->assertSame([], $this->auditEvents);
	}

	private function createCircleSetting(): CircleSetting {
		return new CircleSetting(
			$this->createMock(CircleRequest::class),
			$this->createMock(ShareTokenService::class),
			$this->configService,
			$this->auditLogService,
		);
	}

	public function testCircleSettingSet(): void {
		$event = $this->createEvent(CircleSetting::class);
		$event->setParams(new SimpleDataStore(['setting' => 'password_single', 'value' => 'hashed-secret', 'unset' => false]));
		$event->getData()->sArray('settings', ['password_single' => 'hashed-secret']);

		$this->createCircleSetting()->manage($event);

		$this->assertAuditEvent(Operation::TeamSettingUpdated, 'Setting "%s" of team %s ("%s") was set by "%s"', [
			'setting' => 'password_single',
			'circleId' => 'circle1',
			'name' => 'Team',
			'initiator' => 'alice',
		]);
	}

	public function testCircleSettingUnset(): void {
		$event = $this->createEvent(CircleSetting::class);
		$event->setParams(new SimpleDataStore(['setting' => 'enforce_password', 'value' => null, 'unset' => true]));
		$event->getData()->sArray('settings', []);

		$this->createCircleSetting()->manage($event);

		$this->assertAuditEvent(Operation::TeamSettingUpdated, 'Setting "%s" of team %s ("%s") was removed by "%s"', [
			'setting' => 'enforce_password',
			'circleId' => 'circle1',
			'name' => 'Team',
			'initiator' => 'alice',
		]);
	}

	public function testCircleCreateInvitation(): void {
		$repository = $this->createMock(CircleInvitationRepository::class);
		$repository->expects($this->once())
			->method('replace')
			->willReturnArgument(0);
		$item = new CircleCreateInvitation($repository, $this->createMock(EventService::class), $this->auditLogService);

		$invitation = new CircleInvitation();
		$invitation->circleId = 'circle1';
		$invitation->invitationCode = 'SECRETCODE';
		$event = $this->createEvent(CircleCreateInvitation::class);
		$event->getData()->sObj('circle_invitation', $invitation);
		$item->manage($event);

		$this->assertAuditEvent(Operation::InvitationCreated, 'Invitation link of team %s ("%s") was created by "%s"', [
			'circleId' => 'circle1',
			'name' => 'Team',
			'initiator' => 'alice',
		]);
	}

	public function testCircleRevokeInvitation(): void {
		$repository = $this->createMock(CircleInvitationRepository::class);
		$repository->method('deleteBy')
			->with(['circleId' => 'circle1'])
			->willReturn(1);
		$item = new CircleRevokeInvitation($repository, $this->createMock(EventService::class), $this->auditLogService);

		$item->manage($this->createEvent(CircleRevokeInvitation::class));

		$this->assertAuditEvent(Operation::InvitationRevoked, 'Invitation link of team %s ("%s") was revoked by "%s"', [
			'circleId' => 'circle1',
			'name' => 'Team',
			'initiator' => 'alice',
		]);
	}

	public function testCircleRevokeInvitationWithoutInvitationNotLogged(): void {
		$repository = $this->createMock(CircleInvitationRepository::class);
		$repository->method('deleteBy')
			->willReturn(0);
		$item = new CircleRevokeInvitation($repository, $this->createMock(EventService::class), $this->auditLogService);

		$item->manage($this->createEvent(CircleRevokeInvitation::class));

		$this->assertSame([], $this->auditEvents);
	}
}
