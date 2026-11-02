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
use OCP\Teams\TeamAppAction;
use OCP\Teams\TeamAppActionResult;
use OCP\Teams\TeamAppNavigation;

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
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	public function getNavigationEntryIds(string $circleId, string $providerId): DataResponse {
		$this->authorize($circleId, $providerId, false);
		try {
			$circle = $this->circleRequest->getCircle($circleId);
		} catch (CircleNotFoundException) {
			throw new OCSNotFoundException('Team not found');
		}
		$navigationEntryIds = json_decode($circle->getSettings()[$this->settingKey($providerId)] ?? '[]', true);
		if (!is_array($navigationEntryIds) || !$this->validIds($navigationEntryIds)) {
			throw new OCSException('Invalid stored Teams navigation metadata', Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		return new DataResponse(['navigationEntryIds' => array_values(array_unique($navigationEntryIds))]);
	}

	/** @param list<string> $navigationEntryIds */
	#[NoAdminRequired]
	public function setNavigationEntryIds(string $circleId, string $providerId, array $navigationEntryIds): DataResponse {
		$user = $this->authorize($circleId, $providerId, true);
		if (!$this->validIds($navigationEntryIds)) {
			throw new OCSException('Navigation entry IDs must be a list of at most 100 non-empty resource IDs of at most 64 bytes', Http::STATUS_BAD_REQUEST);
		}
		$navigationEntryIds = array_values(array_unique($navigationEntryIds));
		$this->federatedUserService->setLocalCurrentUser($user);
		$this->circleService->updateSetting($circleId, $this->settingKey($providerId), json_encode($navigationEntryIds, JSON_THROW_ON_ERROR));
		return new DataResponse(['navigationEntryIds' => $navigationEntryIds]);
	}

	/** @return DataResponse<Http::STATUS_OK, TeamAppNavigation, array{}> */
	#[NoAdminRequired]
	public function getNavigation(string $circleId, string $providerId): DataResponse {
		$context = $this->context($circleId, $providerId, false);
		return new DataResponse($this->providers->getProviders()[$providerId]->getNavigation($context));
	}

	/** @return DataResponse<Http::STATUS_OK, TeamAppActionResult, array{}> */
	#[NoAdminRequired]
	public function action(string $circleId, string $providerId, string $actionId, array $parameters = []): DataResponse {
		$context = $this->context($circleId, $providerId, true);
		$provider = $this->providers->getProviders()[$providerId];
		$navigation = $provider->getNavigation($context);
		$actions = $navigation->actions ?? [];
		foreach ($navigation->entries as $entry) {
			array_push($actions, ...($entry->actions ?? []));
		}
		if (!in_array($actionId, array_map(static fn (TeamAppAction $action): string => $action->id, $actions), true)) {
			throw new OCSNotFoundException('Teams app action not found');
		}
		try {
			$result = $provider->executeAction($context, $actionId, $parameters);
		} catch (\InvalidArgumentException $e) {
			throw new OCSException($e->getMessage(), Http::STATUS_BAD_REQUEST);
		} catch (\OCP\Files\NotPermittedException $e) {
			throw new OCSException($e->getMessage(), Http::STATUS_FORBIDDEN);
		}
		if ($result->navigationEntryIds !== null) {
			$this->setNavigationEntryIds($circleId, $providerId, $result->navigationEntryIds);
		}
		return new DataResponse($result);
	}

	private function context(string $circleId, string $providerId, bool $write): array {
		$user = $this->authorize($circleId, $providerId, $write);
		$team = $this->teamManager->getTeam($circleId, $user->getUID());
		if ($team === null) {
			throw new OCSNotFoundException('Team not found');
		}
		$member = $this->permissionService->userMustBeMember($user->getUID(), $circleId);
		return [
			'teamId' => $circleId,
			'teamName' => $team->getDisplayName(),
			'userId' => $user->getUID(),
			'isAdmin' => $member->getLevel() >= Member::LEVEL_ADMIN,
			'folder' => $this->teamManager->getTeamFolderProvider()?->getTeamFolder($circleId),
			'resources' => $this->teamManager->getSharedWith($circleId, $user->getUID()),
			'navigationEntryIds' => $this->getNavigationEntryIds($circleId, $providerId)->getData()['navigationEntryIds'],
			'saveNavigationEntryIds' => fn (array $navigationEntryIds) => $this->setNavigationEntryIds($circleId, $providerId, $navigationEntryIds),
		];
	}

	private function authorize(string $circleId, string $providerId, bool $write): IUser {
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
		if (!isset($this->providers->getProviders()[$providerId])) {
			throw new OCSNotFoundException('Teams app provider not found');
		}
		return $user;
	}

	private function settingKey(string $providerId): string {
		return 'teamsAppPins_' . $providerId;
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
