<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use OCA\Circles\ConfigLexicon;
use OCA\Circles\Model\Circle;
use OCP\AppFramework\Services\IAppConfig;

/**
 * Policy for team folders owned by teams (circles).
 *
 * This class owns the *policy* for team-folder creation:
 *  - the `team_folder_auto_create` app config toggle,
 *  - the `team_folder_default_quota` app config value,
 *  - the circle-type eligibility rules (personal/hidden/system/backend circles
 *    are excluded).
 *
 * Per-creation opt-out from the team wizard/API is applied by the listener
 * before this policy runs.
 *
 * The *orchestration* (creating, unlinking, removing folders) is owned by the
 * groupfolders app. The circles app keeps no reference to the groupfolders app.
 *
 * The Groupfolders provider owns the durable `team_circle_id` linkage. Circles
 * never persists a Groupfolders identifier.
 */
class TeamFolderPolicy {
	public const PARAM_CREATE_TEAM_FOLDER = 'createTeamFolder';

	public function __construct(
		private IAppConfig $appConfig,
	) {
	}

	public function shouldCreateTeamFolder(Circle $circle): bool {
		$autoCreate = $this->appConfig->getAppValueBool(ConfigLexicon::TEAM_FOLDER_AUTO_CREATE, true);
		if (!$autoCreate) {
			return false;
		}

		if ($circle->isConfig(Circle::CFG_PERSONAL)) {
			return false;
		}

		if ($circle->isConfig(Circle::CFG_HIDDEN)) {
			return false;
		}

		if ($circle->isConfig(Circle::CFG_SYSTEM)) {
			return false;
		}

		if ($circle->isConfig(Circle::CFG_BACKEND)) {
			return false;
		}

		return true;
	}

	public function getDefaultQuota(): int {
		return $this->appConfig->getAppValueInt(ConfigLexicon::TEAM_FOLDER_DEFAULT_QUOTA, 0);
	}
}
