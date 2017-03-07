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

use \OCA\Circles\Service\MiscService;
use \OCA\Circles\Service\ConfigService;
use \OCA\Circles\Service\MembersService;

use OC\AppFramework\Http;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;
use OCP\IRequest;

class MembersController extends Controller {

	/** @var string */
	private $userId;
	/** @var IL10N */
	private $l10n;
	/** @var ConfigService */
	private $configService;
	/** @var MembersService */
	private $membersService;
	/** @var MiscService */
	private $miscService;

	public function __construct(
		$appName,
		IRequest $request,
		$userId,
		IL10N $l10n,
		ConfigService $configService,
		MembersService $membersService,
		MiscService $miscService
	) {
		parent::__construct($appName, $request);

		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->configService = $configService;
		$this->membersService = $membersService;
		$this->miscService = $miscService;
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $name
	 *
	 * @return DataResponse
	 */
//	public function search($name) {
//
//		$result = $this->membersService->searchMembers($name);
//
//		if ($result['status'] === 1) {
//			$status = Http::STATUS_CREATED;
//		} else {
//			$status = Http::STATUS_NON_AUTHORATIVE_INFORMATION;
//		}
//
//		return new DataResponse(
//			$result,
//			$status
//		);
//
//	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param $id
	 * @param string $name
	 *
	 * @return DataResponse
	 */
	public function add($id, $name) {

		try {
			$data = $this->membersService->addMember($id, $name);
		} catch (\Exception $e) {
			return
				new DataResponse(
					[
						'circle_id' => $id,
						'name'      => $name,
						'status'    => 0,
						'error'     => $e->getMessage()
					],
					Http::STATUS_NON_AUTHORATIVE_INFORMATION
				);
		}

		return new DataResponse(
			[
				'circle_id' => $id,
				'name'      => $name,
				'members'   => $data,
				'status'    => 1
			], Http::STATUS_CREATED
		);
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param $id
	 * @param $member
	 *
	 * @return DataResponse
	 * @internal param string $name
	 *
	 */
	public function remove($id, $member) {

		try {
			$data = $this->membersService->removeMember($id, $member);
		} catch (\Exception $e) {
			return
				new DataResponse(
					[
						'circle_id' => $id,
						'name'      => $member,
						'status'    => 0,
						'error'     => $e->getMessage()
					],
					Http::STATUS_NON_AUTHORATIVE_INFORMATION
				);
		}

		return new DataResponse(
			[
				'circle_id' => $id,
				'name'      => $member,
				'members'   => $data,
				'status'    => 1
			], Http::STATUS_CREATED
		);
	}


}

