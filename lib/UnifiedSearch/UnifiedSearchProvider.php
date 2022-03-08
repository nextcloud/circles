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


namespace OCA\Circles\UnifiedSearch;

use OCA\Circles\Tools\Traits\TNCLogger;
use Exception;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\FederatedUserService;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;

/**
 * Class UnifiedSearchProvider
 *
 * @package OCA\Circles\UnifiedSearch
 */
class UnifiedSearchProvider implements IProvider {
	use TNCLogger;


	public const ORDER = 9;


	/** @var IL10N */
	private $l10n;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var CircleService */
	private $circleService;


	/**
	 * UnifiedSearchProvider constructor.
	 *
	 * @param IL10N $l10n
	 * @param IURLGenerator $urlGenerator
	 * @param FederatedUserService $federatedUserService
	 * @param CircleService $circleService
	 */
	public function __construct(
		IL10N $l10n,
		IURLGenerator $urlGenerator,
		FederatedUserService $federatedUserService,
		CircleService $circleService
	) {
		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;
		$this->federatedUserService = $federatedUserService;
		$this->circleService = $circleService;

		$this->setup('app', Application::APP_ID);
	}


	/**
	 * return unique id of the provider
	 */
	public function getId(): string {
		return Application::APP_ID;
	}


	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->l10n->t('Circles');
	}


	/**
	 * @param string $route
	 * @param array $routeParameters
	 *
	 * @return int
	 */
	public function getOrder(string $route, array $routeParameters): int {
		return self::ORDER;
	}


	/**
	 * @param IUser $user
	 * @param ISearchQuery $query
	 *
	 * @return SearchResult
	 */
	public function search(IUser $user, ISearchQuery $query): SearchResult {
		$result = [];

		$circle = new Circle();
		$circle->setDisplayName($query->getTerm());

		$probe = new CircleProbe();
		$probe->filterHiddenCircles()
			  ->filterBackendCircles();
		$probe->setFilterCircle($circle);

		try {
			$this->federatedUserService->initCurrentUser();
			$circles = $this->circleService->getCircles($probe);
			$result = $this->convertSearchResult($circles);
		} catch (Exception $e) {
		}

		return SearchResult::paginated(
			$this->getName(),
			$result,
			($query->getCursor() ?? 0) + $query->getLimit()
		);
	}


	/**
	 * @param Circle[] $circles
	 *
	 * @return UnifiedSearchResult[]
	 */
	private function convertSearchResult(array $circles): array {
		$result = [];

		$iconPath = $this->urlGenerator->imagePath(Application::APP_ID, 'circles.svg');
		$icon = $this->urlGenerator->getAbsoluteURL($iconPath);
		foreach ($circles as $circle) {
			$result[] = new UnifiedSearchResult(
				'',
				$circle->getDisplayName(),
				$this->circleService->getDefinition($circle),
				$circle->getUrl(),
				$icon
			);
		}

		return $result;
	}
}
