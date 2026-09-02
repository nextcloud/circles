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

	/**
	 * When a team admin drags the sidebar entries into a new order, the
	 * client sends the server a list of ids like ["team-folder", "page-42",
	 * "home"], and the server saves that list in the team's settings.
	 *
	 * These limits exist because the server just trusts whatever list it
	 * receives: nothing stops someone from skipping the UI and sending the
	 * request by hand, with a list of ten million entries, or ids that are
	 * each a megabyte of garbage text. Without the limits the server would
	 * happily save all of it. Real ids and real teams stay far below them.
	 */
	private const MAX_ID_LENGTH = 64;
	private const MAX_ENTRIES = 100;

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

		return new DataResponse(['order' => $this->sanitizeOrder(is_array($order) ? $order : [])]);
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

		$order = $this->sanitizeOrder($order);

		try {
			// CircleService resolves the circle as the acting (federated) user
			$this->federatedUserService->setLocalCurrentUser($this->getAuthenticatedUser());
			$this->circleService->updateSetting($circleId, self::SETTING_TAB_ORDER, json_encode($order));
		} catch (\Exception $e) {
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}

		return new DataResponse(['order' => $order]);
	}

	/**
	 * Keep only non-empty strings within the structural bounds,
	 * deduplicated, reindexed.
	 *
	 * @return list<string>
	 */
	private function sanitizeOrder(array $order): array {
		$order = array_filter($order, static fn ($id): bool => is_string($id)
			&& $id !== ''
			&& strlen($id) <= self::MAX_ID_LENGTH);

		return array_slice(array_values(array_unique($order)), 0, self::MAX_ENTRIES);
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
