<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\Controller;

use OCA\Circles\Controller\TeamResourceFavoritesController;
use OCA\Circles\Exceptions\InsufficientPermissionException;
use OCA\Circles\Service\PermissionService;
use OCP\AppFramework\OCS\OCSException;
use OCP\Config\IUserConfig;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TeamResourceFavoritesControllerTest extends TestCase {
	private IUserConfig&MockObject $userConfig;
	private PermissionService&MockObject $permissionService;
	private TeamResourceFavoritesController $controller;

	protected function setUp(): void {
		$this->userConfig = $this->createMock(IUserConfig::class);
		$this->permissionService = $this->createMock(PermissionService::class);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($user);

		$this->controller = new TeamResourceFavoritesController(
			'circles',
			$this->createMock(IRequest::class),
			$this->userConfig,
			$this->permissionService,
			$userSession,
		);
	}

	public function testGetReturnsTheStoredFavorites(): void {
		$this->userConfig->expects(self::once())
			->method('getValueArray')
			->with('alice', 'circles', 'teamsFavoriteResources_team-a', [], true)
			->willReturn(['page-42', 'team-folder']);

		self::assertSame(['favorites' => ['page-42', 'team-folder']], $this->controller->getFavorites('team-a')->getData());
	}

	public function testSetStoresTheSanitizedList(): void {
		$this->userConfig->expects(self::once())
			->method('setValueArray')
			->with('alice', 'circles', 'teamsFavoriteResources_team-a', ['page-42', 'board-1'], true);

		$response = $this->controller->setFavorites('team-a', ['page-42', '', 7, 'board-1', 'page-42', str_repeat('x', 65)]);

		self::assertSame(['favorites' => ['page-42', 'board-1']], $response->getData());
	}

	public function testNonMembersAreRejected(): void {
		$this->permissionService->method('userMustBeMember')->willThrowException(new InsufficientPermissionException('Not a member'));
		$this->userConfig->expects(self::never())->method('setValueArray');

		$this->expectException(OCSException::class);
		$this->controller->setFavorites('team-a', ['page-42']);
	}
}
