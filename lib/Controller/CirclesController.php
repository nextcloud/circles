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
use OCA\Circles\Model\Member;
use OCP\AppFramework\Http\DataResponse;

class CirclesController extends BaseController {

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

			return $this->success(
				[
					'name'   => $name,
					'circle' => $data,
					'type'   => $type
				]
			);
		} catch (\Exception $e) {
			return $this->fail(
				[
					'type'  => $type,
					'name'  => $name,
					'error' => $e->getMessage()
				]
			);

		}
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

			return $this->success(
				[
					'type' => $type,
					'data' => $data
				]
			);
		} catch (CircleTypeDisabledException $e) {
			return
				$this->fail(
					[
						'type'  => $type,
						'error' => $e->getMessage()
					]
				);
		}

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

			return $this->success(
				[
					'circle_id' => $id,
					'details'   => $data
				]
			);
		} catch (\Exception $e) {
			return
				$this->fail(
					[
						'circle_id' => $id,
						'error'     => $e->getMessage()
					]
				);
		}

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

			return $this->success(
				[
					'circle_id' => $id,
					'member'    => $data
				]
			);
		} catch (\Exception $e) {
			return $this->fail(
				[
					'circle_id' => $id,
					'error'     => $e->getMessage()
				]
			);
		}
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

			return $this->success(
				[
					'circle_id' => $id,
					'member'    => $data
				]
			);
		} catch (\Exception $e) {
			return $this->fail(
				[
					'circle_id' => $id,
					'error'     => $e->getMessage()
				]
			);
		}

	}


}

