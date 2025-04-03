<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Model;

use JsonSerializable;
use OCA\Circles\Api\v2\TeamSession;
use OCA\Circles\Enum\TeamApi;
use OCA\Circles\Exceptions\TeamOwnerNotFoundException;
use OCA\Circles\Managers\TeamEntityManager;
use OCA\Circles\Managers\TeamMemberManager;
use OCP\AppFramework\Db\Entity;
use OCP\Server;

class TeamCore extends Entity {
	private const DEFAULT_TEAMS_API = TeamApi::V1;
	private ?TeamEntityManager $teamEntityManager = null;
	private ?TeamMemberManager $teamMemberManager = null;

	protected null|TeamSession|TeamEntity $lazyLoadingEntity = null;

	public function enableLazyLoading(TeamSession|TeamEntity $entity): void {
		$this->lazyLoadingEntity = $entity;
	}

	public function getTeamEntityManager(): ?TeamEntityManager {
		if ($this->teamEntityManager === null) {
			$this->teamEntityManager = Server::get(TeamEntityManager::class);
		}

		return $this->teamEntityManager;
	}

	public function getTeamMemberManager(): TeamMemberManager {
		if ($this->teamMemberManager === null) {
			$this->teamMemberManager = Server::get(TeamMemberManager::class);
		}

		return $this->teamMemberManager;
	}

	protected function isApiVersion(TeamApi $api): bool {
		return ($api === $this->getApiVersion());
	}

	protected function getApiVersion(): TeamApi {
		if (!defined('USING_TEAMS_API')) {
			return self::DEFAULT_TEAMS_API;
		}

		return USING_TEAMS_API;
	}
}
