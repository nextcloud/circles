<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Controller;

use OCA\Circles\Controller\TeamDashboardController;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Exceptions\InsufficientPermissionException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\PermissionService;
use OCP\App\IAppManager;
use OCP\AppFramework\OCS\OCSException;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class TeamDashboardControllerTest extends TestCase {
	private CircleRequest&MockObject $circles;
	private CircleService&MockObject $service;
	private FederatedUserService&MockObject $federated;
	private PermissionService&MockObject $permissions;
	private IUserSession&MockObject $session;
	private IAppManager&MockObject $apps;
	private TeamDashboardController $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->circles = $this->createMock(CircleRequest::class);
		$this->service = $this->createMock(CircleService::class);
		$this->federated = $this->createMock(FederatedUserService::class);
		$this->permissions = $this->createMock(PermissionService::class);
		$this->session = $this->createMock(IUserSession::class);
		$this->apps = $this->createMock(IAppManager::class);
		$this->controller = new TeamDashboardController('circles', $this->createMock(IRequest::class), $this->circles, $this->service, $this->federated, $this->permissions, $this->session, $this->apps);
	}

	private function authenticate(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->session->method('getUser')->willReturn($user);
		$this->permissions->method('userMustBeMember')->with('alice', 'team')->willReturn(new Member());
	}

	public static function layouts(): array {
		return [[[], null], [['teamsDashboardWidgetIds' => 'null'], null], [['teamsDashboardWidgetIds' => 'broken'], null], [['teamsDashboardWidgetIds' => '{}'], null], [['teamsDashboardWidgetIds' => '["a","a","",3,"b"]'], ['a', 'b']]];
	}

	#[DataProvider('layouts')]
	public function testReadLayout(array $settings, ?array $expected): void {
		$this->authenticate();
		$circle = new Circle();
		$circle->setSettings($settings);
		$this->circles->method('getCircle')->with('team')->willReturn($circle);
		$this->apps->method('isEnabledForUser')->with('dashboard')->willReturn(true);
		$this->assertSame(['widgetIds' => $expected, 'canEdit' => true, 'available' => true], $this->controller->getLayout('team')->getData());
	}

	public function testMemberCannotEditAndDashboardIsUnavailable(): void {
		$this->authenticate();
		$this->circles->method('getCircle')->willReturn(new Circle());
		$this->permissions->method('memberMustBeAtLeastAdmin')->willThrowException(new InsufficientPermissionException());
		$this->apps->method('isEnabledForUser')->willReturn(false);
		$data = $this->controller->getLayout('team')->getData();
		$this->assertFalse($data['canEdit']);
		$this->assertFalse($data['available']);
	}

	public static function selections(): array {
		return [[null, 'null', null], [[], '[]', []], [['a', '', 'a', 2, str_repeat('x', 65), 'b'], '["a","b"]', ['a', 'b']]];
	}

	#[DataProvider('selections')]
	public function testSaveLayout(?array $input, string $stored, ?array $expected): void {
		$this->authenticate();
		$this->federated->expects($this->once())->method('setLocalCurrentUser');
		$this->service->expects($this->once())->method('updateSetting')->with('team', 'teamsDashboardWidgetIds', $stored);
		$this->assertSame(['widgetIds' => $expected, 'canEdit' => true], $this->controller->setLayout('team', $input)->getData());
	}

	public function testNonAdminCannotSave(): void {
		$this->authenticate();
		$this->permissions->method('memberMustBeAtLeastAdmin')->willThrowException(new InsufficientPermissionException());
		$this->service->expects($this->never())->method('updateSetting');
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(403);
		$this->controller->setLayout('team', []);
	}

	public function testNoSession(): void {
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(401);
		$this->controller->getLayout('team');
	}

	public function testNonMember(): void {
		$user = $this->createMock(IUser::class);
		$this->session->method('getUser')->willReturn($user);
		$this->permissions->method('userMustBeMember')->willThrowException(new InsufficientPermissionException());
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(403);
		$this->controller->getLayout('team');
	}

}
