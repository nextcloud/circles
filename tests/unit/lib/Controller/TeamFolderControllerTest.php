<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Unit\Controller;

use OCA\Circles\Controller\TeamFolderController;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Model\Circle;
use OCA\Circles\Service\PermissionService;
use OCA\Circles\Service\TeamFolderPolicy;
use OCP\AppFramework\OCS\OCSException;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Teams\ITeamFolderPoolProvider;
use OCP\Teams\ITeamFolderProvider;
use OCP\Teams\ITeamManager;
use OCP\Teams\TeamFolder;
use OCP\Teams\TeamFolderPoolQuotaException;
use OCP\Teams\TeamFolderStoragePool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TeamFolderControllerTest extends TestCase {
	private TeamFolderController $controller;
	private TeamFolderPolicy&MockObject $policy;
	private ITeamManager&MockObject $teamManager;
	private CircleRequest&MockObject $circleRequest;
	private PermissionService&MockObject $permissionService;
	private IGroupManager&MockObject $groupManager;
	private IUserSession&MockObject $userSession;

	protected function setUp(): void {
		parent::setUp();

		$this->policy = $this->createMock(TeamFolderPolicy::class);
		$this->teamManager = $this->createMock(ITeamManager::class);
		$this->circleRequest = $this->createMock(CircleRequest::class);
		$this->permissionService = $this->createMock(PermissionService::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userSession = $this->createMock(IUserSession::class);

		$this->controller = new TeamFolderController(
			'circles',
			$this->createMock(IRequest::class),
			$this->teamManager,
			$this->policy,
			$this->circleRequest,
			$this->permissionService,
			$this->groupManager,
			$this->userSession,
		);
	}

	public function testUpgradeTeamFolderForbiddenWhenProvisioningDisabled(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('owner');
		$this->userSession->method('getUser')->willReturn($user);
		$this->circleRequest->method('getCircle')->with('team1')->willReturn($this->createMock(Circle::class));
		$this->policy->method('isTeamFolderProvisioningEnabled')->willReturn(false);
		$this->groupManager->method('isAdmin')->with('owner')->willReturn(false);

		$this->expectException(OCSException::class);
		$this->expectExceptionMessage('Team folder provisioning is disabled');

		$this->controller->upgradeTeamFolder('team1');
	}

	public function testCreationPoolsAreScopedToAuthenticatedUser(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->userSession->method('getUser')->willReturn($user);
		$options = ['defaultPoolId' => 'default', 'pools' => [['id' => 'default', 'label' => '']]];
		$this->policy->expects($this->once())->method('getCreationStoragePoolOptions')->with('alice')->willReturn($options);

		$this->assertSame($options, $this->controller->getCreationStoragePools()->getData());
	}

	public function testUpgradeTeamFolderAllowedForServerAdminWhenProvisioningDisabled(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('admin');
		$this->userSession->method('getUser')->willReturn($user);

		$circle = $this->createMock(Circle::class);
		$circle->method('getSingleId')->willReturn('team1');
		$circle->method('getDisplayName')->willReturn('Team One');
		$this->circleRequest->method('getCircle')->with('team1')->willReturn($circle);

		$folder = $this->createMock(TeamFolder::class);
		$folder->method('getId')->willReturn(42);
		$folder->method('jsonSerialize')->willReturn(['id' => 42, 'mountPoint' => 'Team One']);
		$provider = $this->createMock(ITeamFolderProvider::class);
		$provider->expects($this->once())->method('createTeamFolder')->willReturn($folder);
		$this->teamManager->method('getTeamFolderProvider')->willReturn($provider);

		$this->policy->method('isTeamFolderProvisioningEnabled')->willReturn(false);
		$this->policy->method('isEligibleCircle')->with($circle)->willReturn(true);
		$this->policy->method('getDefaultQuota')->willReturn(0);
		$this->groupManager->method('isAdmin')->with('admin')->willReturn(true);

		$response = $this->controller->upgradeTeamFolder('team1');

		$this->assertSame(42, $response->getData()['folderId']);
	}

	public function testGetLinkableTeamFoldersReturnsDataResponse(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('admin');
		$this->userSession->method('getUser')->willReturn($user);

		$provider = $this->createMock(ITeamFolderProvider::class);
		$provider->method('getLinkableTeamFolders')->with('team1')->willReturn([
			new TeamFolder(56, 'teamfolderda?'),
		]);
		$this->teamManager->method('getTeamFolderProvider')->willReturn($provider);

		$response = $this->controller->getLinkableTeamFolders('team1');

		$this->assertSame([
			['id' => 56, 'quota' => null, 'mountPoint' => 'teamfolderda?'],
		], $response->getData());
	}

	public function testLinkTeamFolderReturnsDataResponse(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('admin');
		$this->userSession->method('getUser')->willReturn($user);

		$folder = new TeamFolder(56, 'teamfolderda?');
		$provider = $this->createMock(ITeamFolderProvider::class);
		$provider->method('linkTeamFolder')->with('team1', 56)->willReturn($folder);
		$this->teamManager->method('getTeamFolderProvider')->willReturn($provider);

		$response = $this->controller->linkTeamFolder('team1', 56);

		$this->assertSame([
			'success' => true,
			'folderId' => 56,
			'folder' => ['id' => 56, 'quota' => null, 'mountPoint' => 'teamfolderda?'],
		], $response->getData());
	}

	public function testUpgradeTeamFolderForbiddenForIneligibleCircle(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('admin');
		$this->userSession->method('getUser')->willReturn($user);

		$circle = $this->createMock(Circle::class);
		$this->policy->method('isTeamFolderProvisioningEnabled')->willReturn(true);
		$this->policy->method('isEligibleCircle')->with($circle)->willReturn(false);
		$this->circleRequest->method('getCircle')->with('team1')->willReturn($circle);

		$this->expectException(OCSException::class);
		$this->expectExceptionMessage('This team cannot have a team folder');

		$this->controller->upgradeTeamFolder('team1');
	}

	public function testQuotaUpdateReportsPoolConflict(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('owner');
		$this->userSession->method('getUser')->willReturn($user);
		$provider = $this->createMock(ITeamFolderProvider::class);
		$this->teamManager->method('getTeamFolderProvider')->willReturn($provider);
		$provider->method('updateTeamFolderQuota')->willThrowException(new TeamFolderPoolQuotaException(
			new TeamFolderStoragePool('default', null, 100, 101, false),
		));
		$response = $this->controller->updateTeamFolderQuota('team1', 101);
		$this->assertSame(409, $response->getStatus());
		$this->assertSame('storage_pool_exceeded', $response->getData()['code']);
	}

	public function testOwnerCannotRetainAndUnlinkPooledFolder(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('owner');
		$this->userSession->method('getUser')->willReturn($user);
		$provider = $this->createMock(ITeamFolderPoolProvider::class);
		$this->teamManager->method('getTeamFolderProvider')->willReturn($provider);
		$provider->method('getTeamStoragePoolAssignments')->willReturn(['team1' => 'default']);
		$provider->expects($this->never())->method('unlinkTeamFolder');
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(403);
		$this->controller->unlinkTeamFolder('team1');
	}

	public function testInvalidJsonQuotasCannotReachProvider(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('owner');
		$this->userSession->method('getUser')->willReturn($user);
		$provider = $this->createMock(ITeamFolderProvider::class);
		$this->teamManager->method('getTeamFolderProvider')->willReturn($provider);
		$provider->expects($this->never())->method('updateTeamFolderQuota');
		foreach ([-1, 1.5, '100', true, null, [], 9007199254740992] as $quota) {
			try {
				$this->controller->updateTeamFolderQuota('team1', $quota);
				$this->fail('Invalid folder quota must be rejected');
			} catch (OCSException $exception) {
				$this->assertSame(400, $exception->getCode());
			}
		}
	}

	public function testInvalidProviderArgumentsReturnBadRequest(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('owner');
		$this->userSession->method('getUser')->willReturn($user);
		$this->circleRequest->method('getCircle')->willReturn($this->createMock(Circle::class));
		$this->policy->method('isEligibleCircle')->willReturn(true);
		$this->policy->method('isTeamFolderProvisioningEnabled')->willReturn(true);
		$provider = $this->createMock(ITeamFolderProvider::class);
		$this->teamManager->method('getTeamFolderProvider')->willReturn($provider);
		foreach (['createTeamFolder', 'linkTeamFolder', 'updateTeamFolderQuota'] as $method) {
			$provider->method($method)->willThrowException(new \InvalidArgumentException('The financing group no longer exists'));
		}
		foreach ([['upgradeTeamFolder', ['team1']], ['linkTeamFolder', ['team1', 42]], ['updateTeamFolderQuota', ['team1', 100]]] as [$method, $arguments]) {
			try {
				$this->controller->$method(...$arguments);
				$this->fail('Invalid provider arguments must be rejected');
			} catch (OCSException $exception) {
				$this->assertSame(400, $exception->getCode());
				$this->assertSame('The financing group no longer exists', $exception->getMessage());
			}
		}
	}
}
