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

use OCA\Circles\Exceptions\CircleDoesNotExistException;
use OCA\Circles\Exceptions\CircleTypeDisabledException;
use \OCA\Circles\Service\MiscService;
use \OCA\Circles\Service\ConfigService;
use \OCA\Circles\Service\CirclesService;
use \OCA\Circles\Model\Member;


use OCP\AppFramework\Http\DataResponse;

class CirclesController extends BaseController {

//	/** @var string */
//	private $userId;
//	/** @var IL10N */
//	private $l10n;
//	/** @var ConfigService */
//	private $configService;
//
//	/** @var MiscService */
//	private $miscService;
//
//	public function __construct(
//		$appName,
//		IRequest $request,
//		$userId,
//		IL10N $l10n,
//		ConfigService $configService,
//		CirclesService $circlesService,
//		MiscService $miscService
//	) {
//		parent::__construct($appName, $request);
//
//		$this->userId = $userId;
//		$this->l10n = $l10n;
//		$this->configService = $configService;
//		$this->circlesService = $circlesService;
//		$this->miscService = $miscService;
//	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param $type
	 * @param string $name
	 *
	 * @return DataResponse
	 */
	public function create($type, $name) {

		$data = null;
		if (substr($name, 0, 1) === '_') {
			return $this->fail(
				[
					'type'  => $type,
					'name'  => $name,
					'error' => "The name of your circle cannot start with this character"
				]

			);
		}


		try {
			$data = $this->circlesService->createCircle($type, $name);
		} catch (\Exception $e) {
			return $this->fail(
				[
					'type'  => $type,
					'name'  => $name,
					'error' => $e->getMessage()
				]
			);

		}

		return $this->success(
			[
				'name'   => $name,
				'circle' => $data,
				'type'   => $type
			]
		);
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param $type
	 * @param string $name
	 *
	 * @return DataResponse
	 */
	public function list($type, $name = '') {

		try {
			$data = $this->circlesService->listCircles($type, $name, Member::LEVEL_NONE);
		} catch (CircleTypeDisabledException $e) {
			return
				$this->fail(
					[
						'type'  => $type,
						'error' => $e->getMessage()
					]
				);
		}

		return $this->success(
			[
				'type' => $type,
				'data' => $data
			]
		);
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param $id
	 *
	 * @return DataResponse
	 * @internal param string $name
	 *
	 */
	public function details($id) {

		try {
			$data = $this->circlesService->detailsCircle($id);
		} catch (CircleDoesNotExistException $e) {
			return
				$this->fail(
					[
						'circle_id' => $id,
						'error'     => $e->getMessage()
					]
				);
		}

		return $this->success(
			[
				'circle_id' => $id,
				'details'   => $data
			]
		);
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param $id
	 *
	 * @return DataResponse
	 * @internal param string $name
	 *
	 */
	public function join($id) {

		try {
			$data = $this->circlesService->joinCircle($id);
		} catch (\Exception $e) {
			return $this->fail(
				[
					'circle_id' => $id,
					'error'     => $e->getMessage()
				]
			);
		}

		return $this->success(
			[
				'circle_id' => $id,
				'member'    => $data
			]
		);
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param $id
	 *
	 * @return DataResponse
	 * @internal param string $name
	 *
	 */
	public function leave($id) {
		try {
			$data = $this->circlesService->leaveCircle($id);
		} catch (\Exception $e) {
			return $this->fail(
				[
					'circle_id' => $id,
					'error'     => $e->getMessage()
				]
			);
		}

		return $this->success(
			[
				'circle_id' => $id,
				'member'    => $data
			]
		);
	}


}

