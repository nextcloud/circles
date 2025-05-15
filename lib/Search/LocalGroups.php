<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Search;

use OCA\Circles\ISearch;
use OCA\Circles\Model\DeprecatedMember;
use OCA\Circles\Model\SearchResult;
use OCP\IGroupManager;
use OCP\Server;

class LocalGroups implements ISearch {
	/**
	 * {@inheritdoc}
	 */
	public function search($needle): array {
		$result = [];
		$groupManager = Server::get(IGroupManager::class);

		$groups = $groupManager->search($needle);
		foreach ($groups as $group) {
			$result[] = new SearchResult($group->getGID(), DeprecatedMember::TYPE_GROUP);
		}

		return $result;
	}
}
