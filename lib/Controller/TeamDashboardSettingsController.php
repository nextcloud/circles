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
use OCA\Circles\Service\TeamDashboardSettingsService;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\Dashboard\IManager;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Teams\ITeamManager;

class TeamDashboardSettingsController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private CircleRequest $circleRequest,
		private CircleService $circleService,
		private FederatedUserService $federatedUserService,
		private PermissionService $permissionService,
		private IUserSession $userSession,
		private ITeamManager $teamManager,
		private IManager $dashboardManager,
		private TeamDashboardSettingsService $settings,
	) {
		parent::__construct($appName, $request);
	}

	private function authorize(string $circleId): Member {
		$user = $this->userSession->getUser();
		if ($user === null || !$this->teamManager->isMemberOfTeam($circleId, $user->getUID())) {
			throw new OCSNotFoundException('Team not found');
		}
		try {
			return $this->permissionService->userMustBeMember($user->getUID(), $circleId);
		} catch (InsufficientPermissionException) {
			throw new OCSNotFoundException('Team not found');
		}
	}

	private function canEdit(Member $member): bool {
		try {
			$this->permissionService->memberMustBeAtLeastAdmin($member);
			return true;
		} catch (InsufficientPermissionException) {
			return false;
		}
	}

	private function readWidgetIds(string $circleId): ?array {
		try {
			$circle = $this->circleRequest->getCircle($circleId);
		} catch (CircleNotFoundException) {
			throw new OCSNotFoundException('Team not found');
		}
		return $this->settings->decode($circle->getSettings()[TeamDashboardSettingsService::SETTING] ?? null);
	}

	#[NoAdminRequired]
	public function getLayout(string $circleId): DataResponse {
		$member = $this->authorize($circleId);
		return new DataResponse(['widgetIds' => $this->readWidgetIds($circleId), 'canEdit' => $this->canEdit($member)]);
	}

	#[NoAdminRequired]
	public function setLayout(string $circleId, mixed $widgetIds): DataResponse {
		$member = $this->authorize($circleId);
		if (!$this->canEdit($member)) {
			throw new OCSException('Only team admins can change dashboards', 403);
		}
		if ($widgetIds !== null) {
			$allowedIds = array_merge(array_keys($this->dashboardManager->getTeamWidgets()), $this->readWidgetIds($circleId) ?? []);
			$widgetIds = $this->settings->validate($widgetIds, $allowedIds);
		}
		$this->federatedUserService->setLocalCurrentUser($this->userSession->getUser());
		$this->circleService->updateSetting($circleId, TeamDashboardSettingsService::SETTING, json_encode($widgetIds, JSON_THROW_ON_ERROR));
		return new DataResponse(['widgetIds' => $widgetIds, 'canEdit' => true]);
	}
}
