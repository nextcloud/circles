<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Controller;

use OCA\Circles\AuditLog\Operation;
use OCA\Circles\ConfigLexicon;
use OCA\Circles\Controller\SettingsController;
use OCA\Circles\Service\AuditLogService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SettingsControllerTest extends TestCase {
	private IAppConfig&MockObject $appConfig;
	private AuditLogService&MockObject $auditLogService;
	private SettingsController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->auditLogService = $this->createMock(AuditLogService::class);

		$this->controller = new SettingsController(
			'circles',
			$this->createMock(IRequest::class),
			$this->appConfig,
			$this->createMock(IGroupManager::class),
			$this->auditLogService,
		);
	}

	public static function dataSetValue(): array {
		return [
			[ConfigLexicon::FEDERATED_TEAMS_ENABLED, 'yes', 'yes'],
			[ConfigLexicon::FEDERATED_TEAMS_ENABLED, 'anything', 'no'],
			[ConfigLexicon::FEDERATED_TEAMS_FRONTAL, 'https://cloud.example.com/', 'https://cloud.example.com/'],
			[ConfigLexicon::TEAM_FOLDER_DEFAULT_QUOTA, '1024', '1024'],
			[ConfigLexicon::TEAM_FOLDER_GROUP_QUOTAS, '{"admin":2048}', '{"admin":2048}'],
		];
	}

	#[DataProvider('dataSetValue')]
	public function testSetValue(string $key, string $value, string $loggedValue): void {
		$this->auditLogService->expects($this->once())
			->method('log')
			->with(Operation::ConfigUpdated, 'Teams setting "%s" was set to "%s"', [
				'key' => $key,
				'value' => $loggedValue,
			]);

		$this->assertSame(Http::STATUS_OK, $this->controller->setValue($key, $value)->getStatus());
	}

	public static function dataSetInvalidValue(): array {
		return [
			[ConfigLexicon::FEDERATED_TEAMS_FRONTAL, 'not a url'],
			[ConfigLexicon::TEAM_FOLDER_DEFAULT_QUOTA, '-1'],
			[ConfigLexicon::TEAM_FOLDER_GROUP_QUOTAS, '{"admin":-1}'],
			['unknown_key', 'value'],
		];
	}

	#[DataProvider('dataSetInvalidValue')]
	public function testSetInvalidValueNotLogged(string $key, string $value): void {
		foreach (['setAppValueString', 'setAppValueBool', 'setAppValueInt', 'setAppValueArray'] as $method) {
			$this->appConfig->expects($this->never())
				->method($method);
		}
		$this->auditLogService->expects($this->never())
			->method('log');

		$this->assertSame(Http::STATUS_BAD_REQUEST, $this->controller->setValue($key, $value)->getStatus());
	}
}
