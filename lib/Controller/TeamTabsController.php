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
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;

/**
 * Team-level order of the tabs shown on a team's page.
 *
 * The order is stored in the circle settings, which regular members cannot
 * read through the circle serialization (settings are only serialized for
 * admins), so this controller exposes it to every member while restricting
 * writes to team admins and above.
 */
class TeamTabsController extends OCSController {
	private const SETTING_TAB_ORDER = 'teamsTabOrder';

	public function __construct(
		string $appName,
		IRequest $request,
		private readonly CircleRequest $circleRequest,
		private readonly CircleService $circleService,
		private readonly FederatedUserService $federatedUserService,
		private readonly PermissionService $permissionService,
		private readonly IUserSession $userSession,
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	public function getTabOrder(string $circleId): DataResponse {
		$this->assertAuthenticatedUserIsMember($circleId);

		try {
			$circle = $this->circleRequest->getCircle($circleId);
		} catch (CircleNotFoundException) {
			throw new OCSNotFoundException('Team not found');
		}

		$order = json_decode($circle->getSettings()[self::SETTING_TAB_ORDER] ?? '[]', true);

		return new DataResponse(['order' => TeamSettingsService::sanitizeIdList(is_array($order) ? $order : [])]);
	}

	/**
	 * @param list<string> $order Tab ids, first to last
	 */
	#[NoAdminRequired]
	public function setTabOrder(string $circleId, array $order): DataResponse {
		$member = $this->assertAuthenticatedUserIsMember($circleId);
		try {
			$this->permissionService->memberMustBeAtLeastAdmin($member);
		} catch (InsufficientPermissionException $e) {
			throw new OCSException($e->getMessage(), Http::STATUS_FORBIDDEN);
		}

		$order = TeamSettingsService::sanitizeIdList($order);

		try {
			// CircleService resolves the circle as the acting (federated) user
			$this->federatedUserService->setLocalCurrentUser($this->getAuthenticatedUser());
			$this->circleService->updateSetting($circleId, self::SETTING_TAB_ORDER, json_encode($order));
		} catch (\Exception $e) {
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}

		return new DataResponse(['order' => $order]);
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
