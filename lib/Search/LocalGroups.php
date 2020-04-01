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

namespace OCA\Circles\Search;

use OC;
use OCA\Circles\ISearch;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SearchResult;
use OCP\IUser;
use OCA\Circles\Service\ConfigService;

class LocalGroups implements ISearch {

	/** @var ConfigService */
	private $configService;

	/**
	 * @param ConfigService $configService
	 */
	public function __construct(ConfigService $configService)
	{
		$this->configService = $configService;
	}

	/**
	 * {@inheritdoc}
	 */
	public function search($search) {

		$result = [];
		$groupManager = OC::$server->getGroupManager();

		$groups = $groupManager->search($search);
		$user = OC::$server->getUserSession()->getUser();
		foreach ($groups as $group) {
			if ($this->configService->isAddingAnyGroupMembersAllowed() ||
				(
					$user instanceof IUser &&
					($group->inGroup($user) || $groupManager->isAdmin($user->getUID()))
				)
			) {
				$result[] = new SearchResult($group->getGID(), Member::TYPE_GROUP);
			}
		}

		return $result;
	}

}
