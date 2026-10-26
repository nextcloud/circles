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
use OCP\Teams\ITeamOwnedResourceProvider;
use OCP\Util;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use UnexpectedValueException;

class TeamOwnedResourceProviderRegistry {
	/** @var array<string, ITeamOwnedResourceProvider>|null */
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

	/** @return array<string, ITeamOwnedResourceProvider> */
	public function getProviders(): array {
		if ($this->providers !== null) {
			return $this->providers;
		}
		$this->providers = [];
		$registrations = [];
		try {
			$registrations = $this->bootstrap->getRegistrationContext()?->getTeamResourceProviders() ?? [];
		} catch (Throwable $e) {
			$this->errors[] = 'registration';
			$this->logger->error('Could not discover Teams app providers', ['exception' => $e]);
		}
		$registeredAppIds = [];
		foreach ($registrations as $registration) {
			$appId = $registration->getAppId();
			if (!$this->appManager->isEnabledForUser($appId)) {
				continue;
			}
			try {
				if (!is_a($registration->getService(), ITeamOwnedResourceProvider::class, true)) {
					continue;
				}
				if (!preg_match('/^[a-z][a-z0-9_]*$/D', $appId) || isset($registeredAppIds[$appId])) {
					throw new UnexpectedValueException('Invalid or duplicate Teams app provider: ' . $appId);
				}
				$registeredAppIds[$appId] = true;
				$provider = $this->container->get($registration->getService());
				if (!$provider instanceof ITeamOwnedResourceProvider) {
					throw new UnexpectedValueException('Registered service is not a Teams app provider');
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

	/** @return array{providers: list<array{id: string, name: string, iconUrl?: string}>, errors: list<string>} */
	public function load(): array {
		$loaded = [];
		foreach (array_keys($this->getProviders()) as $appId) {
			try {
				$language = $this->l10nFactory->findLanguage($appId);
				Util::addTranslations($appId, $language);
				$appInfo = $this->appManager->getAppInfo($appId, lang: $language);
				$name = $appInfo['name'] ?? $appId;
				$iconUrl = $this->appManager->getAppIcon($appId);
				$descriptor = ['id' => $appId, 'name' => $name];
				if ($iconUrl !== null) {
					$descriptor['iconUrl'] = $iconUrl;
				}
				$loaded[] = $descriptor;
			} catch (Throwable $e) {
				$this->errors[] = $appId;
				$this->logger->error('Could not load Teams app provider', ['app' => $appId, 'exception' => $e]);
			}
		}
		return ['providers' => $loaded, 'errors' => array_values(array_unique($this->errors))];
	}
}
