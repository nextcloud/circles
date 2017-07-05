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

use OCA\Circles\Exceptions\LinkedGroupNotAllowedException;
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
	 * @throws LinkedGroupNotAllowedException
	 */
	public function add($id, $name) {
		if (!$this->configService->isLinkedGroupsAllowed()) {
			throw new LinkedGroupNotAllowedException(
				$this->l10n->t("Linked Groups are not allowed on this Nextcloud")
			);
		}

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
	 * @param $id
	 * @param $group
	 * @param $level
	 *
	 * @return DataResponse
	 */
	public function level($id, $group, $level) {

		try {
			$data = $this->groupsService->levelGroup($id, $group, $level);
		} catch (\Exception $e) {
			return
				$this->fail(
					[
						'circle_id' => $id,
						'name'      => $group,
						'level'     => $level,
						'error'     => $e->getMessage()
					]
				);
		}

		return $this->success(
			[
				'circle_id' => $id,
				'name'      => $group,
				'level'     => $level,
				'groups'   => $data,
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
	 * @throws LinkedGroupNotAllowedException
	 */
	public function remove($id, $groupId) {
		if (!$this->configService->isLinkedGroupsAllowed()) {
			throw new LinkedGroupNotAllowedException(
				$this->l10n->t("Linked Groups are not allowed on this Nextcloud")
			);
		}

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
				'groups'    => $data,
			]
		);
	}


}

