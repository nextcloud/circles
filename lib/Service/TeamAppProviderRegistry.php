<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use OC\AppFramework\Bootstrap\Coordinator;
use OCP\App\IAppManager;
use OCP\L10N\IFactory;
use OCP\Teams\ITeamAppProvider;
use OCP\Teams\TeamAppRouteAlias;
use OCP\Util;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use UnexpectedValueException;

class TeamAppProviderRegistry {
	/** @var array<string, ITeamAppProvider>|null */
	private ?array $providers = null;
	/** @var list<string> */
	private array $errors = [];

	public function __construct(
		private Coordinator $bootstrap,
		private IAppManager $appManager,
		private ContainerInterface $container,
		private LoggerInterface $logger,
		private IFactory $l10nFactory,
	) {
	}

	/** @return array<string, ITeamAppProvider> */
	public function getProviders(): array {
		if ($this->providers !== null) {
			return $this->providers;
		}
		$this->providers = [];
		$registrations = [];
		try {
			$registrations = $this->bootstrap->getRegistrationContext()?->getTeamAppProviders() ?? [];
		} catch (Throwable $e) {
			$this->errors[] = 'registration';
			$this->logger->error('Could not discover Teams app providers', ['exception' => $e]);
		}
		$seen = [];
		foreach ($registrations as $registration) {
			$appId = $registration->getAppId();
			if (!$this->appManager->isEnabledForUser($appId)) {
				continue;
			}
			try {
				if (!preg_match('/^[a-z][a-z0-9_]*$/D', $appId) || isset($seen[$appId])) {
					throw new UnexpectedValueException('Invalid or duplicate Teams app provider: ' . $appId);
				}
				$seen[$appId] = true;
				$provider = $this->container->get($registration->getService());
				if (!$provider instanceof ITeamAppProvider || $provider->getId() !== $appId) {
					throw new UnexpectedValueException('Teams provider identity must match its owning app');
				}
				$this->providers[$appId] = $provider;
			} catch (Throwable $e) {
				unset($this->providers[$appId]);
				$this->errors[] = $appId;
				$this->logger->error('Could not register Teams app provider', ['app' => $appId, 'exception' => $e]);
			}
		}
		return $this->providers;
	}

	/** @return array{providers: list<array{id: string, order: int, routeAliases: list<TeamAppRouteAlias>, module: ?string}>, errors: list<string>} */
	public function load(): array {
		$loaded = [];
		foreach ($this->getProviders() as $appId => $provider) {
			try {
				$module = $provider->load();
				if ($module === '') {
					throw new UnexpectedValueException('Teams provider must declare a browser module URL');
				}
				Util::addTranslations($appId, $this->l10nFactory->findLanguage($appId));
				$version = $this->appManager->getAppVersion($appId);
				if ($module !== null && $version !== '') {
					$module .= (str_contains($module, '?') ? '&' : '?') . 'v=' . rawurlencode($version);
				}
				$loaded[] = ['id' => $appId, 'order' => $provider->getOrder(), 'routeAliases' => $provider->getRouteAliases(), 'module' => $module];
			} catch (Throwable $e) {
				$this->errors[] = $appId;
				$this->logger->error('Could not load Teams app provider', ['app' => $appId, 'exception' => $e]);
			}
		}
		return ['providers' => $loaded, 'errors' => array_values(array_unique($this->errors))];
	}
}
