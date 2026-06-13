<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 SURF
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Controller;

use OCA\Circles\Service\AdminManageService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class AdminManageController extends OCSController {

	private AdminManageService $adminManageService;
	private LoggerInterface $logger;
	private string $userId;

	public function __construct(
		string $appName,
		IRequest $request,
		AdminManageService $adminManageService,
		LoggerInterface $logger,
		?string $userId,
	) {
		parent::__construct($appName, $request);
		$this->adminManageService = $adminManageService;
		$this->logger = $logger;
		$this->userId = $userId ?? '';
	}

	/**
	 * @AdminRequired
	 * @NoCSRFRequired
	 */
	public function index(): DataResponse {
		try {
			return new DataResponse($this->adminManageService->listAll());
		} catch (\Exception $e) {
			$this->logger->error('circlesadmin: list failed: ' . $e->getMessage(), ['exception' => $e]);
			return new DataResponse(
				['message' => $e->getMessage()],
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}
	}

	/**
	 * @AdminRequired
	 * @NoCSRFRequired
	 */
	public function show(string $circleId): DataResponse {
		try {
			return new DataResponse($this->adminManageService->getCircle($circleId));
		} catch (\Exception $e) {
			$this->logger->error('circlesadmin: show failed for ' . $circleId . ': ' . $e->getMessage(), ['exception' => $e]);
			return new DataResponse(
				['message' => $e->getMessage()],
				Http::STATUS_NOT_FOUND
			);
		}
	}

	/**
	 * @AdminRequired
	 * @NoCSRFRequired
	 */
	public function create(string $name, string $owner = ''): DataResponse {
		$ownerUserId = $owner ?: $this->userId;
		// Get description from request params (not a method param to avoid Dispatcher issues)
		$params = $this->request->getParams();
		$description = isset($params['description']) ? (string)$params['description'] : null;
		$federated = !empty($params['federated']);
		try {
			return new DataResponse(
				$this->adminManageService->createCircle($name, $ownerUserId, $description, $federated),
				Http::STATUS_CREATED
			);
		} catch (\Exception $e) {
			$this->logger->error('circlesadmin: create failed: ' . $e->getMessage(), ['exception' => $e]);
			return new DataResponse(
				['message' => $e->getMessage()],
				Http::STATUS_BAD_REQUEST
			);
		}
	}

	/**
	 * @AdminRequired
	 * @NoCSRFRequired
	 */
	public function update(string $circleId, ?string $name = null, ?string $description = null): DataResponse {
		if ($name === null && $description === null) {
			return new DataResponse(
				['message' => 'Provide at least one of: name, description'],
				Http::STATUS_BAD_REQUEST
			);
		}
		try {
			return new DataResponse($this->adminManageService->updateCircle($circleId, $name, $description));
		} catch (\Exception $e) {
			$this->logger->error('circlesadmin: update failed for ' . $circleId . ': ' . $e->getMessage(), ['exception' => $e]);
			return new DataResponse(
				['message' => $e->getMessage()],
				Http::STATUS_BAD_REQUEST
			);
		}
	}

	/**
	 * @AdminRequired
	 * @NoCSRFRequired
	 */
	public function destroy(string $circleId): DataResponse {
		try {
			$this->adminManageService->destroyCircle($circleId);
			return new DataResponse(['message' => 'Circle deleted']);
		} catch (\Exception $e) {
			$this->logger->error('circlesadmin: destroy failed for ' . $circleId . ': ' . $e->getMessage(), ['exception' => $e]);
			return new DataResponse(
				['message' => $e->getMessage()],
				Http::STATUS_BAD_REQUEST
			);
		}
	}

	/**
	 * @AdminRequired
	 * @NoCSRFRequired
	 */
	public function members(string $circleId): DataResponse {
		try {
			return new DataResponse($this->adminManageService->getMembers($circleId));
		} catch (\Exception $e) {
			$this->logger->error('circlesadmin: members list failed for ' . $circleId . ': ' . $e->getMessage(), ['exception' => $e]);
			return new DataResponse(
				['message' => $e->getMessage()],
				Http::STATUS_NOT_FOUND
			);
		}
	}

	/**
	 * @AdminRequired
	 * @NoCSRFRequired
	 */
	public function addMember(string $circleId, string $userId): DataResponse {
		try {
			return new DataResponse(
				$this->adminManageService->addMember($circleId, $userId),
				Http::STATUS_CREATED
			);
		} catch (\Exception $e) {
			$this->logger->error('circlesadmin: add member failed for ' . $circleId . '/' . $userId . ': ' . $e->getMessage(), ['exception' => $e]);
			return new DataResponse(
				['message' => $e->getMessage()],
				Http::STATUS_BAD_REQUEST
			);
		}
	}

	/**
	 * @AdminRequired
	 * @NoCSRFRequired
	 */
	public function removeMember(string $circleId, string $memberId): DataResponse {
		try {
			$this->adminManageService->removeMember($circleId, $memberId);
			return new DataResponse(['message' => 'Member removed']);
		} catch (\Exception $e) {
			$this->logger->error('circlesadmin: remove member failed: ' . $e->getMessage(), ['exception' => $e]);
			return new DataResponse(
				['message' => $e->getMessage()],
				Http::STATUS_BAD_REQUEST
			);
		}
	}

	/**
	 * @AdminRequired
	 * @NoCSRFRequired
	 */
	public function setMemberLevel(string $circleId, string $memberId, int $level): DataResponse {
		try {
			$this->adminManageService->setMemberLevel($circleId, $memberId, $level);
			return new DataResponse(['message' => 'Level updated']);
		} catch (\Exception $e) {
			$this->logger->error('circlesadmin: set level failed: ' . $e->getMessage(), ['exception' => $e]);
			return new DataResponse(
				['message' => $e->getMessage()],
				Http::STATUS_BAD_REQUEST
			);
		}
	}
}
