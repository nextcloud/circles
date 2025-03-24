<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Search;

use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\ISearch;
use OCA\Circles\Model\DeprecatedMember;
use OCA\Circles\Model\SearchResult;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MiscService;
use OCA\Circles\Tools\Exceptions\RequestNetworkException;
use OCA\Circles\Tools\Exceptions\RequestResultNotJsonException;
use OCA\Circles\Tools\Model\NCRequest;
use OCA\Circles\Tools\Model\Request;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCA\Circles\Tools\Traits\TNCRequest;

/**
 * Class GlobalScaleUsers
 *
 * @package OCA\Circles\Search
 */
class GlobalScaleUsers implements ISearch {
	use TNCRequest;
	use TArrayTools;


	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * GlobalScaleUsers constructor.
	 *
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(ConfigService $configService, MiscService $miscService) {
		$this->configService = $configService;
		$this->miscService = $miscService;
	}

	/**
	 * {@inheritdoc}
	 */
	public function search(string $needle): array {
		/** @var string $lookup */
		try {
			$lookup = $this->configService->getGSLookup();
		} catch (GSStatusException $e) {
			return [];
		}

		$request = new NCRequest(ConfigService::GS_LOOKUP_USERS, Request::TYPE_GET);
		$this->configService->configureRequest($request);
		$request->basedOnUrl($lookup);
		$request->addParam('search', $needle);

		try {
			$users = $this->retrieveJson($request);
		} catch (
			RequestNetworkException|
			RequestResultNotJsonException $e
		) {
			$this->miscService->log(
				'Issue while search users from lookup: ' . get_class($e) . ' ' . $e->getMessage()
			);

			return [];
		}

		$result = [];
		foreach ($users as $user) {
			[, $instance] = explode('@', $this->get('federationId', $user), 2);
			if ($this->configService->isLocalInstance($instance)) {
				continue;
			}

			$result[] =
				new SearchResult(
					$this->get('userid.value', $user), DeprecatedMember::TYPE_USER, $instance,
					['display' => $this->get('name.value', $user)]
				);
		}

		return $result;
	}
}
