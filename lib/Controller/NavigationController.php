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

use daita\MySmallPhpTools\Exceptions\JsonNotRequestedException;
use daita\MySmallPhpTools\Exceptions\SignatoryException;
use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21LocalSignatory;
use Exception;
use OC\AppFramework\Middleware\Security\Exceptions\NotLoggedInException;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Model\Circle;
use OCA\Circles\Service\ConfigService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;

class NavigationController extends BaseController {


	use TNC21LocalSignatory;


	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 * @PublicPage
	 *
	 * @return Response
	 * @throws Exception
	 */
	public function navigate(): Response {
		try {
			return $this->appService();
		} catch (JsonNotRequestedException $e) {
		}

		$this->mustHaveFrontEndEnabled();

		$data = [
			'allowed_circles' => array(
				Circle::CIRCLES_PERSONAL => $this->configService->isCircleAllowed(
					Circle::CIRCLES_PERSONAL
				),
				Circle::CIRCLES_SECRET   => $this->configService->isCircleAllowed(
					Circle::CIRCLES_SECRET
				),
				Circle::CIRCLES_CLOSED   => $this->configService->isCircleAllowed(
					Circle::CIRCLES_CLOSED
				),
				Circle::CIRCLES_PUBLIC   => $this->configService->isCircleAllowed(
					Circle::CIRCLES_PUBLIC
				),
			)
		];

		return new TemplateResponse(
			Application::APP_ID, 'navigate', $data
		);
	}


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


	/**
	 * @return Response
	 * @throws NotLoggedInException
	 * @throws JsonNotRequestedException
	 * @throws SignatoryException
	 */
	public function appService(): Response {
		$this->setup('app', 'circles');
		$this->publicPageJsonLimited();

		$confirm = $this->request->getParam('auth', '');
		return new DataResponse($this->remoteService->getAppSignatory(false, $confirm));
	}

}


