<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Service;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OCA\Circles\Service\TeamAppProviderRegistry;
use OCP\App\IAppManager;
use OCP\L10N\IFactory;
use OCP\Teams\ITeamAppProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class TeamAppProviderRegistryTest extends TestCase {
	private RegistrationContext $registration;
	private Coordinator&MockObject $bootstrap;
	private IAppManager&MockObject $appManager;
	private ContainerInterface&MockObject $container;
	private LoggerInterface&MockObject $logger;
	private IFactory&MockObject $l10nFactory;

	protected function setUp(): void {
		parent::setUp();
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->registration = new RegistrationContext($this->logger);
		$this->bootstrap = $this->createMock(Coordinator::class);
		$this->bootstrap->method('getRegistrationContext')->willReturn($this->registration);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->container = $this->createMock(ContainerInterface::class);
		$this->l10nFactory = $this->createMock(IFactory::class);
	}

	private function registry(?Coordinator $bootstrap = null): TeamAppProviderRegistry {
		return new TeamAppProviderRegistry($bootstrap ?? $this->bootstrap, $this->appManager, $this->container, $this->logger, $this->l10nFactory);
	}

	public function testDiscoversOnceAndLazilyLoadsEnabledApps(): void {
		$provider = $this->createMock(ITeamAppProvider::class);
		$provider->method('getId')->willReturn('text');
		$provider->expects($this->once())->method('load')->willReturn('/apps/text/js/text-teams.mjs');
		$this->registration->for('text')->registerTeamAppProvider(ITeamAppProvider::class);
		$this->appManager->method('isEnabledForUser')->with('text')->willReturn(true);
		$this->container->expects($this->once())->method('get')->with(ITeamAppProvider::class)->willReturn($provider);
		$this->bootstrap->expects($this->once())->method('getRegistrationContext');
		$registry = $this->registry();
		$this->assertSame(['text' => $provider], $registry->getProviders());
		$this->assertSame(['providers' => [['id' => 'text', 'order' => 0, 'routeAliases' => [], 'module' => '/apps/text/js/text-teams.mjs']], 'errors' => []], $registry->load());
	}

	public function testDisabledAppIsNotInstantiated(): void {
		$this->registration->for('text')->registerTeamAppProvider(ITeamAppProvider::class);
		$this->appManager->method('isEnabledForUser')->willReturn(false);
		$this->container->expects($this->never())->method('get');
		$this->assertSame(['providers' => [], 'errors' => []], $this->registry()->load());
	}

	public function testProviderFailureIsLoggedAndReported(): void {
		$provider = $this->createMock(ITeamAppProvider::class);
		$provider->method('getId')->willReturn('text');
		$provider->method('load')->willThrowException(new RuntimeException('broken assets'));
		$this->registration->for('text')->registerTeamAppProvider(ITeamAppProvider::class);
		$this->appManager->method('isEnabledForUser')->willReturn(true);
		$this->container->method('get')->willReturn($provider);
		$this->logger->expects($this->once())->method('error');
		$this->assertSame(['providers' => [], 'errors' => ['text']], $this->registry()->load());
	}

	public function testEmptyModuleUrlIsReported(): void {
		$provider = $this->createMock(ITeamAppProvider::class);
		$provider->method('getId')->willReturn('text');
		$provider->method('load')->willReturn('');
		$this->registration->for('text')->registerTeamAppProvider(ITeamAppProvider::class);
		$this->appManager->method('isEnabledForUser')->willReturn(true);
		$this->container->method('get')->willReturn($provider);
		$this->logger->expects($this->once())->method('error');
		$this->assertSame(['providers' => [], 'errors' => ['text']], $this->registry()->load());
	}

	public function testLoadsTranslationsAndVersionsModuleUrl(): void {
		$provider = $this->createMock(ITeamAppProvider::class);
		$provider->method('getId')->willReturn('text');
		$provider->method('load')->willReturn('/apps/text/js/text-teams.mjs?existing=1');
		$this->registration->for('text')->registerTeamAppProvider(ITeamAppProvider::class);
		$this->appManager->method('isEnabledForUser')->willReturn(true);
		$this->appManager->method('getAppVersion')->with('text')->willReturn('36.0.0');
		$this->container->method('get')->willReturn($provider);
		$this->l10nFactory->expects($this->once())->method('findLanguage')->with('text')->willReturn('de');
		$this->assertSame(['providers' => [['id' => 'text', 'order' => 0, 'routeAliases' => [], 'module' => '/apps/text/js/text-teams.mjs?existing=1&v=36.0.0']], 'errors' => []], $this->registry()->load());
	}

	public function testPhpOnlyProviderDoesNotRequireAModule(): void {
		$provider = $this->createMock(ITeamAppProvider::class);
		$provider->method('getId')->willReturn('deck');
		$provider->method('getOrder')->willReturn(20);
		$provider->method('load')->willReturn(null);
		$this->registration->for('deck')->registerTeamAppProvider(ITeamAppProvider::class);
		$this->appManager->method('isEnabledForUser')->willReturn(true);
		$this->container->method('get')->willReturn($provider);
		$this->assertSame(['providers' => [['id' => 'deck', 'order' => 20, 'routeAliases' => [], 'module' => null]], 'errors' => []], $this->registry()->load());
	}

	public function testRejectsDuplicateRegistration(): void {
		$provider = $this->createMock(ITeamAppProvider::class);
		$provider->method('getId')->willReturn('text');
		$provider->expects($this->never())->method('load');
		$this->registration->for('text')->registerTeamAppProvider(ITeamAppProvider::class);
		$this->registration->for('text')->registerTeamAppProvider(ITeamAppProvider::class);
		$this->appManager->method('isEnabledForUser')->willReturn(true);
		$this->container->expects($this->once())->method('get')->willReturn($provider);
		$this->assertSame(['providers' => [], 'errors' => ['text']], $this->registry()->load());
	}

	public function testRejectsInvalidAppIdBeforeInstantiation(): void {
		$this->registration->for('../text')->registerTeamAppProvider(ITeamAppProvider::class);
		$this->appManager->method('isEnabledForUser')->willReturn(true);
		$this->container->expects($this->never())->method('get');
		$this->assertSame(['providers' => [], 'errors' => ['../text']], $this->registry()->load());
	}

	public function testRejectsIdentityMismatch(): void {
		$provider = $this->createMock(ITeamAppProvider::class);
		$provider->method('getId')->willReturn('otherapp');
		$this->registration->for('text')->registerTeamAppProvider(ITeamAppProvider::class);
		$this->appManager->method('isEnabledForUser')->willReturn(true);
		$this->container->method('get')->willReturn($provider);
		$this->assertSame(['providers' => [], 'errors' => ['text']], $this->registry()->load());
	}

	public function testUninitializedBootstrapHasNoProviders(): void {
		$bootstrap = $this->createMock(Coordinator::class);
		$bootstrap->method('getRegistrationContext')->willReturn(null);
		$this->container->expects($this->never())->method('get');
		$this->assertSame(['providers' => [], 'errors' => []], $this->registry($bootstrap)->load());
	}
}
