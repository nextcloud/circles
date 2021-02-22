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

use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21LocalSignatory;
use OCA\Circles\Service\ConfigService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

class NavigationController extends BaseController {


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @return DataResponse
	 */
	public function settings() {
		$data = [
			'user_id'                           => $this->userId,
			'allowed_circles'                   => $this->configService->getAppValue(
				ConfigService::CIRCLES_ALLOW_CIRCLES
			),
			'members_list'                      => $this->configService->getAppValue(
				ConfigService::CIRCLES_MEMBERS_LIMIT
			),
			'allowed_linked_groups'             => $this->configService->getAppValue(
				ConfigService::CIRCLES_ALLOW_LINKED_GROUPS
			),
			'allowed_federated_circles'         => $this->configService->getAppValue(
				ConfigService::CIRCLES_ALLOW_FEDERATED_CIRCLES
			),
			'skip_invitation_to_closed_circles' => $this->configService->getAppValue(
				ConfigService::CIRCLES_SKIP_INVITATION_STEP
			),
			'status'                            => 1
		];

		return new DataResponse(
			$data,
			Http::STATUS_OK
		);
	}

}


