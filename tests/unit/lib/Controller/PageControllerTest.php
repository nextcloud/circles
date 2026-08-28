<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Controller;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Controller\PageController;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\TeamFolderPolicy;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\Teams\ITeamFolderProvider;
use OCP\Teams\ITeamManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

final class PageControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private ConfigService&MockObject $configService;
	private IInitialState&MockObject $initialState;
	private ITeamManager&MockObject $teamManager;
	private TeamFolderPolicy&MockObject $teamFolderPolicy;
	private IEventDispatcher&MockObject $eventDispatcher;
	private PageController $pageController;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->configService = $this->createMock(ConfigService::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->teamManager = $this->createMock(ITeamManager::class);
		$this->teamFolderPolicy = $this->createMock(TeamFolderPolicy::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);

		$this->pageController = new PageController(
			$this->request,
			$this->configService,
			$this->initialState,
			$this->teamManager,
			$this->teamFolderPolicy,
			$this->eventDispatcher,
		);
	}

	/**
	 * Assert that index() provides the provider initial state and returns
	 * the SPA template when the frontend is enabled and a folder provider is
	 * registered.
	 */
	public function testIndexProvidesTeamFolderStateWithProvider(): void {
		$this->configService->expects($this->once())
			->method('getAppValueBool')
			->with(ConfigService::FRONTEND_ENABLED)
			->willReturn(true);

		$provider = $this->createMock(ITeamFolderProvider::class);
		$this->teamManager->expects($this->once())
			->method('getTeamFolderProvider')
			->willReturn($provider);
		$this->teamFolderPolicy->expects($this->once())
			->method('isTeamFolderProvisioningEnabled')
			->willReturn(true);

		$provided = [];
		$this->initialState->expects($this->exactly(2))
			->method('provideInitialState')
			->willReturnCallback(function (string $key, mixed $value) use (&$provided): void {
				$provided[$key] = $value;
			});

		$result = $this->pageController->index();

		$this->assertInstanceOf(TemplateResponse::class, $result);
		$this->assertSame(Application::APP_ID, $result->getApp());
		$this->assertSame('main', $result->getTemplateName());
		$this->assertTrue($provided['teamFolderProviderAvailable']);
		$this->assertTrue($provided['teamFolderProvisioningEnabled']);
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

		$this->teamManager->expects($this->once())
			->method('getTeamFolderProvider')
			->willReturn(null);
		$this->teamFolderPolicy->expects($this->once())
			->method('isTeamFolderProvisioningEnabled')
			->willReturn(false);

		$provided = [];
		$this->initialState->expects($this->exactly(2))
			->method('provideInitialState')
			->willReturnCallback(function (string $key, mixed $value) use (&$provided): void {
				$provided[$key] = $value;
			});

		$result = $this->pageController->index();

		$this->assertInstanceOf(TemplateResponse::class, $result);
		$this->assertFalse($provided['teamFolderProviderAvailable']);
		$this->assertFalse($provided['teamFolderProvisioningEnabled']);
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
		$this->teamFolderPolicy->expects($this->never())
			->method('isTeamFolderProvisioningEnabled');

		$result = $this->pageController->index();

		$this->assertInstanceOf(NotFoundResponse::class, $result);
	}
}
