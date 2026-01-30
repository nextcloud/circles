<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Settings;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\ConfigLexicon;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\Settings\IDelegatedSettings;

class Admin implements IDelegatedSettings {
	/**
	 * Admin constructor.
	 */
	public function __construct(
		private IAppConfig $appConfig,
		private IL10N $l,
		private IInitialState $initialState,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$federatedTeamsEnabled = $this->appConfig->getValueBool(Application::APP_ID, ConfigLexicon::FEDERATED_TEAMS_ENABLED, false);
		$federatedTeamsFrontal = $this->appConfig->getValueString(Application::APP_ID, ConfigLexicon::FEDERATED_TEAMS_FRONTAL, '');

		$this->initialState->provideInitialState('federatedTeamsEnabled', $federatedTeamsEnabled);
		$this->initialState->provideInitialState('federatedTeamsFrontal', $federatedTeamsFrontal);

		\OCP\Util::addStyle(Application::APP_ID, 'teams-settings-admin');
		\OCP\Util::addScript(Application::APP_ID, 'teams-settings-admin');

		return new TemplateResponse(Application::APP_ID, 'settings-admin', renderAs: '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection(): string {
		return 'sharing';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the admin section. The forms are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority(): int {
		return 50;
	}

	public function getName(): ?string {
		return $this->l->t('Federated Teams');
	}

	public function getAuthorizedAppConfig(): array {
		return [
			Application::APP_ID => [
				ConfigLexicon::FEDERATED_TEAMS_ENABLED,
				ConfigLexicon::FEDERATED_TEAMS_FRONTAL,
			],
		];
	}
}
