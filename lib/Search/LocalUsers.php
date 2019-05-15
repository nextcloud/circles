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

use OCA\Circles\ISearch;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\SearchResult;

class LocalUsers implements ISearch {

	/**
	 * {@inheritdoc}
	 */
	public function search($search) {

		$result = [];
		$userManager = \OC::$server->getUserManager();
		$groupManager = \OC::$server->getGroupManager();
		$config = \OC::$server->getConfig();
		$disallowUserEnumeration = $config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'no') !== 'yes';
		$self = \OC::$server->getUserSession()->getUser();
		if ($self === null) {
			// This will probably never happen, just to stay consistent with the rest of the codebase.
			return $result;
		}

		if ($disallowUserEnumeration) {
			// Only list users in common groups.
			// TODO: Add support for 'shareapi_exclude_groups' / 'shareapi_exclude_groups_list'
			$ownGroups = $groupManager->getUserGroups($self);
			$allMembersByID = [];
			foreach ($ownGroups as $g) {
				$members = $g->getUsers();
				foreach ($members as $m) {
					$allMembersByID[$m->getUID()] = $m;
				}
			}
			foreach ($allMembersByID as $uid => $m) {
				$result[] =
					new SearchResult(
						$uid, Member::TYPE_USER, ['display' => $m->getDisplayName()]
					);
			}
			return $result;
		}

		$users = $userManager->search($search);
		foreach ($users as $user) {
			$result[] =
				new SearchResult(
					$user->getUID(), Member::TYPE_USER, ['display' => $user->getDisplayName()]
				);
		}

		return $result;
	}
}


