<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Listeners;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\ConfigLexicon;
use OCA\Circles\Service\InterfaceService;
use OCP\AppFramework\Services\IAppConfig;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IRequest;
use OCP\OCM\Events\LocalOCMDiscoveryEvent;

/** @template-implements IEventListener<LocalOCMDiscoveryEvent> */
class OcmDiscoveryListener implements IEventListener {
	public function __construct(
		private readonly IRequest $request,
		private readonly InterfaceService $interfaceService,
		private readonly IAppConfig $appConfig,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!$event instanceof LocalOCMDiscoveryEvent) {
			return;
		}

		$interface = $this->interfaceService->getInterfaceFromRequest($this->request);
		if ($interface === 0) {
			return;
		}

		if ($interface === InterfaceService::IFACE_FRONTAL
			&& !$this->appConfig->getAppValueBool(ConfigLexicon::FEDERATED_TEAMS_ENABLED)) {
			return;
		}

		$event->addCapability(Application::OCM_CAPABILITY);
	}
}
