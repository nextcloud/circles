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

use OCA\Circles\Exceptions\CircleTypeDisabledException;
use OCA\Circles\Exceptions\FederatedCircleNotAllowedException;
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

		if (strlen($name) < 3) {
			$error = $this->l10n->t("The name of your circle must contain at least 3 characters");
		} elseif (substr($name, 0, 1) === '_') {
			$error = $this->l10n->t("The name of your circle cannot start with this character");
		} else {

			try {
				$data = $this->circlesService->createCircle($type, $name);

				return $this->success(['name' => $name, 'circle' => $data, 'type' => $type]);
			} catch (\Exception $e) {
				$error = $e->getMessage();
			}
		}

		return $this->fail(['type' => $type, 'name' => $name, 'error' => $error]);
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
	 * link()
	 *
	 * Called from the UI to create a initiate the process of linking 2 [remote] circles.
	 * $remote format: <circle_name>@<remote_host>
	 *
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $uniqueId
	 * @param string $remote
	 *
	 * @return DataResponse
	 */
	public function link($uniqueId, $remote) {
		try {
			$link = $this->federatedService->linkCircle($uniqueId, $remote);
			$links = $this->circlesService->detailsCircle($uniqueId)
										  ->getLinks();

			return $this->success(
				['circle_id' => $uniqueId, 'remote' => $remote, 'link' => $link, 'links' => $links]
			);
		} catch (\Exception $e) {
			return $this->fail(
				['circle_id' => $uniqueId, 'remote' => $remote, 'error' => $e->getMessage()]
			);
		}
	}


	/**
	 * linkStatus();
	 *
	 * Modify a link status. Used to confirm/dismiss a request or putting down a link.
	 * The function will modify local status and broadcast the status to the remote.
	 *
	 * Note: should be moved to a LinkController
	 *
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param int $linkId
	 * @param int $status
	 *
	 * @return DataResponse
	 * @throws FederatedCircleNotAllowedException
	 */
	public function linkStatus($linkId, $status) {

		if (!$this->configService->isFederatedCirclesAllowed()) {
			throw new FederatedCircleNotAllowedException(
				$this->l10n->t("Federated circles are not allowed on this Nextcloud")
			);
		}

		try {
			$links = $this->federatedService->linkStatus($linkId, $status);

			return $this->success(['link_id' => $linkId, 'links' => $links]);
		} catch (\Exception $e) {
			return $this->fail(
				['link_id' => $linkId, 'error' => $e->getMessage()]
			);
		}
	}


}

