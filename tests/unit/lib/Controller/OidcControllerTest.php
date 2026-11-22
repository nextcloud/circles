<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Controller;

use OCA\Circles\AuditLog\Operation;
use OCA\Circles\Controller\OidcController;
use OCA\Circles\Service\AuditLogService;
use OCA\Circles\Service\OidcService;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Security\ICredentialsManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class OidcControllerTest extends TestCase {
	private IUserSession&MockObject $userSession;
	private ISession&MockObject $session;
	private IClientService&MockObject $clientService;
	private ICredentialsManager&MockObject $credentialsManager;
	private AuditLogService&MockObject $auditLogService;

	protected function setUp(): void {
		parent::setUp();

		$this->userSession = $this->createMock(IUserSession::class);
		$this->session = $this->createMock(ISession::class);
		$this->clientService = $this->createMock(IClientService::class);
		$this->credentialsManager = $this->createMock(ICredentialsManager::class);
		$this->auditLogService = $this->createMock(AuditLogService::class);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->userSession->method('getUser')->willReturn($user);
	}

	private function createController(string $sessionUserId = 'alice'): OidcController {
		$this->session->method('get')->willReturnMap([
			['circles.oidc.state', 'state'],
			['circles.oidc.user_id', $sessionUserId],
		]);

		return new OidcController(
			$this->createMock(IRequest::class),
			$this->createMock(IAppConfig::class),
			$this->userSession,
			$this->session,
			$this->createMock(IURLGenerator::class),
			$this->clientService,
			$this->credentialsManager,
			$this->createMock(OidcService::class),
			$this->auditLogService,
			$this->createMock(LoggerInterface::class),
		);
	}

	private function expectFailedLink(string $reason): void {
		$this->credentialsManager->expects($this->never())->method('store');
		$this->auditLogService->expects($this->once())
			->method('log')
			->with(Operation::OidcAccountLinkFailed, 'Linking the OIDC account of "%s" failed: %s', [
				'userId' => 'alice',
				'reason' => $reason,
			]);
	}

	private function mockTokenResponse(array $data): void {
		$response = $this->createMock(IResponse::class);
		$response->method('getBody')->willReturn(json_encode($data));
		$client = $this->createMock(IClient::class);
		$client->method('post')->willReturn($response);
		$this->clientService->method('newClient')->willReturn($client);
	}

	public function testCallbackWritesAuditLog(): void {
		$this->mockTokenResponse(['refresh_token' => 'refresh']);
		$this->credentialsManager->expects($this->once())->method('store');
		$this->auditLogService->expects($this->once())
			->method('log')
			->with(Operation::OidcAccountLinked, 'OIDC account of "%s" linked to synchronize team memberships', [
				'userId' => 'alice',
			]);

		$this->createController()->callback('state', 'code');
	}

	public static function dataCallbackFailed(): array {
		return [
			'provider error' => ['state', 'access_denied', 'provider_error'],
			'state mismatch' => ['other', '', 'state_mismatch'],
		];
	}

	#[DataProvider('dataCallbackFailed')]
	public function testCallbackFailedWritesAuditLog(string $state, string $error, string $reason): void {
		$this->expectFailedLink($reason);

		$this->createController()->callback($state, 'code', $error);
	}

	public function testCallbackUserMismatchWritesAuditLog(): void {
		$this->expectFailedLink('user_mismatch');

		$this->createController('bob')->callback('state', 'code');
	}

	public function testCallbackTokenExchangeFailedWritesAuditLog(): void {
		$client = $this->createMock(IClient::class);
		$client->method('post')->willThrowException(new \Exception('connection refused'));
		$this->clientService->method('newClient')->willReturn($client);
		$this->expectFailedLink('token_exchange_failed');

		$this->createController()->callback('state', 'code');
	}

	public function testCallbackWithoutRefreshTokenWritesAuditLog(): void {
		$this->mockTokenResponse([]);
		$this->expectFailedLink('missing_refresh_token');

		$this->createController()->callback('state', 'code');
	}
}
