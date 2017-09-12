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

use Exception;
use OCA\Circles\Exceptions\CircleNameFirstCharException;
use OCA\Circles\Exceptions\CircleNameTooShortException;
use OCA\Circles\Exceptions\CircleTypeDisabledException;
use OCA\Circles\Exceptions\FederatedCircleNotAllowedException;
use OCP\AppFramework\Http\DataResponse;

class CirclesController extends BaseController {

	/**
	 * Create a circle.
	 *
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param $type
	 * @param string $name
	 *
	 * @return DataResponse
	 */
	public function create($type, $name) {

		try {
			$this->verifyCreationName($name);
			$data = $this->circlesService->createCircle($type, $name);

			return $this->success(['name' => $name, 'circle' => $data, 'type' => $type]);
		} catch (Exception $e) {
			return $this->fail(['type' => $type, 'name' => $name, 'error' => $e->getMessage()]);
		}

	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param $type
	 * @param string $name
	 * @param int $level
	 *
	 * @return DataResponse
	 */
	public function listing($type, $name = '', $level = 0) {

		try {
			$data = $this->circlesService->listCircles($type, $name, $level);

			return $this->success(['type' => $type, 'data' => $data]);
		} catch (CircleTypeDisabledException $e) {

			return $this->fail(['type' => $type, 'error' => $e->getMessage()]);
		}
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $uniqueId
	 *
	 * @return DataResponse
	 */
	public function details($uniqueId) {
		try {
			$circle = $this->circlesService->detailsCircle($uniqueId);

			return $this->success(['circle_id' => $uniqueId, 'details' => $circle]);
		} catch (\Exception $e) {

			return $this->fail(['circle_id' => $uniqueId, 'error' => $e->getMessage()]);
		}

	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $uniqueId
	 * @param array $settings
	 *
	 * @return DataResponse
	 */
	public function settings($uniqueId, $settings) {
		try {
			$this->verifyCreationName($settings['circle_name']);
			$circle = $this->circlesService->settingsCircle($uniqueId, $settings);

			return $this->success(['circle_id' => $uniqueId, 'details' => $circle]);
		} catch (\Exception $e) {

			return $this->fail(['circle_id' => $uniqueId, 'error' => $e->getMessage()]);
		}

	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $uniqueId
	 *
	 * @return DataResponse
	 */
	public function join($uniqueId) {
		try {
			$data = $this->circlesService->joinCircle($uniqueId);

			return $this->success(['circle_id' => $uniqueId, 'member' => $data]);
		} catch (\Exception $e) {

			return $this->fail(['circle_id' => $uniqueId, 'error' => $e->getMessage()]);
		}
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $uniqueId
	 *
	 * @return DataResponse
	 */
	public function leave($uniqueId) {
		try {
			$data = $this->circlesService->leaveCircle($uniqueId);

			return $this->success(['circle_id' => $uniqueId, 'member' => $data]);
		} catch (\Exception $e) {

			return $this->fail(['circle_id' => $uniqueId, 'error' => $e->getMessage()]);
		}

	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $uniqueId
	 *
	 * @return DataResponse
	 */
	public function destroy($uniqueId) {
		try {
			$this->circlesService->removeCircle($uniqueId);

			return $this->success(['circle_id' => $uniqueId]);
		} catch (\Exception $e) {
			return $this->fail(['circle_id' => $uniqueId, 'error' => $e->getMessage()]);
		}
	}


	/**
	 * verifyCreationName();
	 *
	 * Verify the name at the creation of a circle:
	 * Name must contain at least 3 chars.
	 * First char must be alpha-numeric.
	 *
	 * @param $name
	 *
	 * @throws CircleNameFirstCharException
	 * @throws CircleNameTooShortException
	 */
	private function verifyCreationName($name) {
		if (strlen($name) < 3) {
			throw new CircleNameTooShortException(
				$this->l10n->t('The name of your circle must contain at least 3 characters')
			);
		}

		$chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
		if (strpos($chars, strtolower(substr($name, 0, 1))) === false) {
			throw new CircleNameFirstCharException(
				$this->l10n->t(
					"The name of your circle must start with an alpha-numerical character"
				)
			);
		}
	}

}

