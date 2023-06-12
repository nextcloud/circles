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
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\FederatedUserService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

/**
 * @deprecated
 * re-implemented only to re-enable an old feature until we switch to a better integration.
 */
class DeprecatedController extends Controller {
	/** @var FederatedUserService  */
	private $federatedUserService;

	/** @var CircleService */
	private $circleService;

	public function __construct(
		string $appName,
		IRequest $request,
		FederatedUserService $federatedUserService,
		CircleService $circleService
	) {
		parent::__construct($appName, $request);
		$this->federatedUserService = $federatedUserService;
		$this->circleService = $circleService;
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $term
	 *
	 * @return DataResponse
	 */
	public function listing(string $term = ''): DataResponse {
		try {
			$this->federatedUserService->initCurrentUser();
			$probe = new CircleProbe();

			$filterCircle = new Circle();
			$filterCircle->setName($term)
						 ->setDisplayName($term);
			$probe->setFilterCircle($filterCircle);

			$data = $this->circleService->getCircles($probe);

			return new DataResponse(['data' => $data]);
		} catch (Exception $e) {
			return new DataResponse([]);
		}
	}
}
