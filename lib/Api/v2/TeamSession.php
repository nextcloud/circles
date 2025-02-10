<?php
/*
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

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Api\v2;

use OCA\Circles\Api\v2\Operation\ITeamMembershipOperation;
use OCA\Circles\Api\v2\Operation\TeamEntityOperation;
use OCA\Circles\Api\v2\Operation\TeamMemberOperation;
use OCA\Circles\Api\v2\Operation\TeamMembershipOperation;
use OCA\Circles\Api\v2\Operation\TeamOperation;
use OCA\Circles\Model\TeamEntity;
use OCA\Circles\Service\TeamEntityService;
use OCP\IUser;
use OCP\IUserSession;

// API/endpoint/occ => TeamSession -> Operation => Service -> (FederatedItem) -> Manager -> Mapper
// API/endpoint/occ => TeamSession -> Operation => Service -> (FederatedItem) -> Manager -> Mapper
// API/endpoint/occ => TeamSession -> Operation => Service -> (FederatedItem) -> Manager -> Mapper
// API/endpoint/occ => TeamSession -> Operation => Service -> (FederatedItem) -> Manager -> Mapper
// API/endpoint/occ => TeamSession -> Operation => Service -> (FederatedItem) -> Manager -> Mapper
// API/endpoint/occ => TeamSession -> Operation => Service -> (FederatedItem) -> Manager -> Mapper

class TeamSession implements ITeamSession {
	private ?TeamEntity $entity = null;
	private ?ITeamOperation $initiatedTeamOperation = null;
	private ?ITeamEntityOperation $initiatedTeamEntityOperation = null;
	private ?ITeamMemberOperation $initiatedTeamMemberOperation = null;
	private ?ITeamMembershipOperation $initiatedTeamMembershipOperation = null;

	public function __construct(
		private IUserSession $userSession,
		private readonly TeamEntityService $teamEntityService,
		private TeamOperation $teamOperation,
		private TeamEntityOperation $teamEntityOperation,
		private TeamMemberOperation $teamMemberOperation,
		private TeamMembershipOperation $teamMembershipOperation,
	) {
		// by default, and if available, ITeamSession uses current user session
		$user = $this->userSession?->getUser();
		if ($user !== null) {
			$this->entity = $this->teamEntityService->generateTeamEntityFromUser($user);
		} else if (\OC::$CLI) {
			$this->entity = $this->teamEntityService->generateTeamEntityFromOcc();
		}
	}

	public function sessionAsCurrentUser(): self {
		$user = $this->userSession?->getUser();
		if ($user === null) {
			return $this;
		}

		return $this->sessionAsEntity($this->teamEntityService->generateTeamEntityFromUser($user));
	}

	public function sessionAsUser(IUser $user): self {
		return $this->sessionAsEntity($this->teamEntityService->generateTeamEntityFromUser($user));
	}

	public function sessionAsLocalUser(string $userId): self {
		return $this->sessionAsEntity($this->teamEntityService->generateTeamEntityFromLocalUser($userId));
	}

	public function sessionAsApp(string $appId): self {
		return $this->sessionAsEntity($this->teamEntityService->generateTeamEntityFromApp($appId));
	}

	public function sessionAsOcc(): self {
		return $this->sessionAsEntity($this->teamEntityService->generateTeamEntityFromOcc());
	}

	public function sessionAsSuperAdmin(): self {
		return $this->sessionAsEntity($this->teamEntityService->generateTeamEntityFromSuperAdmin());
	}

	public function sessionAsEntity(TeamEntity $entity): self {
		$session = clone $this;
		$session->entity = $entity;
		$this->initiatedTeamOperation = null;
		$this->initiatedTeamMemberOperation = null;
		return $session;
	}

	public function getSessionEntity(): ?TeamEntity {
		return $this->entity;
	}

	public function performTeamOperation(): ITeamOperation {
//		$this->initializeTeamApi();
		if ($this->initiatedTeamOperation === null) {
			if ($this->entity === null) {
				throw new \Exception('session must be initiated'); // TODO: specific exception
			}
			$operation = clone $this->teamOperation;
			$operation->asEntity($this->entity);
			$this->initiatedTeamOperation = $operation;
		}

		return $this->initiatedTeamOperation;
	}

	public function performTeamEntityOperation(): ITeamEntityOperation {
//		$this->initializeTeamApi();
		if ($this->initiatedTeamEntityOperation === null) {
			if ($this->entity === null) {
				return $this->teamEntityOperation;
			}
			$operation = clone $this->teamEntityOperation;
			$operation->asEntity($this->entity);
			$this->initiatedTeamEntityOperation = $operation;
		}

		return $this->initiatedTeamEntityOperation;
	}

	public function performTeamMemberOperation(): ITeamMemberOperation {
//		$this->initializeTeamApi();
		if ($this->initiatedTeamMemberOperation === null) {
			if ($this->entity === null) {
				throw new \Exception('session must be initiated'); // TODO: specific exception
			}
			$operation = clone $this->teamMemberOperation;
			$operation->asEntity($this->entity);
			$this->initiatedTeamMemberOperation = $operation;
		}

		return $this->initiatedTeamMemberOperation;
	}

	public function performTeamMembershipOperation(): ITeamMembershipOperation {
//		$this->initializeTeamApi();
		if ($this->initiatedTeamMembershipOperation === null) {
			if ($this->entity === null) {
				throw new \Exception('session must be initiated'); // TODO: specific exception
			}
			$operation = clone $this->teamMembershipOperation;
			$operation->asEntity($this->entity);
			$this->initiatedTeamMembershipOperation = $operation;
		}

		return $this->initiatedTeamMembershipOperation;
	}

	private function initializeTeamApi(): void {
//		if (!defined('USING_TEAMS_API')) {
//			define('USING_TEAMS_API', TeamApi::NOT_API);
//		}
	}
}
