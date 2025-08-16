<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Controller;

use OCA\Circles\Exceptions\FrontendException;
use OCA\Circles\FileSharingTeamResourceProvider;
use OCA\Circles\Model\ModelManager;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Service\CircleService;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\FederatedUserService;
use OCA\Circles\Service\MemberService;
use OCA\Circles\Service\TeamResourceService;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class TeamsDashboardController extends OCSController {

	public function __construct(
		string $appName,
		IRequest $request,
		private CircleService $circleService,
		private MemberService $memberService,
		private FederatedUserService $federatedUserService,
		private ConfigService $configService,
		private IUserSession $userSession,
		private FileSharingTeamResourceProvider $resourceProvider,
		private IURLGenerator $urlGenerator,
		private ModelManager $modelManager,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function getCompleteTeamsData(int $limit = 3, int $offset = 0): DataResponse {
		try {
			$this->setCurrentFederatedUser();

			$probe = new CircleProbe();
			$probe->filterHiddenCircles()
				->filterBackendCircles()
				->setItemsLimit($limit)
				->setItemsOffset($offset);

			$circles = $this->circleService->probeCircles($probe);
			
			$teams = [];
			foreach ($circles as $circle) {
				$members = [];
				try {
					$circleMembers = $this->memberService->getMembers($circle->getSingleId(), false);
					
					// Limit to 5 members for the dashboard widget
					$limitedMembers = array_slice($circleMembers, 0, 5);
					
					foreach ($limitedMembers as $member) {
						$members[] = [
							'singleId' => $member->getSingleId(),
							'userId' => $member->getUserId(),
							'displayName' => $member->getDisplayName(),
							'type' => $member->getUserType(),
						];
					}
				} catch (\Exception $e) {
					// Skip members if we can't fetch them
				}

				$resources = [];
				try {
					// Create TeamResourceService instance and fetch real resources
					$resourceService = new TeamResourceService(
						$this->resourceProvider,
						$this->urlGenerator,
						$this->logger
					);
					$resources = $resourceService->getAllTeamResources($circle->getSingleId());
				} catch (\Exception $e) {
					$this->logger->warning('Failed to fetch resources for circle ' . $circle->getSingleId() . ': ' . $e->getMessage());
				}
				
				$teams[] = [
					'singleId' => $circle->getSingleId(),
					'displayName' => $circle->getDisplayName(),
					'name' => $circle->getName(),
					'url' => $this->modelManager->generateLinkToCircle($circle->getSingleId()),
					'members' => $members,
					'resources' => $resources,
				];
			}

			return new DataResponse($teams);
		} catch (\Exception $e) {
			throw new OCSException($e->getMessage(), (int)$e->getCode());
		}
	}

	/**
	 * @return void
	 * @throws FrontendException
	 */
	private function setCurrentFederatedUser(): void {
		if (!$this->configService->getAppValueBool(ConfigService::FRONTEND_ENABLED)) {
			throw new FrontendException('frontend disabled');
		}

		$user = $this->userSession->getUser();
		$this->federatedUserService->setLocalCurrentUser($user);
	}
}
