<?php

declare(strict_types=1);

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

class AdminTeamFolders implements IDelegatedSettings {
	/**
	 * AdminTeamFolders constructor.
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
		$teamFolderAutoCreate = $this->appConfig->getValueBool(Application::APP_ID, ConfigLexicon::TEAM_FOLDER_AUTO_CREATE, true);
		$teamFolderDefaultQuota = $this->appConfig->getValueInt(Application::APP_ID, ConfigLexicon::TEAM_FOLDER_DEFAULT_QUOTA, 0);

		$this->initialState->provideInitialState('teamFolderAutoCreate', $teamFolderAutoCreate);
		$this->initialState->provideInitialState('teamFolderDefaultQuota', $teamFolderDefaultQuota);

		\OCP\Util::addStyle(Application::APP_ID, 'teams-settings-team-folders');
		\OCP\Util::addScript(Application::APP_ID, 'teams-settings-team-folders');

		return new TemplateResponse(Application::APP_ID, 'settings-team-folders', renderAs: '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection(): string {
		return 'teams';
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
		return $this->l->t('Team spaces');
	}

	public function getAuthorizedAppConfig(): array {
		return [
			Application::APP_ID => [
				ConfigLexicon::TEAM_FOLDER_AUTO_CREATE,
				ConfigLexicon::TEAM_FOLDER_DEFAULT_QUOTA,
			],
		];
	}
}
