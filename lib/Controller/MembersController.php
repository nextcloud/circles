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

use OCA\Circles\Model\SearchResult;
use OCA\Circles\Service\MiscService;
use OCP\AppFramework\Http\DataResponse;

class MembersController extends BaseController {


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $uniqueId
	 * @param $ident
	 * @param $type
	 * @param string $instance
	 *
	 * @return DataResponse
	 */
	public function addMember($uniqueId, $ident, $type, $instance) {

		try {
			$this->mustHaveFrontEndEnabled();

			$data = $this->membersService->addMember($uniqueId, $ident, (int)$type, $instance);
		} catch (\Exception $e) {
			return $this->fail(
				[
					'circle_id' => $uniqueId,
					'user_id'   => $ident,
					'user_type' => (int)$type,
					'display'   => $ident,
					'error'     => $e->getMessage()
				]
			);
		}

		return $this->success(
			[
				'circle_id' => $uniqueId,
				'user_id'   => $ident,
				'user_type' => (int)$type,
				'display'   => $ident,
				'members'   => $data
			]
		);
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param $memberId
	 *
	 * @return DataResponse
	 */
	public function addMemberById(string $memberId) {
		try {
			$this->mustHaveFrontEndEnabled();

			$member = $this->membersService->getMemberById($memberId);
			$data = $this->membersService->addMember(
				$member->getCircleId(), $member->getUserId(), $member->getType(), $member->getInstance()
			);
		} catch (\Exception $e) {
			return $this->fail(
				[
					'member_id' => $memberId,
					'error'     => $e->getMessage()
				]
			);
		}

		return $this->success(
			[
				'member_id' => $memberId,
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
	 * @param string $instance
	 * @param int $level
	 *
	 * @return DataResponse
	 */
	public function levelMember($uniqueId, $member, $type, $instance, $level) {

		try {
			$this->mustHaveFrontEndEnabled();

			$data = $this->membersService->levelMember($uniqueId, $member, (int)$type, $instance, $level);
		} catch (\Exception $e) {
			return
				$this->fail(
					[
						'circle_id' => $uniqueId,
						'user_id'   => $member,
						'instance'  => $instance,
						'user_type' => (int)$type,
						'display'   => $member,
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
				'display'   => $member,
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
	 * @param $instance
	 *
	 * @return DataResponse
	 */
	public function removeMember($uniqueId, $member, $type, $instance) {

		try {
			$this->mustHaveFrontEndEnabled();

			$data = $this->membersService->removeMember($uniqueId, $member, (int)$type, $instance);
		} catch (\Exception $e) {
			return
				$this->fail(
					[
						'circle_id' => $uniqueId,
						'user_id'   => $member,
						'user_type' => (int)$type,
						'display'   => $member,
						'error'     => $e->getMessage()
					]
				);
		}

		return $this->success(
			[
				'circle_id' => $uniqueId,
				'user_id'   => $member,
				'user_type' => (int)$type,
				'display'   => $member,
				'members'   => $data,
			]
		);
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $memberId
	 *
	 * @return DataResponse
	 */
	public function removeMemberById(string $memberId) {
		try {
			$this->mustHaveFrontEndEnabled();

			$member = $this->membersService->getMemberById($memberId);
			$data = $this->membersService->removeMember(
				$member->getCircleId(), $member->getUserId(), $member->getType(), $member->getInstance()
			);
		} catch (\Exception $e) {
			return
				$this->fail(
					[
						'member_id' => $memberId,
						'error'     => $e->getMessage()
					]
				);
		}

		return $this->success(
			[
				'member_id' => $memberId,
				'members'   => $data,
			]
		);
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $search
	 * @param int $order
	 *
	 * @return DataResponse
	 */
	public function searchGlobal(string $search, int $order) {
		try {
			$this->mustHaveFrontEndEnabled();

			$result = $this->searchService->searchGlobal($search);
		} catch (\Exception $e) {
			return
				$this->fail(
					[
						'search' => $search,
						'error'  => $e->getMessage()
					]
				);
		}

		if ($this->configService->getCoreValue('shareapi_allow_share_dialog_user_enumeration') === 'no') {
			$result = array_filter(
				$result,
				function($data, $k) use ($search) {
					/** @var SearchResult $data */
					return $data->getIdent() === $search;
				}, ARRAY_FILTER_USE_BOTH
			);
		}

		return $this->success(['search' => $search, 'result' => $result, 'order' => $order]);
	}

}

