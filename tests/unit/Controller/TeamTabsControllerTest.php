<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Controller;

use OCA\Circles\Controller\TeamTabsController;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\PermissionService;
use OCA\Circles\Service\TeamSettingsService;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use Test\TestCase;

class TeamTabsControllerTest extends TestCase {
	public function testSharedSanitizerBounds(): void {
		$this->assertSame(['a', 'b'], TeamSettingsService::sanitizeIdList(['a', '', 'a', 1, str_repeat('x', 65), 'b']));
		$this->assertCount(100, TeamSettingsService::sanitizeIdList(array_map('strval', range(1, 150))));
		$this->assertSame([str_repeat('x', 64)], TeamSettingsService::sanitizeIdList([str_repeat('x', 64)]));
	}

	public function testReadAndSaveStillUseTheSameSanitizer(): void {
		$circles = $this->createMock(CircleRequest::class);
		$circle = new Circle();
		$circle->setSettings(['teamsTabOrder' => '["home","home","",2,"overview"]']);
		$circles->method('getCircle')->willReturn($circle);
		$service = $this->createMock(CircleService::class);
		$service->expects($this->once())->method('updateSetting')->with('team', 'teamsTabOrder', '["home","overview"]');
		$permissions = $this->createMock(PermissionService::class);
		$permissions->method('userMustBeMember')->willReturn(new Member());
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($this->createMock(IUser::class));
		$controller = new TeamTabsController('circles', $this->createMock(IRequest::class), $circles, $service, $this->createMock(FederatedUserService::class), $permissions, $session);
		$this->assertSame(['order' => ['home', 'overview']], $controller->getTabOrder('team')->getData());
		$this->assertSame(['order' => ['home', 'overview']], $controller->setTabOrder('team', ['home', 'home', '', 2, 'overview'])->getData());
	}
}
