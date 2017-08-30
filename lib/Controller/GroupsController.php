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
	 * @param string $uniqueId
	 * @param string $name
	 *
	 * @return DataResponse
	 * @throws LinkedGroupNotAllowedException
	 */
	public function add($uniqueId, $name) {
		if (!$this->configService->isLinkedGroupsAllowed()) {
			throw new LinkedGroupNotAllowedException(
				$this->l10n->t("Linked Groups are not allowed on this Nextcloud")
			);
		}

		try {
			$data = $this->groupsService->linkGroup($uniqueId, $name);
		} catch (\Exception $e) {
			return $this->fail(
				[
					'circle_id' => $uniqueId,
					'name'      => $name,
					'error'     => $e->getMessage()
				]
			);
		}

		return $this->success(
			[
				'circle_id' => $uniqueId,
				'name'      => $name,
				'groups'    => $data
			]
		);
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $uniqueId
	 * @param string $group
	 * @param int $level
	 *
	 * @return DataResponse
	 */
	public function level($uniqueId, $group, $level) {

		try {
			$data = $this->groupsService->levelGroup($uniqueId, $group, $level);
		} catch (\Exception $e) {
			return
				$this->fail(
					[
						'circle_id' => $uniqueId,
						'name'      => $group,
						'level'     => $level,
						'error'     => $e->getMessage()
					]
				);
		}

		return $this->success(
			[
				'circle_id' => $uniqueId,
				'name'      => $group,
				'level'     => $level,
				'groups'    => $data,
			]
		);
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $uniqueId
	 * @param string $group
	 *
	 * @return DataResponse
	 * @throws LinkedGroupNotAllowedException
	 */
	public function remove($uniqueId, $group) {
		if (!$this->configService->isLinkedGroupsAllowed()) {
			throw new LinkedGroupNotAllowedException(
				$this->l10n->t('Linked Groups are not allowed on this Nextcloud')
			);
		}

		$args = ['circle_id' => $uniqueId, 'name' => $group];
		try {
			$data = $this->groupsService->unlinkGroup($uniqueId, $group);
		} catch (\Exception $e) {
			return $this->fail(array_merge($args, ['error' => $e->getMessage()]));
		}

		return $this->success(array_merge($args, ['groups' => $data]));
	}


}

