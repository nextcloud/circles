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
use OCA\Circles\Service\TeamSettingsService;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;

class TeamDashboardController extends OCSController {

	private const SETTING_WIDGET_IDS = 'teamsDashboardWidgetIds';

	public function __construct(
		string $appName,
		IRequest $request,
		private readonly CircleRequest $circleRequest,
		private readonly CircleService $circleService,
		private readonly FederatedUserService $federatedUserService,
		private readonly PermissionService $permissionService,
		private readonly IUserSession $userSession,
		private readonly IAppManager $appManager,
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	public function getLayout(string $circleId): DataResponse {
		$member = $this->assertAuthenticatedUserIsMember($circleId);
		try {
			$circle = $this->circleRequest->getCircle($circleId);
		} catch (CircleNotFoundException) {
			throw new OCSNotFoundException('Team not found');
		}
		$ids = json_decode($circle->getSettings()[self::SETTING_WIDGET_IDS] ?? 'null');
		$canEdit = true;
		try {
			$this->permissionService->memberMustBeAtLeastAdmin($member);
		} catch (InsufficientPermissionException) {
			$canEdit = false;
		}

		return new DataResponse([
			'widgetIds' => is_array($ids) && array_is_list($ids) ? TeamSettingsService::sanitizeIdList($ids) : null,
			'canEdit' => $canEdit,
			'available' => $this->appManager->isEnabledForUser('dashboard'),
		]);
	}

	#[NoAdminRequired]
	public function setLayout(string $circleId, ?array $widgetIds): DataResponse {
		$member = $this->assertAuthenticatedUserIsMember($circleId);
		try {
			$this->permissionService->memberMustBeAtLeastAdmin($member);
		} catch (InsufficientPermissionException $e) {
			throw new OCSException($e->getMessage(), Http::STATUS_FORBIDDEN);
		}
		$widgetIds = $widgetIds === null ? null : TeamSettingsService::sanitizeIdList($widgetIds);
		try {
			$this->federatedUserService->setLocalCurrentUser($this->getAuthenticatedUser());
			$this->circleService->updateSetting($circleId, self::SETTING_WIDGET_IDS, json_encode($widgetIds));
		} catch (\Exception $e) {
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}

		return new DataResponse(['widgetIds' => $widgetIds, 'canEdit' => true]);
	}

	private function assertAuthenticatedUserIsMember(string $circleId): Member {
		try {
			return $this->permissionService->userMustBeMember($this->getAuthenticatedUser()->getUID(), $circleId);
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
