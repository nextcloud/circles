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
	 *
	 * @return DataResponse
	 */
	public function addMember($uniqueId, $ident, $type) {

		try {
			$data = $this->membersService->addMember($uniqueId, $ident, (int)$type);
		} catch (\Exception $e) {
			return $this->fail(
				[
					'circle_id' => $uniqueId,
					'user_id'   => $ident,
					'user_type' => (int)$type,
					'display'   => MiscService::getDisplay($ident, (int)$type),
					'error'     => $e->getMessage()
				]
			);
		}

		return $this->success(
			[
				'circle_id' => $uniqueId,
				'user_id'   => $ident,
				'user_type' => (int)$type,
				'display'   => MiscService::getDisplay($ident, (int)$type),
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
						'display'   => MiscService::getDisplay($member, (int)$type),
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
				'display'   => MiscService::getDisplay($member, (int)$type),
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
						'display'   => MiscService::getDisplay($member, (int)$type),
						'error'     => $e->getMessage()
					]
				);
		}

		return $this->success(
			[
				'circle_id' => $uniqueId,
				'user_id'   => $member,
				'user_type' => (int)$type,
				'display'   => MiscService::getDisplay($member, (int)$type),
				'members'   => $data,
			]
		);
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param string $search
	 *
	 * @return DataResponse
	 */
	public function searchGlobal($search) {

		try {
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

		if ($this->configService->getCoreValue('shareapi_allow_share_dialog_user_enumeration') !== 'yes') {
			$result = array_filter(
				$result,
				function($data, $k) use ($search) {
					/** @var SearchResult $data */
					return $data->getIdent() === $search;
				}, ARRAY_FILTER_USE_BOTH
			);
		}

		return $this->success(['search' => $search, 'result' => $result]);
	}

}

