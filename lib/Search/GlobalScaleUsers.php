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

use daita\MySmallPhpTools\Exceptions\RequestContentException;
use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Exceptions\RequestResultNotJsonException;
use daita\MySmallPhpTools\Exceptions\RequestResultSizeException;
use daita\MySmallPhpTools\Exceptions\RequestServerException;
use daita\MySmallPhpTools\Model\Request;
use daita\MySmallPhpTools\Traits\TArrayTools;
use daita\MySmallPhpTools\Traits\TRequest;
use OCA\Circles\Exceptions\GSStatusException;
use OCA\Circles\ISearch;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SearchResult;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MiscService;


/**
 * Class GlobalScaleUsers
 *
 * @package OCA\Circles\Search
 */
class GlobalScaleUsers implements ISearch {


	use TRequest;
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
	public function search($search) {

		/** @var string $lookup */
		try {
			$lookup = $this->configService->getGSStatus(ConfigService::GS_LOOKUP);
		} catch (GSStatusException $e) {
			return [];
		}

		$request = new Request('/users', Request::TYPE_GET);
		$request->setProtocols(['https', 'http']);
		$request->addData('search', $search);
		$request->setAddressFromUrl($lookup);

		try {
			$users = $this->retrieveJson($request);
		} catch (
		RequestContentException |
		RequestNetworkException |
		RequestResultSizeException |
		RequestServerException |
		RequestResultNotJsonException $e
		) {
			$this->miscService->log('Issue while retrieving instances from lookup: ' . get_class($e) . ' ' . $e->getMessage());

			return [];
		}

		$result = [];
		foreach ($users as $user) {
			list(, $instance) = explode('@', $this->get('federationId', $user), 2);
			if ($instance === $this->configService->getLocalCloudId()) {
				continue;
			}

			$result[] =
				new SearchResult(
					$this->get('userid.value', $user), Member::TYPE_USER, $instance,
					['display' => $this->get('name.value', $user)]
				);
		}

		return $result;
	}
}


