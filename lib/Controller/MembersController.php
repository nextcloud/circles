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

use OCA\Circles\Model\Member;
use OCP\AppFramework\Http\DataResponse;

class MembersController extends BaseController {


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $uniqueId
	 * @param string $name
	 *
	 * @return DataResponse
	 */
	public function addLocalMember($uniqueId, $name) {

		try {
			$data = $this->membersService->addLocalMember($uniqueId, $name);
		} catch (\Exception $e) {
			return $this->fail(
				[
					'circle_id' => $uniqueId,
					'user_id'   => $name,
					'name'      => $this->miscService->getDisplayName($name, true),
					'error'     => $e->getMessage()
				]
			);
		}

		return $this->success(
			[
				'circle_id' => $uniqueId,
				'user_id'   => $name,
				'name'      => $this->miscService->getDisplayName($name, true),
				'members'   => $data
			]
		);
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $uniqueId
	 * @param string $email
	 *
	 * @return DataResponse
	 */
	public function addEmailAddress($uniqueId, $email) {

		try {
			$data = $this->membersService->addEmailAddress($uniqueId, $email);
		} catch (\Exception $e) {
			return $this->fail(
				[
					'circle_id' => $uniqueId,
					'user_id'   => $email,
					'name'      => $this->miscService->getDisplayName($email, true),
					'error'     => $e->getMessage()
				]
			);
		}

		return $this->success(
			[
				'circle_id' => $uniqueId,
				'user_id'   => $email,
				'name'      => $this->miscService->getDisplayName($email, true),
				'members'   => $data
			]
		);
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param $uniqueId
	 * @param string $name
	 *
	 * @return DataResponse
	 */
	public function importFromGroup($uniqueId, $name) {

		try {
			$data = $this->membersService->importMembersFromGroup($uniqueId, $name);
		} catch (\Exception $e) {
			return $this->fail(
				[
					'circle_id' => $uniqueId,
					'user_id'   => $name,
					'name'      => $this->miscService->getDisplayName($name, true),
					'error'     => $e->getMessage()
				]
			);
		}

		return $this->success(
			[
				'circle_id' => $uniqueId,
				'user_id'   => $name,
				'name'      => $this->miscService->getDisplayName($name, true),
				'members'   => $data
			]
		);
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $uniqueId
	 * @param string $member
	 * @param int $type
	 * @param int $level
	 *
	 * @return DataResponse
	 */
	public function levelMember($uniqueId, $member, $type, $level) {

		try {
			$data = $this->membersService->levelMember($uniqueId, $member, (int)$type, $level);
		} catch (\Exception $e) {
			return
				$this->fail(
					[
						'circle_id' => $uniqueId,
						'user_id'   => $member,
						'user_type' => (int)$type,
						'name'      => $this->miscService->getDisplayName($member, true),
						'level'     => $level,
						'error'     => $e->getMessage()
					]
				);
		}

		return $this->success(
			[
				'circle_id' => $uniqueId,
				'user_id'   => $member,
				'user_type' => (int)$type,
				'name'      => $this->miscService->getDisplayName($member, true),
				'level'     => $level,
				'members'   => $data,
			]
		);
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $uniqueId
	 * @param string $member
	 * @param int $type
	 *
	 * @return DataResponse
	 */
	public function removeMember($uniqueId, $member, $type) {

		try {
			$data = $this->membersService->removeMember($uniqueId, $member, (int)$type);
		} catch (\Exception $e) {
			return
				$this->fail(
					[
						'circle_id' => $uniqueId,
						'user_id'   => $member,
						'user_type' => (int)$type,
						'name'      => $this->miscService->getDisplayName($member, true),
						'error'     => $e->getMessage()
					]
				);
		}

		return $this->success(
			[
				'circle_id' => $uniqueId,
				'user_id'   => $member,
				'user_type' => (int)$type,
				'name'      => $this->miscService->getDisplayName($member, true),
				'members'   => $data,
			]
		);
	}


}

