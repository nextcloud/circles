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
	private InterfaceService&MockObject $interfaceService;
	private IRequest&MockObject $request;
	private RemoteController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->remoteStreamService = $this->createMock(RemoteStreamService::class);
		$this->auditLogService = $this->createMock(AuditLogService::class);

		$this->interfaceService = $this->createMock(InterfaceService::class);
		$this->interfaceService->method('getCurrentInterface')->willReturn(InterfaceService::IFACE_INTERNAL);
		$this->request = $this->createMock(IRequest::class);

		$this->controller = new RemoteController(
			'circles',
			$this->request,
			$this->createMock(CircleRequest::class),
			$this->createMock(IAppConfig::class),
			$this->remoteStreamService,
			$this->createMock(RemoteDownstreamService::class),
			$this->createMock(FederatedUserService::class),
			$this->createMock(CircleService::class),
			$this->createMock(MemberService::class),
			$this->createMock(MembershipService::class),
			$this->interfaceService,
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

	public function testAppServiceReturnsNotFoundForUnknownInterface(): void {
		$this->request->method('getHeader')->with('Accept')->willReturn('application/json');
		$this->interfaceService->expects($this->once())->method('setCurrentInterfaceFromRequest');
		$this->interfaceService->method('hasCurrentInterface')->willReturn(false);
		$this->remoteStreamService->expects($this->never())->method('getAppSignatory');

		$this->assertSame(Http::STATUS_NOT_FOUND, $this->controller->appService()->getStatus());
	}

	public function testAppServiceReturnsSignatoryForKnownInterface(): void {
		$this->request->method('getHeader')->with('Accept')->willReturn('application/json');
		$this->request->method('getParam')->with('auth', '')->willReturn('');
		$this->interfaceService->method('hasCurrentInterface')->willReturn(true);
		$signatory = new RemoteInstance();
		$this->remoteStreamService->expects($this->once())
			->method('getAppSignatory')
			->with(false, '')
			->willReturn($signatory);

		$response = $this->controller->appService();

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame($signatory, $response->getData());
	}
}
