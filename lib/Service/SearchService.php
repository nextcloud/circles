<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Circles\Service;

use OC;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\IFederatedUser;
use OCA\Circles\ISearch;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\SearchResult;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Search\FederatedUsers;
use OCA\Circles\Search\UnifiedSearchResult;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCP\IURLGenerator;

class SearchService {
	use TArrayTools;


	public static $SERVICES = [
		FederatedUsers::class
	];


	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var CircleService */
	private $circleService;


	/**
	 * @param IURLGenerator $urlGenerator
	 * @param CircleService $circleService
	 */
	public function __construct(
		IURLGenerator $urlGenerator,
		CircleService $circleService
	) {
		$this->urlGenerator = $urlGenerator;
		$this->circleService = $circleService;
	}


	/**
	 * @param string $needle
	 *
	 * @return list<SearchResult|IFederatedUser>
	 */
	public function search(string $needle): array {
		$result = [];

		foreach (self::$SERVICES as $entry) {
			/** @var ISearch $service */
			$service = OC::$server->get($entry);

			$result = array_merge($result, $service->search($needle));
		}

		return $result;
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
		} catch (InitiatorNotFoundException $e) {
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
