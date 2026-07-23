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
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Teams\ITeamManager;
use OCP\Teams\Team;

class TeamFolderController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly ITeamManager $teamManager,
		private readonly TeamFolderPolicy $policy,
		private readonly CircleRequest $circleRequest,
		private readonly PermissionService $permissionService,
		private readonly IUserSession $userSession,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 */
	public function getTeamFolder(string $circleId): DataResponse {
		$this->requireMember($circleId);
		$folder = $this->getProvider()->getTeamFolder($circleId);
		if ($folder === null) {
			throw new OCSNotFoundException('No team folder linked to this team');
		}

		return new DataResponse($folder->jsonSerialize());
	}

	/**
	 * @NoAdminRequired
	 */
	public function upgradeTeamFolder(string $circleId): DataResponse {
		$circle = $this->getCircle($circleId);
		$this->requireTeamAdmin($circleId);
		$folder = $this->getProvider()->createTeamFolder(
			new Team(
				teamId: $circle->getSingleId(),
				displayName: $circle->getDisplayName(),
				link: null,
			),
			$this->policy->getDefaultQuota(),
		);

		return new DataResponse([
			'success' => true,
			'folderId' => $folder->getId(),
			'folder' => $folder->jsonSerialize(),
		]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function unlinkTeamFolder(string $circleId, bool $deleteFolder = false): DataResponse {
		$this->requireTeamOwner($circleId);
		$provider = $this->getProvider();
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

	private function requireMember(string $circleId): void {
		try {
			$this->permissionService->userMustBeMember($this->getAuthenticatedUser()->getUID(), $circleId);
		} catch (InsufficientPermissionException $e) {
			throw new OCSException($e->getMessage(), Http::STATUS_FORBIDDEN);
		}
	}

	private function requireTeamAdmin(string $circleId): void {
		try {
			$this->permissionService->userMustBeAtLeastTeamAdminOrServerAdmin($this->getAuthenticatedUser()->getUID(), $circleId);
		} catch (InsufficientPermissionException $e) {
			throw new OCSException($e->getMessage(), Http::STATUS_FORBIDDEN);
		}
	}

	private function requireTeamOwner(string $circleId): void {
		try {
			$member = $this->permissionService->userMustBeMember($this->getAuthenticatedUser()->getUID(), $circleId);
			$this->permissionService->memberMustBeOwner($member);
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
