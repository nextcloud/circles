<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\Controller;

use OCA\Circles\Controller\TeamDashboardSettingsController;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Exceptions\InsufficientPermissionException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\PermissionService;
use OCA\Circles\Service\TeamDashboardSettingsService;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\Dashboard\IManager;
use OCP\Dashboard\ITeamWidget;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Teams\ITeamManager;
use PHPUnit\Framework\TestCase;

class TeamDashboardSettingsControllerTest extends TestCase {
	private CircleService $circles;
	private CircleRequest $request;
	private IManager $widgets;
	private PermissionService $permissions;

	private function controller(bool $member = true, bool $admin = true, ?string $stored = null): TeamDashboardSettingsController {
		$this->circles = $this->createMock(CircleService::class);
		$this->request = $this->createMock(CircleRequest::class);
		$this->widgets = $this->createMock(IManager::class);
		$this->permissions = $this->createMock(PermissionService::class);
		$this->permissions->method('userMustBeMember')->willReturn(new Member());
		if (!$admin) {
			$this->permissions->method('memberMustBeAtLeastAdmin')->willThrowException(new InsufficientPermissionException('Denied'));
		}
		$circle = new Circle();
		$circle->setSettings($stored === null ? [] : [TeamDashboardSettingsService::SETTING => $stored]);
		$this->request->method('getCircle')->willReturn($circle);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$teams = $this->createMock(ITeamManager::class);
		$teams->method('isMemberOfTeam')->with('team-a', 'alice')->willReturn($member);
		return new TeamDashboardSettingsController('circles', $this->createMock(IRequest::class), $this->request,
			$this->circles, $this->createMock(FederatedUserService::class), $this->permissions,
			$session, $teams, $this->widgets, new TeamDashboardSettingsService());
	}

	public function testMembersReadSharedSelectionWithoutEditing(): void {
		$controller = $this->controller(admin: false, stored: '["deck-team-today"]');
		self::assertSame(['widgetIds' => ['deck-team-today'], 'canEdit' => false], $controller->getLayout('team-a')->getData());
	}

	public function testNonMembersDoNotReadSettings(): void {
		$controller = $this->controller(member: false);
		$this->request->expects(self::never())->method('getCircle');
		$this->expectException(OCSNotFoundException::class);
		$controller->getLayout('team-a');
	}

	public function testOrdinaryMemberCannotWrite(): void {
		$controller = $this->controller(admin: false);
		$this->circles->expects(self::never())->method('updateSetting');
		$this->widgets->expects(self::never())->method('getTeamWidgets');
		$this->expectException(OCSException::class);
		$controller->setLayout('team-a', []);
	}

	public function testAdminSavesExplicitEmptySelection(): void {
		$controller = $this->controller();
		$this->widgets->method('getTeamWidgets')->willReturn([]);
		$this->circles->expects(self::once())->method('updateSetting')->with('team-a', TeamDashboardSettingsService::SETTING, '[]');
		self::assertSame(['widgetIds' => [], 'canEdit' => true], $controller->setLayout('team-a', [])->getData());
	}

	public function testPreviouslySelectedDisabledWidgetCanBeRetained(): void {
		$controller = $this->controller(stored: '["disabled-widget"]');
		$this->widgets->method('getTeamWidgets')->willReturn(['deck-team-today' => $this->createMock(ITeamWidget::class)]);
		$this->circles->expects(self::once())->method('updateSetting')->with('team-a', TeamDashboardSettingsService::SETTING, '["disabled-widget","deck-team-today"]');
		$controller->setLayout('team-a', ['disabled-widget', 'deck-team-today']);
	}

	public function testResetDoesNotRequireWidgetDiscovery(): void {
		$controller = $this->controller();
		$this->widgets->expects(self::never())->method('getTeamWidgets');
		$this->circles->expects(self::once())->method('updateSetting')->with('team-a', TeamDashboardSettingsService::SETTING, 'null');
		self::assertNull($controller->setLayout('team-a', null)->getData()['widgetIds']);
	}
}
