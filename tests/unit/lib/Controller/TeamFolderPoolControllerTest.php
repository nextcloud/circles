<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\Controller;

use OCA\Circles\Controller\TeamFolderPoolController;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Model\Circle;
use OCA\Circles\Service\AuditLogService;
use OCA\Circles\Service\TeamFolderPolicy;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\OCS\OCSException;
use OCP\IRequest;
use OCP\Teams\ITeamFolderPoolProvider;
use OCP\Teams\ITeamManager;
use OCP\Teams\TeamFolderPoolQuotaException;
use OCP\Teams\TeamFolderStoragePool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TeamFolderPoolControllerTest extends TestCase {
	private TeamFolderPoolController $controller;
	private ITeamManager&MockObject $teamManager;
	private ITeamFolderPoolProvider&MockObject $provider;
	private CircleRequest&MockObject $circles;
	private TeamFolderPolicy&MockObject $policy;

	protected function setUp(): void {
		$this->teamManager = $this->createMock(ITeamManager::class);
		$this->provider = $this->createMock(ITeamFolderPoolProvider::class);
		$this->circles = $this->createMock(CircleRequest::class);
		$this->policy = $this->createMock(TeamFolderPolicy::class);
		$this->controller = new TeamFolderPoolController(
			'circles', $this->createMock(IRequest::class), $this->teamManager,
			$this->circles, $this->policy, $this->createMock(AuditLogService::class),
		);
	}

	public function testUnsupportedProviderIsExplicit(): void {
		$this->assertSame(['supported' => false, 'pools' => []], $this->controller->getPools()->getData());
	}

	public function testReadsProviderAllocations(): void {
		$this->teamManager->method('getTeamFolderProvider')->willReturn($this->provider);
		$pool = new TeamFolderStoragePool('default', null, 100, 60, false);
		$this->provider->method('getStoragePools')->willReturn([$pool]);
		$this->assertSame(['supported' => true, 'pools' => [$pool]], $this->controller->getPools()->getData());
	}

	public function testLimitExceededHasStableConflictResponse(): void {
		$this->teamManager->method('getTeamFolderProvider')->willReturn($this->provider);
		$this->provider->method('setStoragePoolLimit')->willThrowException(new TeamFolderPoolQuotaException(
			new TeamFolderStoragePool('default', null, 50, 60, false),
		));
		$response = $this->controller->setLimit(null, 50);
		$this->assertSame(409, $response->getStatus());
		$this->assertSame('storage_pool_exceeded', $response->getData()['code']);
	}

	public function testAssignValidatesTeamAndPreservesPermissions(): void {
		$this->teamManager->method('getTeamFolderProvider')->willReturn($this->provider);
		$this->circles->method('getCircle')->willReturn($this->createMock(Circle::class));
		$this->policy->method('isEligibleCircle')->willReturn(true);
		$this->provider->expects($this->once())->method('assignTeamStoragePool')->with('team1', 'engineering');
		$this->provider->method('getTeamStoragePoolAssignments')->willReturn(['team1' => 'group:engineering']);
		$this->assertSame(['storagePoolId' => 'group:engineering'], $this->controller->assign('team1', 'engineering')->getData());
	}

	public function testIneligibleTeamCannotBeAssigned(): void {
		$this->circles->method('getCircle')->willReturn($this->createMock(Circle::class));
		$this->provider->expects($this->never())->method('assignTeamStoragePool');
		$this->expectException(OCSException::class);
		$this->controller->assign('team1', null);
	}

	public function testEndpointsRequireAdminAndMutationPasswordConfirmation(): void {
		foreach (['getPools', 'setLimit', 'deletePool', 'assign'] as $method) {
			$reflection = new \ReflectionMethod(TeamFolderPoolController::class, $method);
			$this->assertSame([], $reflection->getAttributes(NoAdminRequired::class));
			if ($method !== 'getPools') {
				$this->assertCount(1, $reflection->getAttributes(PasswordConfirmationRequired::class));
			}
		}
	}

	public function testLimitRejectsInvalidJsonValuesWithoutCallingProvider(): void {
		$this->teamManager->method('getTeamFolderProvider')->willReturn($this->provider);
		$this->provider->expects($this->never())->method('setStoragePoolLimit');
		foreach ([-1, 1.5, '100', true, [], 9007199254740992] as $limit) {
			try {
				$this->controller->setLimit(null, $limit);
				$this->fail('Invalid storage pool limit must be rejected');
			} catch (OCSException $exception) {
				$this->assertSame(400, $exception->getCode());
			}
		}
	}

	public function testZeroLimitIsAnEmptyPool(): void {
		$this->teamManager->method('getTeamFolderProvider')->willReturn($this->provider);
		$pool = new TeamFolderStoragePool('default', null, 0, 0, false);
		$this->provider->expects($this->once())->method('setStoragePoolLimit')->with(null, 0)->willReturn($pool);
		$this->assertSame($pool, $this->controller->setLimit(null, 0)->getData());
	}

	public function testAdminCanConfigureTeamPool(): void {
		$this->teamManager->method('getTeamFolderProvider')->willReturn($this->provider);
		$circle = $this->createMock(Circle::class);
		$circle->method('isLocal')->willReturn(true);
		$this->circles->method('getCircle')->with('source')->willReturn($circle);
		$this->policy->method('isEligibleCircle')->willReturn(true);
		$pool = new TeamFolderStoragePool('team:source', null, 100, 60, false, 'source', 17);
		$this->provider->expects($this->once())->method('setStoragePoolLimit')->with(null, 100, 'source')->willReturn($pool);
		$this->assertSame($pool, $this->controller->setLimit(null, 100, 'source')->getData());
	}

	public function testAdminCanMoveToTeamPool(): void {
		$this->teamManager->method('getTeamFolderProvider')->willReturn($this->provider);
		$circle = $this->createMock(Circle::class);
		$circle->method('isLocal')->willReturn(true);
		$this->circles->method('getCircle')->willReturn($circle);
		$this->policy->method('isEligibleCircle')->willReturn(true);
		$this->provider->expects($this->once())->method('assignTeamStoragePool')->with('target', null, false, 'source');
		$this->provider->method('getTeamStoragePoolAssignments')->willReturn(['target' => 'team:source']);
		$this->assertSame(['storagePoolId' => 'team:source'], $this->controller->assign('target', null, 'source')->getData());
	}

	public function testCannotConfigureGroupAndTeamAsSamePool(): void {
		$this->provider->expects($this->never())->method('setStoragePoolLimit');
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(400);
		$this->controller->setLimit('engineering', 100, 'source');
	}

	public function testAdminCanDeleteGroupAndTeamPools(): void {
		$this->teamManager->method('getTeamFolderProvider')->willReturn($this->provider);
		$this->provider->expects($this->exactly(2))->method('deleteStoragePool');
		$this->assertSame([], $this->controller->deletePool('engineering')->getData());
		$this->assertSame([], $this->controller->deletePool(null, 'source')->getData());
	}

	public function testCannotDeleteDefaultPool(): void {
		$this->provider->expects($this->never())->method('deleteStoragePool');
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(400);
		$this->controller->deletePool(null);
	}

	public function testDeleteConflictHasStableResponse(): void {
		$this->teamManager->method('getTeamFolderProvider')->willReturn($this->provider);
		$this->provider->method('deleteStoragePool')->willThrowException(new TeamFolderPoolQuotaException(
			new TeamFolderStoragePool('default', null, 50, 60, false),
		));
		$response = $this->controller->deletePool('engineering');
		$this->assertSame(409, $response->getStatus());
		$this->assertSame('storage_pool_exceeded', $response->getData()['code']);
	}

	public function testNullLimitIsUnlimited(): void {
		$this->teamManager->method('getTeamFolderProvider')->willReturn($this->provider);
		$pool = new TeamFolderStoragePool('default', null, null, 100, false);
		$this->provider->expects($this->once())->method('setStoragePoolLimit')->with(null, null)->willReturn($pool);
		$this->assertSame($pool, $this->controller->setLimit(null, null)->getData());
	}
}
