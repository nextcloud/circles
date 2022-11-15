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


namespace OCA\Circles\Db;

use OCA\Circles\Exceptions\GSStatusException;

/**
 * @deprecated
 * Class CircleProviderRequest
 *
 * @package OCA\Circles\Db
 */
class CircleProviderRequest extends CircleProviderRequestBuilder {
	/**
	 * @param $userId
	 * @param $circleUniqueIds
	 * @param $limit
	 * @param $offset
	 *
	 * @return array
	 * @throws GSStatusException
	 */
	public function getFilesForCircles($userId, $circleUniqueIds, $limit, $offset) {
		$qb = $this->getCompleteSelectSql();
		$this->linkToFileCache($qb, $userId);
		$this->limitToPage($qb, $limit, $offset);
		$this->limitToCircles($qb, $circleUniqueIds);

		$this->linkToMember($qb, $userId, false, 'c');

		//	$this->leftJoinShareInitiator($qb);

		$cursor = $qb->execute();

		$object_ids = [];
		while ($data = $cursor->fetch()) {
			self::editShareFromParentEntry($data);
			if (self::isAccessibleResult($data)) {
				$object_ids[] = $data['file_source'];
			}
		}
		$cursor->closeCursor();

		return $object_ids;
	}


	/**
	 * Returns whether the given database result can be interpreted as
	 * a share with accessible file (not trashed, not deleted)
	 *
	 * @param $data
	 *F
	 *
	 * @return bool
	 */
	protected static function isAccessibleResult($data) {
		if ($data['fileid'] === null || $data['path'] === null) {
			return false;
		}

		return (!(explode('/', $data['path'], 2)[0] !== 'files'
				  && explode(':', $data['storage_string_id'], 2)[0] === 'home'));
	}


	/**
	 * @param $data
	 */
	protected static function editShareFromParentEntry(&$data) {
		if ($data['parent_id'] > 0) {
			$data['permissions'] = $data['parent_perms'];
			$data['file_target'] = $data['parent_target'];
		}
	}
}
