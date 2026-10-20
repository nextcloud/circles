<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Controller;

use OCA\Circles\ConfigLexicon;
use OCA\Circles\Exceptions\InsufficientPermissionException;
use OCA\Circles\Service\PermissionService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCSController;
use OCP\Config\IUserConfig;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Per-user, ordered list of favorite navigation entries of a team, stored as
 * the same entry ids the team-level tab order uses (e.g. "page-42").
 */
class TeamResourceFavoritesController extends OCSController {
	private const MAX_ID_LENGTH = 64;
	private const MAX_ENTRIES = 100;

	public function __construct(
		string $appName,
		IRequest $request,
		private readonly IUserConfig $userConfig,
		private readonly PermissionService $permissionService,
		private readonly IUserSession $userSession,
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	public function getFavorites(string $circleId): DataResponse {
		$userId = $this->assertAuthenticatedUserIsMember($circleId);
		$favorites = $this->userConfig->getValueArray($userId, $this->appName, ConfigLexicon::USER_RESOURCE_FAVORITES_PREFIX . $circleId, [], true);

		return new DataResponse(['favorites' => $this->sanitize($favorites)]);
	}

	/**
	 * @param list<string> $favorites Entry ids, first to last
	 */
	#[NoAdminRequired]
	public function setFavorites(string $circleId, array $favorites): DataResponse {
		$userId = $this->assertAuthenticatedUserIsMember($circleId);
		$favorites = $this->sanitize($favorites);
		$this->userConfig->setValueArray($userId, $this->appName, ConfigLexicon::USER_RESOURCE_FAVORITES_PREFIX . $circleId, $favorites, true);

		return new DataResponse(['favorites' => $favorites]);
	}

	/**
	 * @return list<string>
	 */
	private function sanitize(array $favorites): array {
		$favorites = array_filter($favorites, static fn ($id): bool => is_string($id)
			&& $id !== ''
			&& strlen($id) <= self::MAX_ID_LENGTH);

		return array_slice(array_values(array_unique($favorites)), 0, self::MAX_ENTRIES);
	}

	private function assertAuthenticatedUserIsMember(string $circleId): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new OCSException('Authentication required', Http::STATUS_UNAUTHORIZED);
		}
		try {
			$this->permissionService->userMustBeMember($user->getUID(), $circleId);
		} catch (InsufficientPermissionException $e) {
			throw new OCSException($e->getMessage(), Http::STATUS_FORBIDDEN);
		}

		return $user->getUID();
	}
}
