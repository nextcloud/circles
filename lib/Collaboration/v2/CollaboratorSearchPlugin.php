<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Collaboration\v2;

use Exception;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Model\Probes\DataProbe;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCP\Collaboration\Collaborators\ISearchPlugin;
use OCP\Collaboration\Collaborators\ISearchResult;
use OCP\Collaboration\Collaborators\SearchResultType;
use OCP\IRequest;
use OCP\Share\IShare;

/**
 * Class CollaboratorSearchPlugin
 *
 * @package OCA\Circles\Collaboration\v2
 */
class CollaboratorSearchPlugin implements ISearchPlugin {
	use TNCLogger;


	/** @var IRequest */
	private $request;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var CircleService */
	private $circleService;


	/**
	 * CollaboratorSearchPlugin constructor.
	 *
	 * @param IRequest $request
	 * @param FederatedUserService $federatedUserService
	 * @param CircleService $circleService
	 */
	public function __construct(
		IRequest $request,
		FederatedUserService $federatedUserService,
		CircleService $circleService,
	) {
		$this->request = $request;
		$this->federatedUserService = $federatedUserService;
		$this->circleService = $circleService;

		$this->setup('app', Application::APP_ID);
	}


	/**
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @param ISearchResult $searchResult
	 *
	 * @return bool
	 */
	public function search($search, $limit, $offset, ISearchResult $searchResult): bool {
		$wide = $exact = [];
		$fromFrontEnd = true;

		// TODO: remove this, using a cleaner way to detect the source of the request
		$params = $this->request->getParams();
		$shareType = $this->getArray('shareType', $params);
		if (empty($shareType) || in_array(IShare::TYPE_ROOM, $shareType)) {
			$fromFrontEnd = false;
		}

		$filterCircle = new Circle();
		$filterCircle->setName($search)
			->setDisplayName($search);

		try {
			$this->federatedUserService->initCurrentUser();

			$probe = new CircleProbe();
			$probe->filterBackendCircles()
				->filterSystemCircles()
				->setItemsLimit($limit)
				->setItemsOffset($offset)
				->setFilterCircle($filterCircle);

			// If from the OCS API, we use getCircles(), to get more complex result at the price of huge resource,
			// if not (ie. share popup) we only need probeCircles()
			if ($fromFrontEnd) {
				$probe->mustBeMember(false)
					->filterConfig(Circle::CFG_ROOT, true);

				$circles = $this->circleService->getCircles($probe);
			} else {
				$dataProbe = new DataProbe();
				$dataProbe->add(DataProbe::OWNER);

				$circles = $this->circleService->probeCircles($probe, $dataProbe);
			}
		} catch (Exception $e) {
			return false;
		}

		foreach ($circles as $circle) {
			$entry = $this->addResultEntry($circle);
			if (strtolower($circle->getName()) === strtolower($search)) {
				$exact[] = $entry;
			} else {
				$wide[] = $entry;
			}
		}

		$type = new SearchResultType('circles');
		$searchResult->addResultSet($type, $wide, $exact);

		return false;
	}


	/**
	 * @param Circle $circle
	 *
	 * @return array
	 */
	private function addResultEntry(Circle $circle): array {
		return [
			'label' => $circle->getDisplayName(),
			'shareWithDescription' => $this->circleService->getDefinition($circle),
			'value' => [
				'shareType' => IShare::TYPE_CIRCLE,
				'shareWith' => $circle->getSingleId(),
				'circle' => $circle
			],
		];
	}
}
