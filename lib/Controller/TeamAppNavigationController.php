<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Controller;

use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\InsufficientPermissionException;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\PermissionService;
use OCA\Circles\Service\TeamOwnedResourceProviderRegistry;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Teams\ITeamManager;
use OCP\Teams\TeamFolder;
use OCP\Teams\TeamResource;
use Psr\Log\LoggerInterface;
use Throwable;

class TeamAppNavigationController extends OCSController {
	private const NAVIGATION_SETTING = 'teamsNavigation';
	private const LEGACY_TAB_ORDER_SETTING = 'teamsTabOrder';
	private const MAX_NAVIGATION_ENTRIES = 100;

	public function __construct(
		string $appName,
		IRequest $request,
		private CircleRequest $circleRequest,
		private PermissionService $permissionService,
		private IUserSession $userSession,
		private TeamOwnedResourceProviderRegistry $providers,
		private ITeamManager $teamManager,
		private CircleService $circleService,
		private FederatedUserService $federatedUserService,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @param list<string> $order Navigation entry IDs, first to last
	 * @return DataResponse<Http::STATUS_OK, array{}, array{}>
	 */
	#[NoAdminRequired]
	public function setNavigation(string $circleId, array $order): DataResponse {
		[$user] = $this->authorize($circleId, null, true);
		try {
			$this->saveNavigation($circleId, $user, $order);
		} catch (\Exception $e) {
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}

		return new DataResponse([]);
	}

	/** @return DataResponse<Http::STATUS_OK, array{entries: list<array{id: string, providerId?: string, entryId?: string}>, providers: array<string, array{entries: list<TeamResource>, canAddResource: bool}>, errors: list<string>}, array{}> */
	#[NoAdminRequired]
	public function getNavigation(string $circleId): DataResponse {
		[$user, $member] = $this->authorize($circleId, null, false);
		$userId = $user->getUID();
		$isAdmin = $member->getLevel() >= Member::LEVEL_ADMIN;
		$folder = $this->teamManager->getTeamFolderProvider()?->getTeamFolder($circleId);
		$settings = $this->circleSettings($circleId);
		$usesLegacyNavigation = !array_key_exists(self::NAVIGATION_SETTING, $settings);
		$navigationIds = $this->getSavedNavigationIds($settings, $circleId, $userId);
		$navigation = [];
		$errors = [];
		foreach ($this->providers->getProviders() as $providerId => $provider) {
			try {
				$navigation[$providerId] = [
					'entries' => $provider->getOwnedByTeam($circleId, $userId, $isAdmin, $folder),
					'canAddResource' => $isAdmin && $provider->canCreateResource($circleId, $userId, $isAdmin, $folder),
				];
			} catch (Throwable $e) {
				$errors[] = $providerId;
				$this->logger->error('Could not load Teams app navigation', ['provider' => $providerId, 'teamId' => $circleId, 'exception' => $e]);
			}
		}
		return new DataResponse(['entries' => $this->getOrderedNavigationEntries($navigation, $navigationIds, $errors, $usesLegacyNavigation), 'providers' => $navigation, 'errors' => $errors]);
	}

	/** @return DataResponse<Http::STATUS_OK, string, array{}> */
	#[NoAdminRequired]
	public function createResource(string $circleId, string $providerId, ?array $order = null): DataResponse {
		[$user, $member] = $this->authorize($circleId, $providerId, true);
		$teamName = $this->teamName($circleId, $user);
		$userId = $user->getUID();
		$isAdmin = $member->getLevel() >= Member::LEVEL_ADMIN;
		$folder = $this->teamManager->getTeamFolderProvider()?->getTeamFolder($circleId);
		$settings = $this->circleSettings($circleId);
		$navigationIds = $order === null
			? $this->getSavedNavigationIds($settings, $circleId, $userId)
			: $this->sanitizeNavigationIds($order);
		$provider = $this->providers->getProviders()[$providerId];

		try {
			if (!$provider->canCreateResource($circleId, $userId, $isAdmin, $folder)) {
				throw new OCSException('Adding a resource is not available for this team', Http::STATUS_FORBIDDEN);
			}
			if ($order === null && !array_key_exists(self::NAVIGATION_SETTING, $settings)) {
				$navigationIds = $this->resolveLegacyNavigationOrder($circleId, $userId, $providerId, $folder, $navigationIds);
			}
		} catch (\InvalidArgumentException $e) {
			throw new OCSException($e->getMessage(), Http::STATUS_BAD_REQUEST);
		} catch (\OCP\Files\NotPermittedException $e) {
			throw new OCSException($e->getMessage(), Http::STATUS_FORBIDDEN);
		}
		if (count($navigationIds) >= self::MAX_NAVIGATION_ENTRIES) {
			throw new OCSException('Navigation entry limit reached', Http::STATUS_BAD_REQUEST);
		}
		try {
			$resourceId = $provider->createResource($circleId, $teamName, $userId, $isAdmin, $folder);
		} catch (\InvalidArgumentException $e) {
			throw new OCSException($e->getMessage(), Http::STATUS_BAD_REQUEST);
		} catch (\OCP\Files\NotPermittedException $e) {
			throw new OCSException($e->getMessage(), Http::STATUS_FORBIDDEN);
		}
		if (!$this->isValidIds([$resourceId])) {
			throw new OCSException('Invalid Teams navigation entry ID', Http::STATUS_BAD_REQUEST);
		}
		$navigationId = $providerId . '-' . $resourceId;
		if (!in_array($navigationId, $navigationIds, true)) {
			array_unshift($navigationIds, $navigationId);
		}
		$this->saveNavigation($circleId, $user, $navigationIds);
		return new DataResponse($resourceId);
	}

	private function teamName(string $circleId, IUser $user): string {
		$team = $this->teamManager->getTeam($circleId, $user->getUID());
		if ($team === null) {
			throw new OCSNotFoundException('Team not found');
		}
		return $team->getDisplayName();
	}

	/**
	 * @param array<string, array{entries: list<TeamResource>}> $navigation
	 * @param list<string> $navigationIds
	 * @param list<string> $errors
	 * @return list<array{id: string, providerId?: string, entryId?: string}>
	 */
	private function getOrderedNavigationEntries(array $navigation, array $navigationIds, array $errors, bool $usesLegacyNavigation): array {
		$entries = ['team-folder' => ['id' => 'team-folder']];
		$entries['home'] = ['id' => 'home'];
		foreach ($navigationIds as $id) {
			if (preg_match('/^page-[0-9]+$/D', $id) === 1) {
				$entries[$id] = ['id' => $id];
			} elseif (!isset($navigation['files']) && preg_match('/^files-([0-9]+)$/D', $id, $matches) === 1) {
				// The frontend resolves earlier page aliases against the team's Pages directory.
				$entries[$id] = ['id' => $id, 'providerId' => 'files', 'entryId' => $matches[1]];
			}
		}
		foreach ($navigation as $providerId => $providerNavigation) {
			foreach ($providerNavigation['entries'] as $entry) {
				$id = $providerId . '-' . $entry->getId();
				$entries[$id] = ['id' => $id, 'providerId' => $providerId, 'entryId' => $entry->getId()];
			}
		}
		// Keep saved navigation entries when a provider is temporarily unavailable.
		foreach ($errors as $providerId) {
			$prefix = $providerId . '-';
			foreach ($navigationIds as $id) {
				if (str_starts_with($id, $prefix) && strlen($id) > strlen($prefix)) {
					$entries[$id] = ['id' => $id, 'providerId' => $providerId, 'entryId' => substr($id, strlen($prefix))];
				}
			}
		}
		if ($usesLegacyNavigation) {
			$navigationIds = [...$navigationIds, ...$this->getLegacyDefaultNavigationIds($navigation)];
		}
		$ordered = [];
		foreach ($navigationIds as $id) {
			if (isset($entries[$id])) {
				$ordered[] = $entries[$id];
				unset($entries[$id]);
			}
		}
		return [...$ordered, ...array_values($entries)];
	}

	/**
	 * Master appended unlisted entries in this default order.
	 *
	 * @param array<string, array{entries: list<TeamResource>}> $navigation
	 * @return list<string>
	 */
	private function getLegacyDefaultNavigationIds(array $navigation): array {
		$defaults = ['team-folder'];
		foreach (array_unique(['collectives', 'deck', 'files', ...array_keys($navigation)]) as $providerId) {
			foreach ($navigation[$providerId]['entries'] ?? [] as $entry) {
				$defaults[] = $providerId . '-' . $entry->getId();
			}
		}
		$defaults[] = 'home';
		return $defaults;
	}

	/** @return array<string, mixed> */
	private function circleSettings(string $circleId): array {
		try {
			return $this->circleRequest->getCircle($circleId)->getSettings();
		} catch (CircleNotFoundException) {
			throw new OCSNotFoundException('Team not found');
		}
	}

	/**
	 * @param array<string, mixed> $settings
	 * @return list<string> Canonical navigation order, or the legacy order-only preference.
	 */
	private function getSavedNavigationIds(array $settings, string $circleId, string $userId): array {
		$setting = array_key_exists(self::NAVIGATION_SETTING, $settings)
			? self::NAVIGATION_SETTING
			: self::LEGACY_TAB_ORDER_SETTING;
		if (!array_key_exists($setting, $settings)) {
			return [];
		}
		$storedOrder = $settings[$setting] ?? '[]';
		$order = is_string($storedOrder) ? json_decode($storedOrder, true) : [];
		$navigationIds = $this->sanitizeNavigationIds(is_array($order) ? $order : []);
		return $setting === self::LEGACY_TAB_ORDER_SETTING
			? $this->migrateLegacyNavigationIds($circleId, $userId, $navigationIds)
			: $navigationIds;
	}

	/** @return list<string> */
	private function migrateLegacyNavigationIds(string $circleId, string $userId, array $order): array {
		$order = preg_replace(
			'/^board-([0-9]+)$/D',
			'deck-$1',
			$order,
		);
		$collectivePosition = array_search('collective', $order, true);
		if ($collectivePosition !== false) {
			foreach ($this->teamManager->getSharedWith($circleId, $userId) as $resource) {
				if ($resource->getProvider()->getId() === 'collectives') {
					$order[$collectivePosition] = 'collectives-' . $resource->getId();
					break;
				}
			}
		}
		return $this->sanitizeNavigationIds($order);
	}

	/**
	 * Persist the selected navigation entries and their order together.
	 *
	 * @param list<string> $order
	 */
	private function saveNavigation(string $circleId, IUser $user, array $order): void {
		$order = $this->sanitizeNavigationIds($order);
		$this->federatedUserService->setLocalCurrentUser($user);
		$this->circleService->updateSetting($circleId, self::NAVIGATION_SETTING, json_encode($order, JSON_THROW_ON_ERROR));
	}

	/** @return list<string> */
	private function sanitizeNavigationIds(array $order): array {
		$order = array_filter($order, static fn ($id): bool => is_string($id)
			&& $id !== ''
			&& strlen($id) <= 64
			&& preg_match('/[\x00-\x1f\x7f]/', $id) !== 1);

		return array_slice(array_values(array_unique($order)), 0, self::MAX_NAVIGATION_ENTRIES);
	}

	/**
	 * Resolve legacy ordering before the first canonical save.
	 *
	 * @param list<string> $navigationIds
	 * @return list<string>
	 */
	private function resolveLegacyNavigationOrder(string $circleId, string $userId, string $providerId, ?TeamFolder $folder, array $navigationIds): array {
		$navigations = [];
		$errors = [];
		foreach ($this->providers->getProviders() as $id => $provider) {
			try {
				$navigations[$id] = ['entries' => $provider->getOwnedByTeam($circleId, $userId, true, $folder)];
			} catch (Throwable $e) {
				if ($id === $providerId) {
					throw $e;
				}
				$errors[] = $id;
				$this->logger->error('Could not initialize Teams navigation', ['provider' => $id, 'teamId' => $circleId, 'exception' => $e]);
			}
		}
		return array_slice(array_column($this->getOrderedNavigationEntries($navigations, $navigationIds, $errors, true), 'id'), 0, self::MAX_NAVIGATION_ENTRIES);
	}

	/** @return array{IUser, Member} */
	private function authorize(string $circleId, ?string $providerId, bool $write): array {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new OCSException('Authentication required', Http::STATUS_UNAUTHORIZED);
		}
		try {
			$member = $this->permissionService->userMustBeMember($user->getUID(), $circleId);
			if ($write) {
				$this->permissionService->memberMustBeAtLeastAdmin($member);
			}
		} catch (InsufficientPermissionException $e) {
			throw new OCSException($e->getMessage(), Http::STATUS_FORBIDDEN);
		}
		if ($providerId !== null && !isset($this->providers->getProviders()[$providerId])) {
			throw new OCSNotFoundException('Teams app provider not found');
		}
		return [$user, $member];
	}

	private function isValidIds(array $ids): bool {
		if (!array_is_list($ids) || count($ids) > self::MAX_NAVIGATION_ENTRIES) {
			return false;
		}
		foreach ($ids as $id) {
			if (!is_string($id) || $id === '' || strlen($id) > 64 || preg_match('/[\x00-\x1f\x7f]/', $id)) {
				return false;
			}
		}
		return true;
	}
}
