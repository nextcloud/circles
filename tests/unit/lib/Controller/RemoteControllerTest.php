<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Controller;

use OCA\Circles\AuditLog\Operation;
use OCA\Circles\Controller\RemoteController;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\AuditLogService;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\InterfaceService;
use OCA\Circles\Service\MemberService;
use OCA\Circles\Service\MembershipService;
use OCA\Circles\Service\RemoteDownstreamService;
use OCA\Circles\Service\RemoteStreamService;
use OCA\Circles\Tools\Model\NCSignatory;
use OCA\Circles\Tools\Model\NCSignedRequest;
use OCP\AppFramework\Http;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IRequest;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class RemoteControllerTest extends TestCase {
	private RemoteStreamService&MockObject $remoteStreamService;
	private AuditLogService&MockObject $auditLogService;
	private RemoteController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->remoteStreamService = $this->createMock(RemoteStreamService::class);
		$this->auditLogService = $this->createMock(AuditLogService::class);

		$interfaceService = $this->createMock(InterfaceService::class);
		$interfaceService->method('getCurrentInterface')->willReturn(InterfaceService::IFACE_INTERNAL);

		$this->controller = new RemoteController(
			'circles',
			$this->createMock(IRequest::class),
			$this->createMock(CircleRequest::class),
			$this->createMock(IAppConfig::class),
			$this->remoteStreamService,
			$this->createMock(RemoteDownstreamService::class),
			$this->createMock(FederatedUserService::class),
			$this->createMock(CircleService::class),
			$this->createMock(MemberService::class),
			$this->createMock(MembershipService::class),
			$interfaceService,
			$this->createMock(ConfigService::class),
			$this->createMock(IUserSession::class),
			$this->auditLogService,
		);
	}

	private function expectRejectedRequest(?string $instance, string $reason): void {
		$this->auditLogService->expects($this->once())
			->method('log')
			->with(Operation::RemoteRequestRejected, 'Federated request from instance "%s" rejected: %s', [
				'instance' => $instance,
				'reason' => $reason,
			]);
	}

	private function mockSignedRequest(NCSignatory $signatory, string $body = '{}'): void {
		$signedRequest = $this->createMock(NCSignedRequest::class);
		$signedRequest->method('getSignatory')->willReturn($signatory);
		$signedRequest->method('getOrigin')->willReturn('remote.example');
		$signedRequest->method('getBody')->willReturn($body);
		$this->remoteStreamService->method('incomingSignedRequest')->willReturn($signedRequest);
	}

	private function createRemoteInstance(string $type): RemoteInstance {
		$remoteInstance = new RemoteInstance();
		$remoteInstance->setType($type);
		$remoteInstance->setInstance('remote.example');

		return $remoteInstance;
	}

	public function testUnknownSignatoryWritesAuditLog(): void {
		$this->mockSignedRequest(new NCSignatory());
		$this->expectRejectedRequest('remote.example', 'unknown_signatory');

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $this->controller->event()->getStatus());
	}

	public function testUntrustedInstanceWritesAuditLog(): void {
		$this->mockSignedRequest($this->createRemoteInstance(RemoteInstance::TYPE_UNKNOWN));
		$this->expectRejectedRequest('remote.example', 'untrusted_instance');

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $this->controller->event()->getStatus());
	}

	public function testInitiatorMismatchWritesAuditLog(): void {
		$this->mockSignedRequest($this->createRemoteInstance(RemoteInstance::TYPE_TRUSTED), json_encode([
			'initiator' => [
				'id' => 'singleId',
				'userId' => 'alice',
				'userType' => Member::TYPE_USER,
				'instance' => 'other.example',
			],
		]));
		$this->expectRejectedRequest('remote.example', 'initiator_mismatch');

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $this->controller->circles()->getStatus());
	}
}
