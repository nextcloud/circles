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
use OCP\Teams\ITeamFolderPoolProvider;
use OCP\Teams\ITeamManager;

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
	public const PARAM_STORAGE_POOL_ID = 'storagePoolId';

	public function __construct(
		private IAppConfig $appConfig,
		private MembershipRequest $membershipRequest,
		private CircleRequest $circleRequest,
		private IGroupManager $groupManager,
		private IUserManager $userManager,
		private ITeamManager $teamManager,
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

	/**
	 * @param array<string, int|null> $groupLimits
	 * @param array<string, int|null> $teamLimits
	 * @return list<array{id: string, label: string, groupId: ?string, teamId: ?string, quota: ?int}>
	 */
	private function getStoragePoolCandidates(string $userId, array $groupLimits, array $teamLimits): array {
		$user = $this->userManager->get($userId);
		if ($user === null) {
			return [];
		}
		foreach ([...array_values($groupLimits), ...array_values($teamLimits)] as $limit) {
			if ($limit !== null && $limit < 0) {
				throw new \InvalidArgumentException('storage pool limit must be non-negative');
			}
		}
		$candidates = [];
		foreach ($this->groupManager->getUserGroupIds($user) as $groupId) {
			if (array_key_exists($groupId, $groupLimits)) {
				$candidates[] = ['id' => 'group:' . $groupId, 'label' => $this->groupManager->get($groupId)?->getDisplayName() ?? $groupId, 'groupId' => $groupId, 'teamId' => null, 'quota' => $groupLimits[$groupId]];
			}
		}
		if ($teamLimits !== []) {
			foreach ($this->teamManager->getTeamsForUser($userId) as $team) {
				$teamId = $team->getId();
				if (!array_key_exists($teamId, $teamLimits)) {
					continue;
				}
				try {
					$circle = $this->circleRequest->getCircle($teamId);
				} catch (CircleNotFoundException) {
					continue;
				}
				if ($this->isEligibleCircle($circle) && $circle->isLocal()) {
					$candidates[] = ['id' => 'team:' . $teamId, 'label' => $circle->getDisplayName(), 'groupId' => null, 'teamId' => $teamId, 'quota' => $teamLimits[$teamId]];
				}
			}
		}
		usort($candidates, static function (array $left, array $right): int {
			if ($left['quota'] === $right['quota']) {
				return strcmp($left['id'], $right['id']);
			}
			return $left['quota'] === null ? -1 : ($right['quota'] === null ? 1 : $right['quota'] <=> $left['quota']);
		});
		return $candidates;
	}

	/** @return array{defaultPoolId: string, pools: list<array{id: string, label: string}>}|null */
	public function getCreationStoragePoolOptions(string $userId): ?array {
		$provider = $this->teamManager->getTeamFolderProvider();
		if (!$provider instanceof ITeamFolderPoolProvider) {
			return null;
		}
		$candidates = $this->getStoragePoolCandidates($userId, $provider->getStoragePoolLimits(), $provider->getTeamStoragePoolLimits());
		return [
			'defaultPoolId' => $candidates[0]['id'] ?? 'default',
			'pools' => $candidates === []
				? [['id' => 'default', 'label' => '']]
				: array_map(static fn (array $candidate): array => ['id' => $candidate['id'], 'label' => $candidate['label']], $candidates),
		];
	}

	public function validateRequestedStoragePool(string $userId, string $storagePoolId): void {
		$options = $this->getCreationStoragePoolOptions($userId);
		if ($options === null || !in_array($storagePoolId, array_column($options['pools'], 'id'), true)) {
			throw new \InvalidArgumentException('The selected storage pool is not available to you', 403);
		}
	}

	public function assignStoragePoolForUser(ITeamFolderPoolProvider $provider, string $teamId, string $userId, ?string $storagePoolId = null): void {
		if (isset($provider->getTeamStoragePoolAssignments([$teamId])[$teamId])) {
			return;
		}
		$candidates = $this->getStoragePoolCandidates($userId, $provider->getStoragePoolLimits(), $provider->getTeamStoragePoolLimits());
		if ($storagePoolId === null) {
			$source = $candidates[0] ?? ['groupId' => null, 'teamId' => null];
		} elseif ($storagePoolId === 'default' && $candidates === []) {
			$source = ['groupId' => null, 'teamId' => null];
		} else {
			$source = null;
			foreach ($candidates as $candidate) {
				if ($candidate['id'] === $storagePoolId) {
					$source = $candidate;
					break;
				}
			}
			if ($source === null) {
				throw new \InvalidArgumentException('The selected storage pool is not available to you', 403);
			}
		}
		$provider->assignTeamStoragePool($teamId, $source['groupId'], true, $source['teamId']);
	}
}
