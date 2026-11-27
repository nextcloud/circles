<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Tests\Service;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OCA\Circles\Service\TeamOwnedResourceProviderRegistry;
use OCP\App\IAppManager;
use OCP\L10N\IFactory;
use OCP\Teams\ITeamOwnedResourceProvider;
use OCP\Teams\ITeamResourceProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class TeamOwnedResourceProviderRegistryTest extends TestCase {
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

	private function registry(?Coordinator $bootstrap = null): TeamOwnedResourceProviderRegistry {
		return new TeamOwnedResourceProviderRegistry($bootstrap ?? $this->bootstrap, $this->appManager, $this->container, $this->logger, $this->l10nFactory);
	}

	public function testDiscoversEnabledAppsOnceAndLoadsDescriptors(): void {
		$provider = $this->createMock(ITeamOwnedResourceProvider::class);
		$this->registration->for('text')->registerTeamResourceProvider(ITeamOwnedResourceProvider::class);
		$this->appManager->method('isEnabledForUser')->with('text')->willReturn(true);
		$this->appManager->method('getAppInfo')->willReturn(['name' => 'Text']);
		$this->appManager->method('getAppIcon')->with('text')->willReturn('/apps/text/img/app.svg');
		$this->container->expects($this->once())->method('get')->with(ITeamOwnedResourceProvider::class)->willReturn($provider);
		$this->bootstrap->expects($this->once())->method('getRegistrationContext');
		$registry = $this->registry();
		$this->assertSame(['text' => $provider], $registry->getProviders());
		$this->assertSame(['providers' => [['id' => 'text', 'name' => 'Text', 'iconUrl' => '/apps/text/img/app.svg']], 'errors' => []], $registry->load());
	}

	public function testDisabledAppIsNotInstantiated(): void {
		$this->registration->for('text')->registerTeamResourceProvider(ITeamOwnedResourceProvider::class);
		$this->appManager->method('isEnabledForUser')->willReturn(false);
		$this->container->expects($this->never())->method('get');
		$this->assertSame(['providers' => [], 'errors' => []], $this->registry()->load());
	}

	public function testLoadsTranslationsWithoutAModuleDescriptor(): void {
		$provider = $this->createMock(ITeamOwnedResourceProvider::class);
		$this->registration->for('text')->registerTeamResourceProvider(ITeamOwnedResourceProvider::class);
		$this->appManager->method('isEnabledForUser')->willReturn(true);
		$this->appManager->expects($this->never())->method('getAppVersion');
		$this->container->method('get')->willReturn($provider);
		$this->l10nFactory->expects($this->once())->method('findLanguage')->with('text')->willReturn('de');
		$this->appManager->method('getAppInfo')->with('text', false, 'de')->willReturn(['name' => 'Text']);
		$this->assertSame(['providers' => [['id' => 'text', 'name' => 'Text']], 'errors' => []], $this->registry()->load());
	}

	public function testProviderDoesNotRequireAModule(): void {
		$provider = $this->createMock(ITeamOwnedResourceProvider::class);
		$this->registration->for('deck')->registerTeamResourceProvider(ITeamOwnedResourceProvider::class);
		$this->appManager->method('isEnabledForUser')->willReturn(true);
		$this->container->method('get')->willReturn($provider);
		$this->assertSame(['providers' => [['id' => 'deck', 'name' => 'deck']], 'errors' => []], $this->registry()->load());
	}

	public function testIgnoresResourceOnlyProviders(): void {
		$this->registration->for('files')->registerTeamResourceProvider(ITeamResourceProvider::class);
		$this->appManager->method('isEnabledForUser')->willReturn(true);
		$this->container->expects($this->never())->method('get');
		$this->logger->expects($this->never())->method('error');

		$this->assertSame(['providers' => [], 'errors' => []], $this->registry()->load());
	}

	public function testResourceOnlyProviderDoesNotConflictWithOwnedProviderFromSameApp(): void {
		$this->registration->for('deck')->registerTeamResourceProvider(ITeamResourceProvider::class);
		$this->registration->for('deck')->registerTeamResourceProvider(ITeamOwnedResourceProvider::class);
		$this->appManager->method('isEnabledForUser')->willReturn(true);
		$provider = $this->createMock(ITeamOwnedResourceProvider::class);
		$this->container->expects($this->once())->method('get')->with(ITeamOwnedResourceProvider::class)->willReturn($provider);

		$this->assertSame(['deck' => $provider], $this->registry()->getProviders());
	}

	public function testRejectsOwnedProviderResolvedAsResourceOnlyProvider(): void {
		$this->registration->for('deck')->registerTeamResourceProvider(ITeamOwnedResourceProvider::class);
		$this->appManager->method('isEnabledForUser')->willReturn(true);
		$this->container->method('get')->willReturn($this->createMock(ITeamResourceProvider::class));

		$this->assertSame(['providers' => [], 'errors' => ['deck']], $this->registry()->load());
	}

	public function testRejectsDuplicateRegistration(): void {
		$provider = $this->createMock(ITeamOwnedResourceProvider::class);
		$this->registration->for('text')->registerTeamResourceProvider(ITeamOwnedResourceProvider::class);
		$this->registration->for('text')->registerTeamResourceProvider(ITeamOwnedResourceProvider::class);
		$this->appManager->method('isEnabledForUser')->willReturn(true);
		$this->container->expects($this->once())->method('get')->willReturn($provider);
		$this->assertSame(['providers' => [], 'errors' => ['text']], $this->registry()->load());
	}

	public function testRejectsInvalidAppIdBeforeInstantiation(): void {
		$this->registration->for('../text')->registerTeamResourceProvider(ITeamOwnedResourceProvider::class);
		$this->appManager->method('isEnabledForUser')->willReturn(true);
		$this->container->expects($this->never())->method('get');
		$this->assertSame(['providers' => [], 'errors' => ['../text']], $this->registry()->load());
	}

	public function testUninitializedBootstrapHasNoProviders(): void {
		$bootstrap = $this->createMock(Coordinator::class);
		$bootstrap->method('getRegistrationContext')->willReturn(null);
		$this->container->expects($this->never())->method('get');
		$this->assertSame(['providers' => [], 'errors' => []], $this->registry($bootstrap)->load());
	}
}
