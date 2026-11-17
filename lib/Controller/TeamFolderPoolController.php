<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Controller;

use OCA\Circles\AuditLog\Operation;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Service\AuditLogService;
use OCA\Circles\Service\TeamFolderPolicy;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\Teams\ITeamFolderPoolProvider;
use OCP\Teams\ITeamManager;
use OCP\Teams\TeamFolderPoolQuotaException;

class TeamFolderPoolController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly ITeamManager $teamManager,
		private readonly CircleRequest $circleRequest,
		private readonly TeamFolderPolicy $policy,
		private readonly AuditLogService $auditLogService,
	) {
		parent::__construct($appName, $request);
	}

	public function getPools(): DataResponse {
		$provider = $this->teamManager->getTeamFolderProvider();
		return new DataResponse([
			'supported' => $provider instanceof ITeamFolderPoolProvider,
			'pools' => $provider instanceof ITeamFolderPoolProvider ? $provider->getStoragePools() : [],
		]);
	}

	#[PasswordConfirmationRequired]
	public function setLimit(?string $groupId, mixed $limit, ?string $sourceTeamId = null): DataResponse {
		return $this->respond(function () use ($groupId, $limit, $sourceTeamId): DataResponse {
			if ($limit !== null && (!is_int($limit) || $limit < 0 || $limit > 9007199254740991)) {
				throw new \InvalidArgumentException('Storage pool limit must be a safe non-negative integer or null');
			}
			$this->assertFinancingTeam($groupId, $sourceTeamId);
			$pool = $this->getProvider()->setStoragePoolLimit($groupId, $limit, $sourceTeamId);
			$this->auditLogService->log(Operation::ConfigUpdated, 'Storage pool "%s" limit was updated', [
				'poolId' => $pool->id,
				'limit' => $limit,
			]);
			return new DataResponse($pool);
		});
	}

	#[PasswordConfirmationRequired]
	public function deletePool(?string $groupId, ?string $sourceTeamId = null): DataResponse {
		return $this->respond(function () use ($groupId, $sourceTeamId): DataResponse {
			if (($groupId === null && $sourceTeamId === null) || ($groupId !== null && $sourceTeamId !== null)) {
				throw new \InvalidArgumentException('Select a group or team pool to delete');
			}
			$this->getProvider()->deleteStoragePool($groupId, $sourceTeamId);
			$this->auditLogService->log(Operation::ConfigUpdated, 'Storage pool was deleted and its teams moved to the default pool', [
				'groupId' => $groupId,
				'sourceTeamId' => $sourceTeamId,
			]);
			return new DataResponse([]);
		});
	}

	#[PasswordConfirmationRequired]
	public function assign(string $circleId, ?string $groupId, ?string $sourceTeamId = null): DataResponse {
		return $this->respond(function () use ($circleId, $groupId, $sourceTeamId): DataResponse {
			$circle = $this->circleRequest->getCircle($circleId);
			if (!$this->policy->isEligibleCircle($circle)) {
				throw new \InvalidArgumentException('This team cannot have a storage pool');
			}
			$this->assertFinancingTeam($groupId, $sourceTeamId);
			$provider = $this->getProvider();
			$provider->assignTeamStoragePool($circleId, $groupId, false, $sourceTeamId);
			$this->auditLogService->log(Operation::TeamSettingUpdated, 'Team "%s" storage pool was updated', [
				'teamId' => $circleId,
				'groupId' => $groupId,
				'sourceTeamId' => $sourceTeamId,
			]);
			return new DataResponse(['storagePoolId' => $provider->getTeamStoragePoolAssignments([$circleId])[$circleId]]);
		});
	}

	private function assertFinancingTeam(?string $groupId, ?string $sourceTeamId): void {
		if ($sourceTeamId === null) {
			return;
		}
		if ($groupId !== null) {
			throw new \InvalidArgumentException('Select either a financing group or a financing team');
		}
		$circle = $this->circleRequest->getCircle($sourceTeamId);
		if (!$this->policy->isEligibleCircle($circle) || !$circle->isLocal()) {
			throw new \InvalidArgumentException('This team cannot finance a storage pool');
		}
	}

	private function getProvider(): ITeamFolderPoolProvider {
		$provider = $this->teamManager->getTeamFolderProvider();
		if (!$provider instanceof ITeamFolderPoolProvider) {
			throw new OCSNotFoundException('The team folder provider does not support storage pools');
		}
		return $provider;
	}

	/** @param callable(): DataResponse $action */
	private function respond(callable $action): DataResponse {
		try {
			return $action();
		} catch (TeamFolderPoolQuotaException $exception) {
			return new DataResponse([
				'code' => 'storage_pool_exceeded',
				'message' => $exception->getMessage(),
				'pool' => $exception->pool,
			], Http::STATUS_CONFLICT);
		} catch (CircleNotFoundException) {
			throw new OCSNotFoundException('Team not found');
		} catch (\InvalidArgumentException $exception) {
			throw new OCSException($exception->getMessage(), Http::STATUS_BAD_REQUEST);
		}
	}
}
