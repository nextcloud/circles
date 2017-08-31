<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
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
use OCA\Circles\Service\CirclesService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedLinkCreationService;
use OCA\Circles\Service\FederatedLinkService;
use OCA\Circles\Service\MiscService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;
use OCP\IRequest;

class LinksController extends Controller {

	/** @var IL10N */
	protected $l10n;

	/** @var ConfigService */
	protected $configService;

	/** @var CirclesService */
	protected $circlesService;

	/** @var FederatedLinkService */
	protected $federatedLinkService;

	/** @var FederatedLinkCreationService */
	protected $federatedLinkCreationService;

	/** @var MiscService */
	protected $miscService;


	/**
	 * BaseController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param string $UserId
	 * @param IL10N $l10n
	 * @param ConfigService $configService
	 * @param CirclesService $circlesService
	 * @param FederatedLinkService $federatedLinkService
	 * @param FederatedLinkCreationService $federatedLinkCreationService
	 * @param MiscService $miscService
	 */
	public function __construct(
		$appName,
		IRequest $request,
		$UserId,
		IL10N $l10n,
		ConfigService $configService,
		CirclesService $circlesService,
		FederatedLinkService $federatedLinkService,
		FederatedLinkCreationService $federatedLinkCreationService,
		MiscService $miscService
	) {
		parent::__construct($appName, $request);

		$this->l10n = $l10n;
		$this->configService = $configService;
		$this->circlesService = $circlesService;
		$this->federatedLinkService = $federatedLinkService;
		$this->federatedLinkCreationService = $federatedLinkCreationService;
		$this->miscService = $miscService;
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
	public function createLink($uniqueId, $remote) {
		try {
			$link = $this->federatedLinkCreationService->createLinkWithRemoteCircle($uniqueId, $remote);
			$links = $this->circlesService->detailsCircle($uniqueId)
										  ->getLinks();

			return $this->miscService->success(
				['circle_id' => $uniqueId, 'remote' => $remote, 'link' => $link, 'links' => $links]
			);
		} catch (\Exception $e) {
			return $this->miscService->fail(
				['circle_id' => $uniqueId, 'remote' => $remote, 'error' => $e->getMessage()]
			);
		}
	}


	/**
	 * updateLinkStatus();
	 *
	 * Modify a link status. Used to confirm/dismiss a request or putting down a link.
	 * The function will modify local status and broadcast the status to the remote.
	 *
	 * Note: should be moved to a LinkController
	 *
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $linkId
	 * @param int $status
	 *
	 * @return DataResponse
	 * @throws FederatedCircleNotAllowedException
	 */
	public function updateLinkStatus($linkId, $status) {

		if (!$this->configService->isFederatedCirclesAllowed()) {
			throw new FederatedCircleNotAllowedException(
				$this->l10n->t("Federated circles are not allowed on this Nextcloud")
			);
		}

		try {
			$links = $this->federatedLinkService->linkStatus($linkId, (int)$status);

			return $this->miscService->success(['link_id' => $linkId, 'links' => $links]);
		} catch (\Exception $e) {
			return $this->miscService->fail(['link_id' => $linkId, 'error' => $e->getMessage()]);
		}
	}


}

