<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Command;

use OCA\Circles\AuditLog\Operation;
use OCA\Circles\Command\CirclesScimAddRemoteInstance;
use OCA\Circles\Command\CirclesScimRemoveRemoteInstance;
use OCA\Circles\ConfigLexicon;
use OCA\Circles\Db\RemoteRequest;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Service\AuditLogService;
use OCA\Circles\Service\ScimService;
use OCP\AppFramework\Services\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Test\TestCase;

class CirclesScimRemoteInstanceTest extends TestCase {
	private IAppConfig&MockObject $appConfig;
	private AuditLogService&MockObject $auditLogService;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->auditLogService = $this->createMock(AuditLogService::class);
	}

	private function createAddCommand(): CirclesScimAddRemoteInstance {
		$remoteInstance = new RemoteInstance();
		$remoteInstance->setInstance('remote.example.com');
		$remoteRequest = $this->createMock(RemoteRequest::class);
		$remoteRequest->method('getKnownInstances')
			->willReturn([$remoteInstance]);

		return new CirclesScimAddRemoteInstance($this->appConfig, $remoteRequest, $this->auditLogService);
	}

	public function testAdd(): void {
		$this->appConfig->method('getAppValueArray')
			->willReturn([]);
		$this->appConfig->expects($this->once())
			->method('setAppValueArray')
			->with(ConfigLexicon::SCIM_REMOTE_INSTANCES, ['remote.example.com']);
		$this->auditLogService->expects($this->once())
			->method('log')
			->with(Operation::ScimInstanceAdded, 'Remote instance "%s" was allowed to add members to SCIM teams', [
				'instance' => 'remote.example.com',
			]);

		$this->assertSame(0, $this->createAddCommand()->run(new ArrayInput(['instance' => 'remote.example.com']), new NullOutput()));
	}

	public function testAddAlreadyAddedNotLogged(): void {
		$this->appConfig->method('getAppValueArray')
			->willReturn(['remote.example.com']);
		$this->appConfig->expects($this->never())
			->method('setAppValueArray');
		$this->auditLogService->expects($this->never())
			->method('log');

		$this->assertSame(0, $this->createAddCommand()->run(new ArrayInput(['instance' => 'remote.example.com']), new NullOutput()));
	}

	public function testAddUnknownInstanceNotLogged(): void {
		$this->auditLogService->expects($this->never())
			->method('log');

		$this->assertSame(1, $this->createAddCommand()->run(new ArrayInput(['instance' => 'unknown.example.com']), new NullOutput()));
	}

	public function testRemove(): void {
		$scimService = $this->createMock(ScimService::class);
		$scimService->expects($this->once())
			->method('removeRemoteInstanceMembers')
			->with('remote.example.com');
		$this->appConfig->method('getAppValueArray')
			->willReturn(['other.example.com', 'remote.example.com']);
		$this->appConfig->expects($this->once())
			->method('setAppValueArray')
			->with(ConfigLexicon::SCIM_REMOTE_INSTANCES, ['other.example.com']);
		$this->auditLogService->expects($this->once())
			->method('log')
			->with(Operation::ScimInstanceRemoved, 'Remote instance "%s" is no longer allowed to add members to SCIM teams', [
				'instance' => 'remote.example.com',
			]);

		$command = new CirclesScimRemoveRemoteInstance($this->appConfig, $scimService, $this->auditLogService);
		$this->assertSame(0, $command->run(new ArrayInput(['instance' => 'remote.example.com']), new NullOutput()));
	}

	public function testRemoveUnknownInstanceNotLogged(): void {
		$this->appConfig->method('getAppValueArray')
			->willReturn(['other.example.com']);
		$this->auditLogService->expects($this->never())
			->method('log');

		$command = new CirclesScimRemoveRemoteInstance($this->appConfig, $this->createMock(ScimService::class), $this->auditLogService);
		$this->assertSame(0, $command->run(new ArrayInput(['instance' => 'remote.example.com']), new NullOutput()));
	}
}
