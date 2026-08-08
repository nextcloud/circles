<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Settings;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\ConfigLexicon;
use OCA\Circles\Service\OidcService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IAppConfig;
use OCP\IUserSession;
use OCP\Security\ICredentialsManager;
use OCP\Settings\ISettings;
use OCP\Util;

class Personal implements ISettings {

	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly IInitialState $initialState,
		private readonly IUserSession $userSession,
		private readonly ICredentialsManager $credentialsManager,
	) {
	}

	#[\Override]
	public function getForm(): TemplateResponse {
		$userId = $this->userSession->getUser()?->getUID();
		$oidcEnabled = $this->appConfig->getValueBool(Application::APP_ID, ConfigLexicon::OIDC_ENABLED, false);

		$oidcConnected = $userId !== null && $this->credentialsManager->retrieve($userId, OidcService::CREDENTIAL_REFRESH_TOKEN) !== null;

		$this->initialState->provideInitialState('oidc_enabled', $oidcEnabled);
		$this->initialState->provideInitialState('oidc_connected', $oidcConnected);

		Util::addScript(Application::APP_ID, 'teams-settings-personal');
		Util::addStyle(Application::APP_ID, 'teams-settings-personal');

		return new TemplateResponse(Application::APP_ID, 'settings-personal', renderAs: '');
	}

	#[\Override]
	public function getSection(): ?string {
		if (!$this->shouldDisplaySection()) {
			return null;
		}
		return Application::APP_ID;
	}

	#[\Override]
	public function getPriority(): int {
		return 80;
	}

	private function shouldDisplaySection(): bool {
		return $this->appConfig->getValueBool(Application::APP_ID, ConfigLexicon::OIDC_ENABLED, false);
	}
}
