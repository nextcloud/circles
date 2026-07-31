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
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\Settings\IDelegatedSettings;
use OCP\Util;

class TeamsAdmin implements IDelegatedSettings {
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly IL10N $l,
		private readonly IInitialState $initialState,
		private readonly IGroupManager $groupManager,
	) {
	}

	public function getForm(): TemplateResponse {
		$allowedGroupsRaw = $this->appConfig->getValueString(
			Application::APP_ID,
			ConfigLexicon::TEAM_CREATION_ALLOWED_GROUPS,
			'[]',
		);

		$availableGroups = [];
		foreach ($this->groupManager->search('') as $group) {
			$availableGroups[] = [
				'gid' => $group->getGID(),
				'displayName' => $group->getDisplayName(),
			];
		}

		$this->initialState->provideInitialState('teamCreationAllowedGroups', json_decode($allowedGroupsRaw, true) ?: []);
		$this->initialState->provideInitialState('availableGroups', $availableGroups);

		Util::addStyle(Application::APP_ID, 'teams-settings-teams-admin');
		Util::addScript(Application::APP_ID, 'teams-settings-teams-admin');

		return new TemplateResponse(Application::APP_ID, 'settings-teams-admin', renderAs: '');
	}

	public function getSection(): string {
		return 'teams';
	}

	public function getPriority(): int {
		return 10;
	}

	public function getName(): ?string {
		return null;
	}

	public function getAuthorizedAppConfig(): array {
		return [
			Application::APP_ID => [
				ConfigLexicon::TEAM_CREATION_ALLOWED_GROUPS,
			],
		];
	}
}
