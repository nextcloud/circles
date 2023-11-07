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

use OCA\Circles\ISearch;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SearchResult;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCP\Collaboration\Collaborators\ISearch as ICollaboratorSearch;
use OCP\Share\IShare;

class LocalUsers implements ISearch {
	use TArrayTools;


	/** @var ICollaboratorSearch */
	private $search;

	/** @var ConfigService */
	private $configService;


	/**
	 * LocalUsers constructor.
	 *
	 * @param ICollaboratorSearch $search
	 * @param ConfigService $configService
	 */
	public function __construct(
		ICollaboratorSearch $search,
		ConfigService $configService
	) {
		$this->search = $search;
		$this->configService = $configService;
	}


	/**
	 * {@inheritdoc}
	 */
	public function search($needle): array {
		$result = [];
		$userManager = \OC::$server->getUserManager();

		if ($this->configService->getAppValue(ConfigService::CIRCLES_SEARCH_FROM_COLLABORATOR) === '1') {
			return $this->searchFromCollaborator($needle);
		}

		$users = $userManager->search($needle);
		foreach ($users as $user) {
			$result[] = new SearchResult(
				$user->getUID(),
				Member::TYPE_USER,
				'',
				['display' => $userManager->getDisplayName($user->getUID())]
			);
		}

		return $result;
	}


	/**
	 * @param $search
	 *
	 * @return array
	 */
	private function searchFromCollaborator($search): array {
		[$temp, $hasMore] = $this->search->search($search, [IShare::TYPE_USER, IShare::TYPE_EMAIL], false, 50, 0);

		$result = array_merge($temp['exact']['users'], $temp['users']);
		$parsed = [];
		foreach ($result as $entry) {
			$parsed[] =
				new SearchResult(
					$this->get('value.shareWith', $entry),
					Member::TYPE_USER,
					'',
					['display' => $this->get('label', $entry)]
				);
		}

		return $parsed;
	}
}
