<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
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

namespace OCA\Circles\Service;


use OCA\Circles\ISearch;
use OCP\IL10N;
use OCP\IUserManager;

class SearchService {

	/** @var IL10N */
	private $l10n;

	/** @var IUserManager */
	private $userManager;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;

	/** @var string[] */
	private $searchList;

	/**
	 * MembersService constructor.
	 *
	 * @param IL10N $l10n
	 * @param IUserManager $userManager
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IL10N $l10n, IUserManager $userManager, ConfigService $configService,
		MiscService $miscService
	) {
		$this->l10n = $l10n;
		$this->userManager = $userManager;
		$this->configService = $configService;
		$this->miscService = $miscService;

		$this->loadSearch();
	}


	/**
	 * load list of search engine
	 */
	public function loadSearch() {
		$this->searchList = [
			'OCA\Circles\Search\LocalUsers',
			'OCA\Circles\Search\LocalGroups',
			'OCA\Circles\Search\Contacts'
		];
	}


	public function searchGlobal($str) {

		$result = [];
		foreach ($this->searchList as $container) {
			$searcher = \OC::$server->query((string)$container);

			if (!($searcher instanceof ISearch)) {
				$this->miscService->log('Search ' . $container . ' is not compatible exception');
				continue;
			}

			$result = array_merge($result, $searcher->search($str));
		}

		return $result;
	}

}