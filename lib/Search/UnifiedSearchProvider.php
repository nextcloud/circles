<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
		SearchService $searchService,
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
		return $this->l10n->t('Teams');
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
			$this->l10n->t('Teams'),
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
