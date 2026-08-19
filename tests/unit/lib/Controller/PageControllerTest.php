<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Controller;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\ConfigLexicon;
use OCA\Circles\Controller\PageController;
use OCA\Circles\Service\ConfigService;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Services\IInitialState;
use OCP\IRequest;
use OCP\Teams\ITeamFolderProvider;
use OCP\Teams\ITeamManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

final class PageControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private ConfigService&MockObject $configService;
	private IAppConfig&MockObject $appConfig;
	private IInitialState&MockObject $initialState;
	private ITeamManager&MockObject $teamManager;
	private PageController $pageController;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->configService = $this->createMock(ConfigService::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->teamManager = $this->createMock(ITeamManager::class);

		$this->pageController = new PageController(
			$this->request,
			$this->configService,
			$this->appConfig,
			$this->initialState,
			$this->teamManager,
		);
	}

	/**
	 * Assert that index() provides the team-folder initial state and returns
	 * the SPA template when the frontend is enabled and a folder provider is
	 * registered.
	 */
	public function testIndexProvidesTeamFolderStateWithProvider(): void {
		$this->configService->expects($this->once())
			->method('getAppValueBool')
			->with(ConfigService::FRONTEND_ENABLED)
			->willReturn(true);

		$this->appConfig->expects($this->once())
			->method('getAppValueBool')
			->with(ConfigLexicon::TEAM_FOLDER_AUTO_CREATE, true)
			->willReturn(true);

		$provider = $this->createMock(ITeamFolderProvider::class);
		$this->teamManager->expects($this->once())
			->method('getTeamFolderProvider')
			->willReturn($provider);

		$this->initialState->expects($this->exactly(2))
			->method('provideInitialState')
			->willReturnCallback(function (string $key, mixed $value): void {
				match ($key) {
					'teamFolderAutoCreate' => $this->assertTrue($value),
					'teamFolderProviderAvailable' => $this->assertTrue($value),
					default => $this->fail("Unexpected initial state key: $key"),
				};
			});

		$result = $this->pageController->index();

		$this->assertInstanceOf(TemplateResponse::class, $result);
		$this->assertSame(Application::APP_ID, $result->getApp());
		$this->assertSame('main', $result->getTemplateName());
	}

	/**
	 * Auto-create disabled must be reflected in the initial state, while the
	 * provider is still available.
	 */
	public function testIndexProvidesAutoCreateDisabled(): void {
		$this->configService->expects($this->once())
			->method('getAppValueBool')
			->with(ConfigService::FRONTEND_ENABLED)
			->willReturn(true);

		$this->appConfig->expects($this->once())
			->method('getAppValueBool')
			->with(ConfigLexicon::TEAM_FOLDER_AUTO_CREATE, true)
			->willReturn(false);

		$provider = $this->createMock(ITeamFolderProvider::class);
		$this->teamManager->expects($this->once())
			->method('getTeamFolderProvider')
			->willReturn($provider);

		$this->initialState->expects($this->exactly(2))
			->method('provideInitialState')
			->willReturnCallback(function (string $key, mixed $value): void {
				match ($key) {
					'teamFolderAutoCreate' => $this->assertFalse($value),
					'teamFolderProviderAvailable' => $this->assertTrue($value),
					default => $this->fail("Unexpected initial state key: $key"),
				};
			});

		$this->pageController->index();
	}

	/**
	 * No folder provider registered (groupfolders disabled or uninstalled):
	 * `teamFolderProviderAvailable` must be false, but the page still renders
	 * so the SPA can fall back to the regular "Folder" button.
	 */
	public function testIndexProvidesProviderUnavailable(): void {
		$this->configService->expects($this->once())
			->method('getAppValueBool')
			->with(ConfigService::FRONTEND_ENABLED)
			->willReturn(true);

		$this->appConfig->expects($this->once())
			->method('getAppValueBool')
			->with(ConfigLexicon::TEAM_FOLDER_AUTO_CREATE, true)
			->willReturn(true);

		$this->teamManager->expects($this->once())
			->method('getTeamFolderProvider')
			->willReturn(null);

		$this->initialState->expects($this->exactly(2))
			->method('provideInitialState')
			->willReturnCallback(function (string $key, mixed $value): void {
				match ($key) {
					'teamFolderAutoCreate' => $this->assertTrue($value),
					'teamFolderProviderAvailable' => $this->assertFalse($value),
					default => $this->fail("Unexpected initial state key: $key"),
				};
			});

		$result = $this->pageController->index();

		$this->assertInstanceOf(TemplateResponse::class, $result);
	}

	/**
	 * Frontend disabled: no initial state is provided and a 404 is returned.
	 */
	public function testIndexReturnsNotFoundWhenFrontendDisabled(): void {
		$this->configService->expects($this->once())
			->method('getAppValueBool')
			->with(ConfigService::FRONTEND_ENABLED)
			->willReturn(false);

		$this->initialState->expects($this->never())
			->method('provideInitialState');

		$this->teamManager->expects($this->never())
			->method('getTeamFolderProvider');

		$result = $this->pageController->index();

		$this->assertInstanceOf(NotFoundResponse::class, $result);
	}
}
