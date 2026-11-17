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
use OCA\Circles\Service\PermissionService;
use OCA\Circles\Service\TeamFolderPolicy;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Teams\ITeamFolderPoolProvider;
use OCP\Teams\ITeamManager;
use OCP\Teams\Team;
use OCP\Teams\TeamFolderPoolQuotaException;

class TeamFolderController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly ITeamManager $teamManager,
		private readonly TeamFolderPolicy $policy,
		private readonly CircleRequest $circleRequest,
		private readonly PermissionService $permissionService,
		private readonly IGroupManager $groupManager,
		private readonly IUserSession $userSession,
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	public function getCreationStoragePools(): DataResponse {
		return new DataResponse($this->policy->getCreationStoragePoolOptions($this->getAuthenticatedUser()->getUID()));
	}

	#[NoAdminRequired]
	public function getTeamFolder(string $circleId): DataResponse {
		$this->assertAuthenticatedUserIsMember($circleId);
		$folder = $this->getProvider()->getTeamFolder($circleId);
		if ($folder === null) {
			throw new OCSNotFoundException('No team folder linked to this team');
		}

		return new DataResponse($folder->jsonSerialize());
	}

	#[NoAdminRequired]
	public function upgradeTeamFolder(string $circleId, string $name = ''): DataResponse {
		$circle = $this->getCircle($circleId);
		$user = $this->getAuthenticatedUser();
		$this->assertAuthenticatedUserIsTeamOwnerOrServerAdmin($circleId, $user);

		if (!$this->policy->isTeamFolderProvisioningEnabled() && !$this->groupManager->isAdmin($user->getUID())) {
			throw new OCSException('Team folder provisioning is disabled', Http::STATUS_FORBIDDEN);
		}

		if (!$this->policy->isEligibleCircle($circle)) {
			throw new OCSException('This team cannot have a team folder', Http::STATUS_FORBIDDEN);
		}

		$provider = $this->getProvider();
		try {
			if ($provider instanceof ITeamFolderPoolProvider) {
				$this->policy->assignStoragePoolForUser($provider, $circleId, $user->getUID());
			}
			$folder = $provider->createTeamFolder(
				new Team(
					teamId: $circle->getSingleId(),
					displayName: trim($name) ?: $circle->getDisplayName(),
					link: null,
				),
				$this->policy->getQuotaForCircle($circle),
			);
		} catch (TeamFolderPoolQuotaException $exception) {
			return $this->poolConflict($exception);
		} catch (\InvalidArgumentException $exception) {
			throw new OCSException($exception->getMessage(), Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse([
			'success' => true,
			'folderId' => $folder->getId(),
			'folder' => $folder->jsonSerialize(),
		]);
	}

	#[NoAdminRequired]
	public function getLinkableTeamFolders(string $circleId): DataResponse {
		$this->assertAuthenticatedUserIsTeamOwnerOrServerAdmin($circleId);

		return new DataResponse(array_map(
			static fn (\OCP\Teams\TeamFolder $folder): array => $folder->jsonSerialize(),
			$this->getProvider()->getLinkableTeamFolders($circleId),
		));
	}

	#[NoAdminRequired]
	public function linkTeamFolder(string $circleId, int $folderId): DataResponse {
		$this->assertAuthenticatedUserIsTeamOwnerOrServerAdmin($circleId);
		$provider = $this->getProvider();
		try {
			if ($provider instanceof ITeamFolderPoolProvider) {
				$this->policy->assignStoragePoolForUser($provider, $circleId, $this->getAuthenticatedUser()->getUID());
			}
			$folder = $provider->linkTeamFolder($circleId, $folderId);
		} catch (TeamFolderPoolQuotaException $exception) {
			return $this->poolConflict($exception);
		} catch (\InvalidArgumentException $exception) {
			throw new OCSException($exception->getMessage(), Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse([
			'success' => true,
			'folderId' => $folder->getId(),
			'folder' => $folder->jsonSerialize(),
		]);
	}

	#[NoAdminRequired]
	public function updateTeamFolderQuota(string $circleId, mixed $quota): DataResponse {
		$this->assertAuthenticatedUserIsTeamOwnerOrServerAdmin($circleId);
		if (!is_int($quota) || $quota < 0 || $quota > 9007199254740991) {
			throw new OCSException('Quota must be a non-negative number', Http::STATUS_BAD_REQUEST);
		}

		try {
			$folder = $this->getProvider()->updateTeamFolderQuota($circleId, $quota);
		} catch (TeamFolderPoolQuotaException $exception) {
			return $this->poolConflict($exception);
		} catch (\InvalidArgumentException $exception) {
			throw new OCSException($exception->getMessage(), Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse([
			'success' => true,
			'folderId' => $folder->getId(),
			'folder' => $folder->jsonSerialize(),
		]);
	}

	#[NoAdminRequired]
	public function unlinkTeamFolder(string $circleId, bool $deleteFolder = false): DataResponse {
		$this->assertAuthenticatedUserIsTeamOwnerOrServerAdmin($circleId);
		$provider = $this->getProvider();
		if (!$deleteFolder && $provider instanceof ITeamFolderPoolProvider
			&& isset($provider->getTeamStoragePoolAssignments([$circleId])[$circleId])
			&& !$this->groupManager->isAdmin($this->getAuthenticatedUser()->getUID())) {
			throw new OCSException('Only a server administrator can unlink a pooled folder without deleting it', Http::STATUS_FORBIDDEN);
		}
		if ($deleteFolder) {
			$changed = $provider->removeTeamFolder($circleId);
		} else {
			$changed = $provider->unlinkTeamFolder($circleId) !== null;
		}
		if (!$changed) {
			throw new OCSNotFoundException('No team folder linked to this team');
		}

		return new DataResponse(['success' => true]);
	}

	private function poolConflict(TeamFolderPoolQuotaException $exception): DataResponse {
		return new DataResponse([
			'code' => 'storage_pool_exceeded',
			'message' => $exception->getMessage(),
			'pool' => $exception->pool,
		], Http::STATUS_CONFLICT);
	}

	private function getProvider(): \OCP\Teams\ITeamFolderProvider {
		$provider = $this->teamManager->getTeamFolderProvider();
		if ($provider === null) {
			throw new OCSNotFoundException('No team folder provider is enabled');
		}

		return $provider;
	}

	private function getCircle(string $circleId): \OCA\Circles\Model\Circle {
		try {
			return $this->circleRequest->getCircle($circleId);
		} catch (CircleNotFoundException) {
			throw new OCSNotFoundException('Team not found');
		}
	}

	private function assertAuthenticatedUserIsMember(string $circleId): void {
		try {
			$this->permissionService->userMustBeMember($this->getAuthenticatedUser()->getUID(), $circleId);
		} catch (InsufficientPermissionException $e) {
			throw new OCSException($e->getMessage(), Http::STATUS_FORBIDDEN);
		}
	}

	private function assertAuthenticatedUserIsTeamOwnerOrServerAdmin(string $circleId, ?IUser $user = null): void {
		try {
			$this->permissionService->userMustBeTeamOwnerOrServerAdmin(($user ?? $this->getAuthenticatedUser())->getUID(), $circleId);
		} catch (InsufficientPermissionException $e) {
			throw new OCSException($e->getMessage(), Http::STATUS_FORBIDDEN);
		}
	}

	private function getAuthenticatedUser(): IUser {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new OCSException('Authentication required', Http::STATUS_UNAUTHORIZED);
		}

		return $user;
	}
}
