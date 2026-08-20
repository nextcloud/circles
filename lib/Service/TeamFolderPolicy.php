<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\ConfigLexicon;
use OCA\Circles\Model\Circle;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IConfig;

/**
 * Policy for team folders owned by teams (circles).
 *
 * This class owns the *policy* for team-folder creation:
 *  - the `circles.team_folder_auto_create` system config toggle (config.php),
 *  - the `team_folder_default_quota` app config value,
 *  - the circle-type eligibility rules (personal/hidden/system/backend circles
 *    are excluded).
 *
 * The *orchestration* (creating, unlinking, removing folders) is owned by the
 * groupfolders app. The circles app keeps no reference to the groupfolders app.
 *
 * The Groupfolders provider owns the durable `team_circle_id` linkage. Circles
 * never persists a Groupfolders identifier.
 */
class TeamFolderPolicy {
	public const SYSTEM_TEAM_FOLDER_AUTO_CREATE = Application::APP_ID . '.' . ConfigLexicon::TEAM_FOLDER_AUTO_CREATE;

	public function __construct(
		private IAppConfig $appConfig,
		private IConfig $config,
	) {
	}

	/**
	 * Whether Circles may provision team folders (auto-create on team creation
	 * and Circles UI/API upgrade). Controlled via config.php:
	 *
	 *   'circles.team_folder_auto_create' => false,
	 *
	 * Defaults to true when unset. OCC upgrade commands bypass this gate.
	 */
	public function isTeamFolderProvisioningEnabled(): bool {
		$value = $this->config->getSystemValue(self::SYSTEM_TEAM_FOLDER_AUTO_CREATE, null);
		if ($value === null) {
			return true;
		}

		return filter_var($value, FILTER_VALIDATE_BOOLEAN);
	}

	/**
	 * Whether the circle type is eligible for a dedicated team folder.
	 */
	public function isEligibleCircle(Circle $circle): bool {
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

	public function shouldCreateTeamFolder(Circle $circle): bool {
		if (!$this->isTeamFolderProvisioningEnabled()) {
			return false;
		}

		return $this->isEligibleCircle($circle);
	}

	public function getDefaultQuota(): int {
		return $this->appConfig->getAppValueInt(ConfigLexicon::TEAM_FOLDER_DEFAULT_QUOTA, 0);
	}
}
