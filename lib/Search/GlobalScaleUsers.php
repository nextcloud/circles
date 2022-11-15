<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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

use OCA\Circles\Tools\Exceptions\RequestNetworkException;
use OCA\Circles\Tools\Exceptions\RequestResultNotJsonException;
use OCA\Circles\Tools\Model\NCRequest;
use OCA\Circles\Tools\Model\Request;
use OCA\Circles\Tools\Traits\TNCRequest;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\ISearch;
use OCA\Circles\Model\DeprecatedMember;
use OCA\Circles\Model\SearchResult;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MiscService;

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
			RequestNetworkException |
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
