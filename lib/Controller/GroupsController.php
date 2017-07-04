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

namespace OCA\Circles\Controller;

use OCP\AppFramework\Http\DataResponse;

class GroupsController extends BaseController {


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param int $id
	 * @param string $name
	 *
	 * @return DataResponse
	 */
	public function add($id, $name) {
		try {
			$data = $this->groupsService->addGroup($id, $name);
		} catch (\Exception $e) {
			return $this->fail(
				[
					'circle_id' => $id,
					'name'      => $name,
					'error'     => $e->getMessage()
				]
			);
		}

		return $this->success(
			[
				'circle_id' => $id,
				'name'      => $name,
				'groups'    => $data
			]
		);
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param int $id
	 * @param string $groupId
	 *
	 * @return DataResponse
	 */
	public function remove($id, $groupId) {

		try {
			$data = $this->groupsService->removeGroup($id, $groupId);
		} catch (\Exception $e) {
			return
				$this->fail(
					[
						'circle_id' => $id,
						'name'      => $groupId,
						'error'     => $e->getMessage()
					]
				);
		}

		return $this->success(
			[
				'circle_id' => $id,
				'name'      => $groupId,
				'groups'   => $data,
			]
		);
	}


}

