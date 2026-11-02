<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Service;

use OCA\Circles\AuditLog\Operation;
use OCA\Circles\Db\RemoteRequest;
use OCA\Circles\Exceptions\RemoteAlreadyExistsException;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Service\AuditLogService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\InterfaceService;
use OCA\Circles\Service\RemoteStreamService;
use OCP\IConfig;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class RemoteStreamServiceTest extends TestCase {
	private RemoteRequest&MockObject $remoteRequest;
	private ConfigService&MockObject $configService;
	private AuditLogService&MockObject $auditLogService;
	private RemoteStreamService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->remoteRequest = $this->createMock(RemoteRequest::class);
		$this->configService = $this->createMock(ConfigService::class);
		$this->auditLogService = $this->createMock(AuditLogService::class);

		$this->service = new RemoteStreamService(
			$this->createMock(IConfig::class),
			$this->createMock(IURLGenerator::class),
			$this->remoteRequest,
			$this->createMock(InterfaceService::class),
			$this->configService,
			$this->auditLogService,
		);
	}

	private function createRemoteInstance(): RemoteInstance {
		$remoteInstance = new RemoteInstance();
		$remoteInstance->setInstance('remote.example.com')
			->setType(RemoteInstance::TYPE_TRUSTED)
			->setInterface(InterfaceService::IFACE_FRONTAL);

		return $remoteInstance;
	}

	public function testSaveRemoteInstance(): void {
		$remoteInstance = $this->createRemoteInstance();
		$this->remoteRequest->expects($this->once())
			->method('save')
			->with($remoteInstance);
		$this->auditLogService->expects($this->once())
			->method('log')
			->with(Operation::RemoteInstanceAdded, 'Remote instance "%s" was added as %s using the %s interface', [
				'instance' => 'remote.example.com',
				'type' => 'Trusted',
				'interface' => 'frontal',
			]);

		$this->service->saveRemoteInstance($remoteInstance);
	}

	public function testAddLocalInstanceNotLogged(): void {
		$this->configService->method('isLocalInstance')
			->willReturn(true);
		$this->remoteRequest->expects($this->never())
			->method('save');
		$this->auditLogService->expects($this->never())
			->method('log');

		$this->expectException(RemoteAlreadyExistsException::class);
		$this->service->addRemoteInstance('cloud.example.com');
	}

	public function testRemoveRemoteInstance(): void {
		$this->remoteRequest->expects($this->once())
			->method('deleteInstance')
			->with('remote.example.com')
			->willReturn(1);
		$this->auditLogService->expects($this->once())
			->method('log')
			->with(Operation::RemoteInstanceRemoved, 'Remote instance "%s" was removed', [
				'instance' => 'remote.example.com',
			]);

		$this->service->removeRemoteInstance('remote.example.com');
	}

	public function testRemoveUnknownRemoteInstanceNotLogged(): void {
		$this->remoteRequest->method('deleteInstance')
			->willReturn(0);
		$this->auditLogService->expects($this->never())
			->method('log');

		$this->service->removeRemoteInstance('unknown.example.com');
	}

	public function testUpdateIdentity(): void {
		$this->remoteRequest->expects($this->once())
			->method('update');
		$this->auditLogService->expects($this->once())
			->method('log')
			->with(Operation::RemoteInstanceUpdated, 'Identity of remote instance "%s" was changed, it is known as %s', [
				'instance' => 'remote.example.com',
				'type' => 'Trusted',
			]);

		$this->service->update($this->createRemoteInstance());
	}

	public function testUpdateType(): void {
		$this->remoteRequest->expects($this->once())
			->method('updateType');
		$this->auditLogService->expects($this->once())
			->method('log')
			->with(Operation::RemoteInstanceUpdated, 'Type of remote instance "%s" was changed to %s', [
				'instance' => 'remote.example.com',
				'type' => 'Trusted',
			]);

		$this->service->update($this->createRemoteInstance(), RemoteStreamService::UPDATE_TYPE);
	}

	public function testUpdateItemNotLogged(): void {
		$this->remoteRequest->expects($this->once())
			->method('updateItem');
		$this->auditLogService->expects($this->never())
			->method('log');

		$this->service->update($this->createRemoteInstance(), RemoteStreamService::UPDATE_ITEM);
	}
}
