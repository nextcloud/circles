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

use OC\AppFramework\Http;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedLink;
use OCA\Circles\Service\FederatedService;
use OCA\Circles\Service\CirclesService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\MembersService;
use OCA\Circles\Service\MiscService;
use OCA\Circles\Service\SharesService;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;
use OCP\IRequest;

class FederatedController extends BaseController {

	/** @var string */
	protected $userId;

	/** @var IL10N */
	protected $l10n;

	/** @var ConfigService */
	protected $configService;

	/** @var CirclesService */
	protected $circlesService;

	/** @var MembersService */
	protected $membersService;

	/** @var SharesService */
	protected $sharesService;

	/** @var FederatedService */
	protected $federatedService;

	/** @var MiscService */
	protected $miscService;


	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $circleName
	 *
	 * @return DataResponse
	 */
	public function requestedLink($sourceId, $sourceName, $circleName) {

		if (!$this->configService->isFederatedAllowed()) {
			return new DataResponse(
				[
					'status' => FederatedService::STATUS_ERROR,
					'reason' => 'federated_not_allowed'
				],
				Http::STATUS_OK
			);
		}

		$circle = $this->circlesService->infoCircleByName($circleName);
		if ($circle === null) {
			return new DataResponse(
				[
					'status' => FederatedService::STATUS_ERROR,
					'reason' => 'circle_does_not_exist'
				],
				Http::STATUS_OK
			);
		}

		$link = new FederatedLink();
		$link->setRemoteCircleId($sourceId)
			 ->setRemoteCircleName($sourceName);
		
		if (!$this->federatedService->initiateLink($circle, $link)) {
			return new DataResponse(
				[
					'status' => FederatedService::STATUS_LINK_UP,
					'token'  => $link->getToken()
				],
				Http::STATUS_OK
			);
		} else {
			return new DataResponse(
				[
					'status' => FederatedService::STATUS_REQUEST_SENT,
					'token'  => $link->getToken()
				],
				Http::STATUS_OK
			);
		}
	}

}