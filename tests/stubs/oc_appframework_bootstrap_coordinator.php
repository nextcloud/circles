<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Bootstrap;

use OC\Support\CrashReport\Registry;
use OC_App;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\QueryException;
use OCP\Dashboard\IManager;
use OCP\Diagnostics\IEventLogger;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IServerContainer;
use Psr\Log\LoggerInterface;
use Throwable;
use function class_exists;
use function class_implements;
use function in_array;

class Coordinator {
	public function __construct(
		private IServerContainer $serverContainer,
		private Registry $registry,
		private IManager $dashboardManager,
		private IEventDispatcher $eventDispatcher,
		private IEventLogger $eventLogger,
		private IAppManager $appManager,
		private LoggerInterface $logger,
	) {
	}

	public function runInitialRegistration(): void
 {
 }

	public function runLazyRegistration(string $appId): void
 {
 }

	public function getRegistrationContext(): ?RegistrationContext
 {
 }

	public function bootApp(string $appId): void
 {
 }

	public function isBootable(string $appId)
 {
 }
}
