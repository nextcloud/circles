<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Controller;

use OCA\Circles\Service\TeamFavoriteService;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class TeamFavoritesController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly TeamFavoriteService $teamFavoriteService,
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	public function getFavorites(): DataResponse {
		return new DataResponse([
			'circleIds' => $this->teamFavoriteService->getFavoriteCircleIds(),
		]);
	}

	#[NoAdminRequired]
	public function setFavorite(string $circleId, bool $isFavorite): DataResponse {
		$circleIds = $this->teamFavoriteService->setFavorite($circleId, $isFavorite);

		return new DataResponse([
			'circleId' => $circleId,
			'isFavorite' => $isFavorite,
			'circleIds' => $circleIds,
		]);
	}

	/**
	 * @param list<string> $circleIds
	 * @param list<string> $expectedCircleIds
	 */
	#[NoAdminRequired]
	public function reorder(array $circleIds, array $expectedCircleIds): DataResponse {
		return new DataResponse([
			'circleIds' => $this->teamFavoriteService->reorder($circleIds, $expectedCircleIds),
		]);
	}
}
