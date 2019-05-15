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

class LocalGroups implements ISearch {

	/**
	 * {@inheritdoc}
	 */
	public function search($search) {

		$result = [];
		$groupManager = \OC::$server->getGroupManager();
		$config = \OC::$server->getConfig();
		$listOwnGroupsOnly = $config->getAppValue('core', 'shareapi_only_share_with_group_members', 'no') === 'yes';
		$self = \OC::$server->getUserSession()->getUser();
		if ($self === null) {
			// This will probably never happen, just to stay consistent with the rest of the codebase.
			return $result;
		}

		if ($listOwnGroupsOnly) {
			// TODO: Add support for 'shareapi_exclude_groups' / 'shareapi_exclude_groups_list'
			$ownGroupIDs = $groupManager->getUserGroupIds($self);
			foreach ($ownGroupIDs as $gid) {
				$result[] = new SearchResult($gid, Member::TYPE_GROUP);
			}
			return $result;
		}

		$groups = $groupManager->search($search);
		foreach ($groups as $group) {
			$result[] = new SearchResult($group->getGID(), Member::TYPE_GROUP);
		}

		return $result;
	}

}
