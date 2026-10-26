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
use OCA\Circles\Service\TeamAppProviderRegistry;
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
use OCP\Teams\TeamAppNavigation;
use OCP\Teams\TeamAppNavigationAction;
use OCP\Teams\TeamAppNavigationEntry;
use Psr\Log\LoggerInterface;
use Throwable;

class TeamAppNavigationController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private CircleRequest $circleRequest,
		private CircleService $circleService,
		private FederatedUserService $federatedUserService,
		private PermissionService $permissionService,
		private IUserSession $userSession,
		private TeamAppProviderRegistry $providers,
		private ITeamManager $teamManager,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/** @param list<string> $navigationEntryIds */
	private function saveNavigationEntryIds(string $circleId, string $providerId, array $navigationEntryIds): void {
		[$user] = $this->authorize($circleId, $providerId, true);
		if (!$this->validIds($navigationEntryIds)) {
			throw new OCSException('Navigation entry IDs must be a list of at most 100 non-empty resource IDs of at most 64 bytes', Http::STATUS_BAD_REQUEST);
		}
		$navigationEntryIds = array_values(array_unique($navigationEntryIds));
		$this->federatedUserService->setLocalCurrentUser($user);
		$this->circleService->updateSetting($circleId, $this->settingKey($providerId), json_encode($navigationEntryIds, JSON_THROW_ON_ERROR));
	}

	/** @return DataResponse<Http::STATUS_OK, array{providers: array<string, array{entries: list<TeamAppNavigationEntry>, createAction?: TeamAppNavigationAction, selectedEntryIds: list<string>}>, errors: list<string>}, array{}> */
	#[NoAdminRequired]
	public function getNavigations(string $circleId): DataResponse {
		[$user, $member] = $this->authorize($circleId, null, false);
		$userId = $user->getUID();
		$isAdmin = $member->getLevel() >= Member::LEVEL_ADMIN;
		$folder = $this->teamManager->getTeamFolderProvider()?->getTeamFolder($circleId);
		$settings = $this->circleSettings($circleId);
		$navigation = [];
		$errors = [];
		foreach ($this->providers->getProviders() as $providerId => $provider) {
			try {
				$navigationEntryIds = $this->getSavedNavigationEntryIds($settings, $providerId);
				$providerNavigation = $provider->getNavigation($circleId, $userId, $isAdmin, $folder);
				$navigation[$providerId] = $this->applySavedNavigationSelection($providerNavigation, $navigationEntryIds);
			} catch (Throwable $e) {
				$errors[] = $providerId;
				$this->logger->error('Could not load Teams app navigation', ['provider' => $providerId, 'teamId' => $circleId, 'exception' => $e]);
			}
		}
		return new DataResponse(['providers' => $navigation, 'errors' => $errors]);
	}

	/** @return DataResponse<Http::STATUS_OK, string, array{}> */
	#[NoAdminRequired]
	public function action(string $circleId, string $providerId, string $actionId, ?string $value = null): DataResponse {
		[$user, $member] = $this->authorize($circleId, $providerId, true);
		$teamName = $this->teamName($circleId, $user);
		$userId = $user->getUID();
		$isAdmin = $member->getLevel() >= Member::LEVEL_ADMIN;
		$folder = $this->teamManager->getTeamFolderProvider()?->getTeamFolder($circleId);
		$settings = $this->circleSettings($circleId);
		$navigationEntryIds = $this->getSavedNavigationEntryIds($settings, $providerId);
		$provider = $this->providers->getProviders()[$providerId];
		$navigation = $this->applySavedNavigationSelection($provider->getNavigation($circleId, $userId, $isAdmin, $folder), $navigationEntryIds);
		$createAction = $navigation['createAction'] ?? null;
		if (!$createAction instanceof TeamAppNavigationAction || $createAction->id !== $actionId) {
			throw new OCSNotFoundException('Teams app action not found');
		}
		try {
			$resourceId = $provider->addResource($circleId, $teamName, $userId, $isAdmin, $folder, $value);
		} catch (\InvalidArgumentException $e) {
			throw new OCSException($e->getMessage(), Http::STATUS_BAD_REQUEST);
		} catch (\OCP\Files\NotPermittedException $e) {
			throw new OCSException($e->getMessage(), Http::STATUS_FORBIDDEN);
		}
		if (!$this->validIds([$resourceId])) {
			throw new OCSException('Invalid Teams navigation entry ID', Http::STATUS_BAD_REQUEST);
		}
		$navigationEntryIds[] = $resourceId;
		$navigationEntryIds = array_values(array_unique($navigationEntryIds));
		if (!$this->validIds($navigationEntryIds)) {
			throw new OCSException('Navigation entry limit reached or invalid navigation entry IDs', Http::STATUS_BAD_REQUEST);
		}
		$this->saveNavigationEntryIds($circleId, $providerId, $navigationEntryIds);
		return new DataResponse($resourceId);
	}

	/** @return DataResponse<Http::STATUS_OK, array{}, array{}> */
	#[NoAdminRequired]
	public function setEntryNavigation(string $circleId, string $providerId, string $entryId, bool $inNavigation): DataResponse {
		[$user, $member] = $this->authorize($circleId, $providerId, true);
		if (!$this->validIds([$entryId])) {
			throw new OCSException('Invalid navigation entry ID', Http::STATUS_BAD_REQUEST);
		}
		$settings = $this->circleSettings($circleId);
		$navigationEntryIds = $this->getSavedNavigationEntryIds($settings, $providerId);
		if ($inNavigation) {
			$provider = $this->providers->getProviders()[$providerId];
			$folder = $this->teamManager->getTeamFolderProvider()?->getTeamFolder($circleId);
			try {
				$navigation = $provider->getNavigation($circleId, $user->getUID(), $member->getLevel() >= Member::LEVEL_ADMIN, $folder);
			} catch (\InvalidArgumentException $e) {
				throw new OCSException($e->getMessage(), Http::STATUS_BAD_REQUEST);
			} catch (\OCP\Files\NotPermittedException $e) {
				throw new OCSException($e->getMessage(), Http::STATUS_FORBIDDEN);
			}
			if (!in_array($entryId, array_map(static fn (TeamAppNavigationEntry $entry): string => $entry->id, $navigation->entries), true)) {
				throw new OCSNotFoundException('Teams app navigation entry not found');
			}
			$navigationEntryIds[] = $entryId;
		} else {
			$navigationEntryIds = array_values(array_diff($navigationEntryIds, [$entryId]));
		}
		$navigationEntryIds = array_values(array_unique($navigationEntryIds));
		if (!$this->validIds($navigationEntryIds)) {
			throw new OCSException('Navigation entry limit reached or invalid navigation entry IDs', Http::STATUS_BAD_REQUEST);
		}
		$this->saveNavigationEntryIds($circleId, $providerId, $navigationEntryIds);
		return new DataResponse([]);
	}

	private function teamName(string $circleId, IUser $user): string {
		$team = $this->teamManager->getTeam($circleId, $user->getUID());
		if ($team === null) {
			throw new OCSNotFoundException('Team not found');
		}
		return $team->getDisplayName();
	}

	/** @return array{entries: list<TeamAppNavigationEntry>, createAction?: TeamAppNavigationAction, selectedEntryIds: list<string>} */
	private function applySavedNavigationSelection(TeamAppNavigation $navigation, array $navigationEntryIds): array {
		$entryIds = array_map(static fn (TeamAppNavigationEntry $entry): string => $entry->id, $navigation->entries);
		$selectedEntryIds = array_values(array_intersect($navigationEntryIds, $entryIds));
		$response = ['entries' => $navigation->entries, 'selectedEntryIds' => $selectedEntryIds];
		if ($navigation->createAction !== null) {
			$response['createAction'] = $navigation->createAction;
		}
		return $response;
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
	 * @return list<string>
	 */
	private function getSavedNavigationEntryIds(array $settings, string $providerId): array {
		$navigationEntryIds = json_decode($settings[$this->settingKey($providerId)] ?? '[]', true);
		if (!is_array($navigationEntryIds) || !$this->validIds($navigationEntryIds)) {
			throw new OCSException('Invalid stored Teams navigation metadata', Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		return array_values(array_unique($navigationEntryIds));
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

	private function settingKey(string $providerId): string {
		return 'teamsAppNavigation_' . $providerId;
	}

	private function validIds(array $ids): bool {
		if (!array_is_list($ids) || count($ids) > 100) {
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
