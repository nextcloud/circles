<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Search\UnifiedSearchResult;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCP\IURLGenerator;

class SearchService {
	use TArrayTools;

	public function __construct(
		private readonly IURLGenerator $urlGenerator,
		private readonly CircleService $circleService,
		private readonly MemberRequest $memberRequest,
	) {
	}

	/**
	 * @param string $needle
	 *
	 * @return list<IFederatedUser>
	 */
	public function search(string $needle): array {
		return $this->memberRequest->searchFederatedUsers($needle);
	}

	/**
	 * @param string $term
	 * @param array $options
	 *
	 * @return UnifiedSearchResult[]
	 * @throws RequestBuilderException
	 */
	public function unifiedSearch(string $term, array $options): array {
		$result = [];
		$probe = $this->generateSearchProbe($term, $options);

		try {
			$circles = $this->circleService->getCircles($probe);
		} catch (InitiatorNotFoundException) {
			return [];
		}

		$iconPath = $this->urlGenerator->imagePath(Application::APP_ID, 'circles.svg');
		$icon = $this->urlGenerator->getAbsoluteURL($iconPath);
		foreach ($circles as $circle) {
			$result[] = new UnifiedSearchResult(
				'',
				$circle->getDisplayName(),
				$circle->getDescription(),
				$circle->getUrl(),
				$icon
			);
		}

		return $result;
	}

	/**
	 * @param string $term
	 * @param array $options
	 *
	 * @return CircleProbe
	 */
	private function generateSearchProbe(string $term, array $options): CircleProbe {
		$probe = new CircleProbe();
		switch ($this->getInt('level', $options)) {
			case Member::LEVEL_MEMBER:
				$probe->mustBeMember();
				break;
			case Member::LEVEL_MODERATOR:
				$probe->mustBeModerator();
				break;
			case Member::LEVEL_ADMIN:
				$probe->mustBeAdmin();
				break;
			case Member::LEVEL_OWNER:
				$probe->mustBeOwner();
				break;
		}

		$probe->filterHiddenCircles()
			->filterBackendCircles();

		$circle = new Circle();
		$circle->setDisplayName($term);

		$probe->setFilterCircle($circle);

		return $probe;
	}
}
