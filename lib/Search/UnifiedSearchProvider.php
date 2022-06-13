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


namespace OCA\Circles\Search;

use Exception;
use OCA\Circles\Exceptions\ParseMemberLevelException;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\SearchService;
use OCP\IL10N;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;

class UnifiedSearchProvider implements IProvider {
	public const PROVIDER_ID = 'circles';
	public const ORDER = 9;


	/** @var IL10N */
	private $l10n;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var SearchService */
	private $searchService;


	/**
	 * @param IL10N $l10n
	 * @param FederatedUserService $federatedUserService
	 * @param SearchService $searchService
	 */
	public function __construct(
		IL10N $l10n,
		FederatedUserService $federatedUserService,
		SearchService $searchService
	) {
		$this->l10n = $l10n;
		$this->federatedUserService = $federatedUserService;
		$this->searchService = $searchService;
	}


	/**
	 * return unique id of the provider
	 */
	public function getId(): string {
		return self::PROVIDER_ID;
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
		$term = $query->getTerm();
		$options = $this->extractOptions($term);

		$result = [];
		try {
			$this->federatedUserService->setLocalCurrentUser($user);
			$result = $this->searchService->unifiedSearch($term, $options);
		} catch (Exception $e) {
		}

		return SearchResult::paginated(
			$this->l10n->t('Circles'),
			$result,
			($query->getCursor() ?? 0) + $query->getLimit()
		);
	}


	/**
	 * This is temporary, should be handled by core to extract Options from Term
	 *
	 * @param string $term
	 *
	 * @return array
	 */
	private function extractOptions(string &$term): array {
		$new = $options = [];
		foreach (explode(' ', $term) as $word) {
			$word = trim($word);
			if (strtolower(substr($word, 0, 3)) === 'is:') {
				try {
					$options['level'] = Member::parseLevelString(substr($word, 3));
				} catch (ParseMemberLevelException $e) {
				}
			} else {
				$new[] = $word;
			}
		}

		$term = trim(implode(' ', $new));

		return $options;
	}
}
