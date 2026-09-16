<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use OCA\Circles\ConfigLexicon;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MembershipRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Model\Circle;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IGroupManager;
use OCP\IUserManager;

/**
 * Policy for team folders owned by teams (circles).
 *
 * This class owns the *policy* for team-folder creation:
 *  - the `team_folder_auto_create` app config toggle (occ only, not admin UI),
 *  - the default quota and per-team quota settings,
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
	public const PARAM_CREATE_TEAM_FOLDER = 'createTeamFolder';

	public function __construct(
		private IAppConfig $appConfig,
		private MembershipRequest $membershipRequest,
		private CircleRequest $circleRequest,
		private IGroupManager $groupManager,
		private IUserManager $userManager,
	) {
	}

	/**
	 * Whether Circles may provision team folders (auto-create on team creation
	 * and Circles UI/API upgrade). Controlled via app config, e.g.:
	 *
	 *   occ config:app:set circles team_folder_auto_create --value="false" --type=boolean
	 *
	 * Defaults to true when unset.
	 */
	public function isTeamFolderProvisioningEnabled(): bool {
		return $this->appConfig->getAppValueBool(ConfigLexicon::TEAM_FOLDER_AUTO_CREATE, true);
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

	/**
	 * Get the quota applied when no group-specific override matches.
	 */
	public function getDefaultQuota(): int {
		$quota = $this->appConfig->getAppValueInt(ConfigLexicon::TEAM_FOLDER_DEFAULT_QUOTA, ConfigLexicon::DEFAULT_QUOTA);
		return $quota >= 0 ? $quota : ConfigLexicon::DEFAULT_QUOTA;
	}

	/**
	 * @throws \InvalidArgumentException when the quota is negative.
	 */
	public function setDefaultQuota(int $quota): void {
		if ($quota < 0) {
			throw new \InvalidArgumentException('default quota must be a non-negative integer');
		}

		$this->appConfig->setAppValueInt(ConfigLexicon::TEAM_FOLDER_DEFAULT_QUOTA, $quota);
	}

	public function getTeamFolderQuota(Circle $circle): ?int {
		$quota = $circle->getSettings()[Circle::SETTING_TEAM_FOLDER_QUOTA] ?? null;

		return is_int($quota) && $quota >= 0 ? $quota : null;
	}

	public function setTeamFolderQuota(Circle $circle, int $quota): void {
		if ($quota < 0) {
			throw new \InvalidArgumentException('team folder quota must be a non-negative integer');
		}

		$settings = $circle->getSettings();
		$settings[Circle::SETTING_TEAM_FOLDER_QUOTA] = $quota;
		$this->circleRequest->updateSettings($circle->setSettings($settings));
	}

	public function removeTeamFolderQuota(Circle $circle): void {
		$settings = $circle->getSettings();
		if (array_key_exists(Circle::SETTING_TEAM_FOLDER_QUOTA, $settings)) {
			unset($settings[Circle::SETTING_TEAM_FOLDER_QUOTA]);
			$this->circleRequest->updateSettings($circle->setSettings($settings));
		}
	}

	/** @return array<string, int> */
	public function getGroupQuotas(): array {
		$quotas = $this->appConfig->getAppValueArray(ConfigLexicon::TEAM_FOLDER_GROUP_QUOTAS, []);
		if (!is_array($quotas)) {
			return [];
		}

		return array_filter($quotas, static fn (mixed $quota): bool => is_int($quota) && $quota >= 0);
	}

	/**
	 * Resolve the highest configured team or group quota for the local team owner.
	 * Unlimited (0) takes precedence over every finite quota.
	 */
	public function getQuotaForCircle(Circle $circle): int {
		$override = $this->getTeamFolderQuota($circle);
		$fallback = $this->getDefaultQuota();
		$matches = $override === null ? [] : [$override];
		$owner = $circle->getOwner();
		if (!$owner->isLocal()) {
			return $matches === [] ? $fallback : ($matches[0] ?? $fallback);
		}

		$matches = [...$matches, ...$this->getMatchingGroupQuotas($owner->getUserId())];
		foreach ($this->membershipRequest->getMemberships($owner->getSingleId()) as $membership) {
			try {
				$membershipCircle = $this->circleRequest->getCircle($membership->getCircleId());
			} catch (CircleNotFoundException) {
				continue;
			}

			$quota = $this->getTeamFolderQuota($membershipCircle);
			if ($quota !== null) {
				$matches[] = $quota;
			}
		}

		if ($matches === []) {
			return $fallback;
		}

		if (in_array(0, $matches, true)) {
			return 0;
		}

		return max($matches);
	}

	/** @return list<int> */
	private function getMatchingGroupQuotas(string $userId): array {
		$user = $this->userManager->get($userId);
		if ($user === null) {
			return [];
		}

		$quotas = $this->getGroupQuotas();
		return array_values(array_filter(array_map(
			static fn (string $groupId): ?int => $quotas[$groupId] ?? null,
			$this->groupManager->getUserGroupIds($user),
		), static fn (?int $quota): bool => $quota !== null));
	}
}
