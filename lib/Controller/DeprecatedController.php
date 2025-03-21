<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var CircleService */
	private $circleService;

	public function __construct(
		string $appName,
		IRequest $request,
		FederatedUserService $federatedUserService,
		CircleService $circleService,
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
			$probe->filterSystemCircles();

			$data = $this->circleService->getCircles($probe);

			return new DataResponse(['data' => $data]);
		} catch (Exception $e) {
			return new DataResponse([]);
		}
	}
}
